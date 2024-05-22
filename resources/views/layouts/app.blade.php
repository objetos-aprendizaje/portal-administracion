<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        :root {
            --color-hover: #507ab9;

            --primary-color: {{ env('COLOR_PRIMARY') }};
            --secondary-color: {{ env('COLOR_SECONDARY') }};
        }
    </style>

    <title>
        @if (isset($page_title))
            {{ $page_title }}
        @else
            POA
        @endif
    </title>

    <script>
        window.sessionLifetime = {{ config('session.lifetime') }};
        window.userRoles = @json($roles);
    </script>

    @vite(['resources/css/app.css', 'resources/scss/app.scss', 'resources/css/toastify.css', 'resources/js/cookie_handler.js', 'resources/js/app.js', 'resources/js/menu.js', 'resources/js/modal_handler.js', 'resources/js/loading_handler.js', 'resources/js/notifications_handler.js', 'resources/js/refresh_csrf_token.js'])

    @if (isset($variables_js))
        <script>
            @foreach ($variables_js as $name => $value)
                window['{{ $name }}'] = @json($value);
            @endforeach
        </script>
    @endif

    @if (isset($tabulator) && $tabulator)
        @vite(['node_modules/tabulator-tables/dist/css/tabulator.min.css', 'resources/js/tabulator_handler.js'])
    @endif

    @if (isset($coloris) && $coloris)
        @vite(['resources/js/coloris.min.js', 'resources/css/coloris.min.css'])
    @endif

    @if (isset($tomselect) && $tomselect)
        @vite(['node_modules/tom-select/dist/css/tom-select.min.css'])
    @endif

    @if (isset($resources) && is_array($resources))
        @vite($resources)
    @endif

    @if (isset($flatpickr) && $flatpickr)
        @vite(['node_modules/flatpickr/dist/flatpickr.css'])
    @endif
    @if (isset($tinymce) && $tinymce)
        <script src="/dist/tinymce/tinymce.min.js"></script>
    @endif

    @if (isset($choicesjs) && $choicesjs)
        @vite(['node_modules/choices.js/public/assets/styles/choices.min.css', 'node_modules/choices.js/public/assets/scripts/choices.min.js'])
    @endif

    @if (isset($treeselect) && $treeselect)
        @vite(['node_modules/treeselectjs/dist/treeselectjs.css'])
    @endif

</head>

<body>

    <div id="fullscreen-loader" class="fullscreen-loader">
        <div class="spinner"></div>
        <div class="text-primary text-[19px]">Cargando...</div>
    </div>

    @include('partials.header')
    <div>
        @include('partials.menu')

        <div class="bg-[#EEEEEE] p-8" id="main-content">
            @yield('content')
        </div>
    </div>

    @include('partials.loading')
    @include('partials.notification-info-modal')
    @include('partials.notification-change-course-status-modal')

</body>

</html>
