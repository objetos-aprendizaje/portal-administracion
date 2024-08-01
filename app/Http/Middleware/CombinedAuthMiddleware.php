<?php

namespace App\Http\Middleware;

use App\Models\GeneralNotificationsAutomaticModel;
use App\Models\GeneralNotificationsModel;
use App\Models\UserGeneralNotificationsModel;
use App\Models\UsersModel;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
class CombinedAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $request->session()->regenerate();
        // Comprobamos si tenemos usuario
        if (Auth::check()) {
            try {
                $this->loadUserData(Auth::user()->email);
            } catch (\Exception $e) {
                return redirect('login')->withErrors($e->getMessage());
            }

            return $next($request);
        } elseif ($this->isAuthenticatedWithGoogle($request) || $this->isAuthenticatedWithTwitter($request) || $this->isAuthenticatedWithFacebook($request) || $this->isAuthenticatedWithLinkedin($request)) {
            try {
                $email_user = $request->session()->get('email');
                $this->loadUserData($email_user);
                return $next($request);
            } catch (\Exception $e) {
                return redirect('login')->withErrors($e->getMessage());
            }
        }

        // Redirigir a la página de inicio de sesión o mostrar un mensaje de error
        return redirect('login');
    }

    protected function isAuthenticatedWithGoogle($request)
    {
        return $request->session()->has('google_id');
    }

    protected function isAuthenticatedWithTwitter($request)
    {
        return $request->session()->has('twitter_id');
    }

    protected function isAuthenticatedWithLinkedin($request)
    {
        return $request->session()->has('linkedin_id');
    }

    protected function isAuthenticatedWithFacebook($request)
    {
        return $request->session()->has('facebook_id');
    }

    private function loadUserData($user_email)
    {
        $user = UsersModel::where('email', $user_email)->with("roles")->first();

        if (!$user) {
            throw new \Exception('No hay ninguna cuenta asociada al email');
        }

        $notifications = $this->getNotifications($user);

        // Comprobamos si tiene alguna notificación sin leer para mostrarlo en el icono de notificaciones
        $is_read_values = array_column($notifications, 'is_read');
        $unread_notifications = in_array(0, $is_read_values);

        View::share('notifications', $notifications);
        View::share('unread_notifications', $unread_notifications);
        View::share('roles', $user['roles']->toArray());
        Auth::login($user);
    }

    private function getNotifications($user)
    {
        $generalNotificationsQuery = $this->getGeneralNotifications($user);
        $generalNotificationsAutomaticQuery = $this->getGeneralNotificationsAutomatic($user);

        // Unimos las dos querys
        $unionQueries = $generalNotificationsQuery->unionAll($generalNotificationsAutomaticQuery);

        $unionQueries->orderBy('date', 'DESC');

        return $unionQueries->get()->toArray();
    }

    private function getGeneralNotifications($user)
    {

        $user_array = $user->toArray();

        $uids_roles = array_map(function ($item) {
            return $item['uid'];
        }, $user_array['roles']);

        $user_uid = $user['uid'];

        $generalNotificationsQuery = GeneralNotificationsModel::query()
            ->where(function ($query) use ($user_uid, $uids_roles) {
                $query->where(function ($q) use ($user_uid) {
                    $q->where('type', 'USERS')
                        ->whereExists(function ($query) use ($user_uid) {
                            $query->select(DB::raw(1))
                                ->from('destinations_general_notifications_users')
                                ->whereColumn('destinations_general_notifications_users.general_notification_uid', 'general_notifications.uid')
                                ->where('destinations_general_notifications_users.user_uid', $user_uid);
                        });
                })
                ->orWhere(function ($q) use ($uids_roles) {
                    $q->where('type', 'ROLES')
                        ->whereExists(function ($query) use ($uids_roles) {
                            $query->select(DB::raw(1))
                                ->from('destinations_general_notifications_roles')
                                ->whereColumn('destinations_general_notifications_roles.general_notification_uid', 'general_notifications.uid')
                                ->whereIn('destinations_general_notifications_roles.rol_uid', $uids_roles);
                        });
                })
                ->orWhere('type', 'ALL_USERS');
            })
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->select([
                'general_notifications.uid as uid',
                'general_notifications.title as title',
                'general_notifications.description as description',
                'general_notifications.created_at as date',
                DB::raw("'general' as type"),
                // Subconsulta para determinar si la notificación ha sido leída por el usuario
                'is_read' => UserGeneralNotificationsModel::select(DB::raw('IF(COUNT(*), 1, 0)'))
                    ->whereColumn('user_general_notifications.general_notification_uid', 'general_notifications.uid')
                    ->where('user_general_notifications.user_uid', $user_uid)
                    ->limit(1),
            ]);

            return $generalNotificationsQuery;
    }

    private function getGeneralNotificationsAutomatic($user)
    {
        $generalNotificationsAutomaticQuery = GeneralNotificationsAutomaticModel::leftJoin('automatic_notification_types', 'automatic_notification_types.uid', '=', 'general_notifications_automatic.automatic_notification_type_uid')
            ->leftJoin('general_notifications_automatic_users', 'general_notifications_automatic_users.general_notifications_automatic_uid', '=', 'general_notifications_automatic.uid')
            ->select('general_notifications_automatic.uid', 'general_notifications_automatic.title', 'general_notifications_automatic.description', 'general_notifications_automatic.created_at as date', DB::raw("'automatic' as type"), 'general_notifications_automatic_users.is_read')
            ->where('general_notifications_automatic_users.user_uid', $user->uid);

        return $generalNotificationsAutomaticQuery;
    }
}
