<?php

namespace App\Http\Controllers\Notifications;

use App\Models\NotificationsChangesStatusesCoursesModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationsChangesStatusesCoursesController extends BaseController
{

    use AuthorizesRequests, ValidatesRequests;

    public function getNotificationsChangesStatusesCoursesController($status_notification_uid)
    {
        $notification = NotificationsChangesStatusesCoursesModel::with(['course' => function ($query) {
            $query->select('uid', 'title');
        }])->with('status')->where('uid', $status_notification_uid)->where('user_uid', Auth::user()['uid'])->select('notifications_changes_statuses_courses.*')->first();

        // La marcamos como leída
        $notification->is_read = 1;
        $notification->save();

        return response()->json($notification->toArray());
    }
}
