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
        // Comprobamos si tenemos usuario
        if (Auth::check()) {
            try {
                $this->loadUserData(Auth::user());
            } catch (\Exception $e) {
                return redirect('login')->withErrors($e->getMessage());
            }

            return $next($request);
        }

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

    private function loadUserData($user)
    {

        Auth::user()->load('roles');

        $notifications = $this->getNotifications($user);

        // Comprobamos si tiene alguna notificación sin leer para mostrarlo en el icono de notificaciones
        $isReadValues = array_column($notifications, 'is_read');
        $unreadNotifications = in_array(0, $isReadValues);

        View::share('notifications', $notifications);
        View::share('unread_notifications', $unreadNotifications);
        View::share('roles', $user['roles']->toArray());
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
        $userArray = $user->toArray();

        $uidsRoles = array_map(function ($item) {
            return $item['uid'];
        }, $userArray['roles']);

        $userUid = $user['uid'];

        return GeneralNotificationsModel::query()
            ->where(function ($query) use ($userUid, $uidsRoles) {
                $query->where(function ($q) use ($userUid) {
                    $q->where('type', 'USERS')
                        ->whereExists(function ($query) use ($userUid) {
                            $query->select(DB::raw(1))
                                ->from('destinations_general_notifications_users')
                                ->whereColumn('destinations_general_notifications_users.general_notification_uid', 'general_notifications.uid')
                                ->where('destinations_general_notifications_users.user_uid', $userUid);
                        });
                })
                    ->orWhere(function ($q) use ($uidsRoles) {
                        $q->where('type', 'ROLES')
                            ->whereExists(function ($query) use ($uidsRoles) {
                                $query->select(DB::raw(1))
                                    ->from('destinations_general_notifications_roles')
                                    ->whereColumn('destinations_general_notifications_roles.general_notification_uid', 'general_notifications.uid')
                                    ->whereIn('destinations_general_notifications_roles.rol_uid', $uidsRoles);
                            });
                    })
                    ->orWhere('type', 'ALL_USERS');
            })
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            // Exclusión de notificaciones deshabilitadas por el usuario
            ->whereNotIn('notification_type_uid', function ($query) use ($userUid) {
                $query->select('notification_type_uid')
                    ->from('user_general_notification_types_disabled')
                    ->where('user_general_notification_types_disabled.user_uid', $userUid);
            })
            ->select([
                'general_notifications.uid as uid',
                'general_notifications.title as title',
                'general_notifications.description as description',
                'general_notifications.created_at as date',
                DB::raw("'general' as type"),
                // Subconsulta para determinar si la notificación ha sido leída por el usuario
                'is_read' => UserGeneralNotificationsModel::select(DB::raw('CAST(CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END AS INTEGER)'))
                    ->whereColumn('user_general_notifications.general_notification_uid', 'general_notifications.uid')
                    ->where('user_general_notifications.user_uid', $userUid)
                    ->limit(1),
            ]);
    }

    private function getGeneralNotificationsAutomatic($user)
    {
        return GeneralNotificationsAutomaticModel::leftJoin('automatic_notification_types', 'automatic_notification_types.uid', '=', 'general_notifications_automatic.automatic_notification_type_uid')
            ->leftJoin('general_notifications_automatic_users', 'general_notifications_automatic_users.general_notifications_automatic_uid', '=', 'general_notifications_automatic.uid')
            ->select('general_notifications_automatic.uid', 'general_notifications_automatic.title', 'general_notifications_automatic.description', 'general_notifications_automatic.created_at as date', DB::raw("'automatic' as type"), DB::raw('CAST(general_notifications_automatic_users.is_read AS INTEGER) as is_read'))
            ->where('general_notifications_automatic_users.user_uid', $user->uid);
    }
}
