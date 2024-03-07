<header id="poa-header" class="p-8 border-t-4 border-gray border-b-4 w-full fixed bg-white z-50 top-0">

    <div class="flex justify-between">

        <div class="flex items-center gap-2">
            <button id="toggle-menu-btn" class="text-gray-600 focus:outline-none bg-gray-100 p-2 rounded-full">
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16">
                    </path>
                </svg>
            </button>
            <h1>
                @if (isset($page_name))
                    {{ $page_name }}
                @else
                    POA
                @endif
            </h1>
        </div>
        <div class="flex gap-4">
            <div class="notifications">
                <div class="bell" id="bell-btn">
                    {{ e_heroicon('bell', 'solid') }}
                    <div id="notification-dot"
                        class="notification-dot {{ $unread_notifications ? 'block' : 'hidden' }}"></div>
                </div>

                <div id="notification-box"
                    class="hidden notification-box bg-white absolute w-[600px] top-[calc(100%+10px)] right-0 rounded-lg overflow-y-scroll border-gray-200 border-[3.5px] py-[24px] px-[24px]">

                    <div class="font-roboto-bold text-[22px] text-primary leading-[22px]">Notificaciones</div>

                    <hr class="mt-[18px] border-gray-300" />


                    @if (!empty($notifications))
                        @foreach ($notifications as $notification)
                            <div data-notification_uid="{{ $notification['uid'] }}" data-notification_type="{{ $notification['type'] }}" class="notification cursor-pointer">
                                <div class="select-none py-[18px] flex gap-2">
                                    @if (!$notification['is_read'])
                                        <div class="not-read">
                                            <svg class="mt-3" xmlns="http://www.w3.org/2000/svg" width="4"
                                                height="4" viewBox="0 0 4 4" fill="none">
                                                <circle cx="2" cy="2" r="2" fill="#FF0000" />
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="w-full cursor-pointer {{$notification['is_read'] ? 'ml-2' : ''}}">
                                        <div class="flex justify-between items-center gap-2 mb-[4px]">
                                            <div class="font-roboto-bold flex-shrink truncate">
                                                @if ($notification['type'] == 'general')
                                                    {{ $notification['title'] }}
                                                @elseif ($notification['type'] == 'course_status')
                                                    Ha cambiado el estado del curso
                                                @endif
                                            </div>
                                            <div
                                                class="text-[#697386] text-[10px] flex-auto whitespace-nowrap text-right">
                                                {{ formatDateTimeNotifications($notification['date']) }}
                                            </div>
                                        </div>
                                        <p class="truncate">
                                            @if ($notification['type'] == 'general')
                                                {{ $notification['description'] }}
                                            @elseif ($notification['type'] == 'course_status')
                                                Ha cambiado el estado del curso {{ $notification['course']['title'] }}
                                            @endif
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
            </div>
            <div>
                <img src="{{ asset(Auth::user()->photo_path ?? 'data/images/default_images/no-user.svg') }}"
                    class="rounded-full h-12 w-12" />
            </div>
            <div class="border-l border-gray-300"></div>
            <div>
                <p class="test">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</p>
                <p class="text-gray-400 text-">Administrador</p>

            </div>

            <div class="flex items-center" title="Cerrar sesión">
                <a href="/logout">
                    <button type="button" class="btn btn-primary btn-close-session">Cerrar sesión
                        {{ e_heroicon('arrow-left-on-rectangle', 'outline') }}
                    </button>
                </a>
            </div>
        </div>
    </div>
</header>
