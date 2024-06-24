<header id="poa-header" class="h-[120px] p-8 border-t-4 border-gray border-b-4 w-full fixed bg-white z-50 top-0">

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

                @include('partials.notifications')

            </div>
            <div>
                <img src="{{ asset(Auth::user()->photo_path ?? 'data/images/default_images/no-user.svg') }}"
                    class="rounded-full h-12 w-12" />
            </div>
            <div class="border-l border-gray-300"></div>
            <div>
                <p class="test">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</p>
                <p class="text-gray-400">{{ Auth::user()->roles->pluck("name")->implode(', ') }}</p>
            </div>

            <div class="flex items-center" title="Cerrar sesión">
                <a href="{{ env('APP_URL') }}/logout">
                    <button type="button" class="btn btn-primary btn-close-session">Cerrar sesión
                        {{ e_heroicon('arrow-left-on-rectangle', 'outline') }}
                    </button>
                </a>
            </div>
        </div>
    </div>
</header>
