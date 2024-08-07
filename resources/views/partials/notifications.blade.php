<div id="notification-box"
    class="hidden notification-box bg-white absolute w-[300px] md:w-[600px] top-[calc(100%+10px)] rounded-lg overflow-y-scroll border-gray-200 border-[3.5px] py-[24px] px-[24px] max-h-[300px]">

    <div class="font-roboto-bold text-[22px] text-primary leading-[22px]">Notificaciones</div>

    <hr class="mt-[18px] border-gray-300" />

    @if (!empty($notifications))
        @foreach ($notifications as $notification)
            <div data-notification_uid="{{ $notification['uid'] }}" data-notification_type="{{ $notification['type'] }}"
                class="notification cursor-pointer">
                <div class="select-none py-[18px] flex gap-2">
                    @if (!$notification['is_read'])
                        <div class="not-read">
                            <svg class="mt-3" xmlns="http://www.w3.org/2000/svg" width="4" height="4"
                                viewBox="0 0 4 4" fill="none">
                                <circle cx="2" cy="2" r="2" fill="#FF0000" />
                            </svg>
                        </div>
                    @endif
                    <div class="w-full cursor-pointer {{ $notification['is_read'] ? 'ml-2' : '' }}">
                        <div class="flex justify-between items-center gap-2 mb-[4px]">
                            <div class="font-roboto-bold flex-shrink truncate">
                                {{ $notification['title'] }}
                            </div>
                            <div class="text-[#697386] text-[10px] flex-auto whitespace-nowrap text-right">
                                {{ formatDateTimeNotifications($notification['date']) }}
                            </div>
                        </div>
                        <p class="truncate">
                            {{ strip_tags($notification['description']) }}
                        </p>
                    </div>

                </div>
            </div>
            <hr class="border-gray-300" />
        @endforeach
    @else
        <div class=" text-center mt-[28px]">
            <p class="font-roboto-bold text-[16px]">
                Sin notificaciones
            </p>

            <p class="mt-1.5 text-[14px]">
                Te avisaremos tan pronto haya una actualización o se suba un nuevo curso.
            </p>
        </div>
    @endif


</div>
