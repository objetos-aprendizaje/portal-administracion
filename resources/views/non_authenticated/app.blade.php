<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        :root {
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

    @vite(['resources/css/app.css', 'resources/scss/app.scss', 'resources/css/toastify.css'])

    @if (isset($resources) && is_array($resources))
        @vite($resources)
    @endif

    <script>
        @if(Session::has('error'))
            var error = "{{ Session::get('error') }}";
        @endif
    </script>

</head>

<body>
    @yield('content')

</body>

</html>
