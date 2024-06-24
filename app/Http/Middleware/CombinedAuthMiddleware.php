<?php

namespace App\Http\Middleware;

use App\Models\GeneralNotificationsModel;
use App\Models\NotificationsChangesStatusesCoursesModel;
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

        $user_array = $user->toArray();

        $uids_roles = array_map(function ($item) {
            return $item['uid'];
        }, $user_array['roles']);

        $user_uid = $user['uid'];

        $general_notifications = GeneralNotificationsModel::with(['users', 'roles'])
            ->where(function ($query) use ($user_uid, $uids_roles) {
                $query->where(function ($q) use ($user_uid) {
                    $q->where('type', 'USERS')
                        ->whereHas('users', function ($query) use ($user_uid) {
                            $query->where('user_uid', $user_uid);
                        });
                })
                    ->orWhere(function ($q) use ($uids_roles) {
                        $q->where('type', 'ROLES')
                            ->whereHas('roles', function ($query) use ($uids_roles) {
                                $query->whereIn('rol_uid', $uids_roles);
                            });
                    })
                    ->orWhere('type', 'ALL_USERS');
            })
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->addSelect([
                'is_read' => UserGeneralNotificationsModel::select(DB::raw('IF(COUNT(*), 1, 0)'))
                    ->whereColumn('user_general_notifications.general_notification_uid', 'general_notifications.uid')
                    ->where('user_general_notifications.user_uid', $user_uid)
                    ->limit(1)
            ])
            ->orderBy('start_date', 'desc')
            ->get()->map(function ($notification) {
                $notification->date = $notification->start_date;
                $notification->type = 'general';
                return $notification;
            });

        // Sólo incluímos estas notificaciones si el usuario no ha desactivado las notificaciones de comunicaciones de matriculación
        $courseEnrollmentCommunications = $user->hasAnyAutomaticGeneralNotificationTypeDisabled(['COURSE_ENROLLMENT_COMMUNICATIONS']);
        if (!$courseEnrollmentCommunications) {
            $notifications_changes_statuses_courses = NotificationsChangesStatusesCoursesModel::with(['user', 'status', 'course'])
                ->where('user_uid', $user_uid)
                ->where('date', '>=', now()->subDays(7))
                ->orderBy('date', 'desc')
                ->get()->map(function ($notification) {
                    $notification->type = 'course_status';
                    return $notification;
                });

            // Combinamos notificaciones generales con notificaciones de cambios de estado de cursos y ordenamos por fecha
            $notifications = $general_notifications->concat($notifications_changes_statuses_courses)
                ->sortByDesc('date')->toArray();
        } else {
            $notifications = $general_notifications->toArray();
        }

        // Comprobamos si tiene alguna notificación sin leer para mostrarlo en el icono de notificaciones
        $is_read_values = array_column($notifications, 'is_read');
        $unread_notifications = in_array(0, $is_read_values);

        View::share('notifications', $notifications);
        View::share('unread_notifications', $unread_notifications);
        View::share('roles', $user['roles']->toArray());
        Auth::login($user);
    }
}
