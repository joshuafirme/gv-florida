<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ gs()->siteName($pageTitle ?? '') }}</title>

    @php
        $v = buildVer();
    @endphp


    <link rel="shortcut icon" type="image/png" href="{{ siteFavicon() }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/global/css/bootstrap.min.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/admin/css/vendor/bootstrap-toggle.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/global/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/global/css/line-awesome.min.css') }}">

    @stack('style-lib')

    <link rel="stylesheet" href="{{ asset('assets/global/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/app.css?v=' . $v) }}">
    <link href="https://cdn.datatables.net/v/bs5/dt-2.3.4/cc-1.1.0/r-3.0.6/datatables.min.css" rel="stylesheet"
        integrity="sha384-J9sByJqc7aFtpa/p3+RvVXdwDcmYzOe4xoAvHPrnbK0IU/LEyeVf3FvlpuCKk0Xf" crossorigin="anonymous">

    @stack('style')
</head>

<body>
    @yield('content')

    <script src="{{ asset('assets/global/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/vendor/bootstrap-toggle.min.js') }}"></script>


    @include('partials.notify')
    @stack('script-lib')

    <script src="{{ asset('assets/global/js/nicEdit.js') }}"></script>

    <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/app.js') }}"></script>


    <script src="https://cdn.datatables.net/v/bs5/dt-2.3.4/cc-1.1.0/r-3.0.6/datatables.min.js"
        integrity="sha384-Q8EUWi3cPzrh/7cmW/l0GdIB2qJ25Wa0rLHPfuQpKZt88OBP2xV5f2ejnXdNyKLJ" crossorigin="anonymous">
    </script>


    <script>
        let table = new DataTable('#datatable');
    </script>

    {{-- LOAD NIC EDIT --}}
    <script>
        "use strict";
        bkLib.onDomLoaded(function() {
            $(".nicEdit").each(function(index) {
                $(this).attr("id", "nicEditor" + index);
                new nicEditor({
                    fullPanel: true
                }).panelInstance('nicEditor' + index, {
                    hasPanel: true
                });
            });
        });
        (function($) {
            $(document).on('mouseover ', '.nicEdit-main,.nicEdit-panelContain', function() {
                $('.nicEdit-main').focus();
            });

            $('.breadcrumb-nav-open').on('click', function() {
                $(this).toggleClass('active');
                $('.breadcrumb-nav').toggleClass('active');
            });

            $('.breadcrumb-nav-close').on('click', function() {
                $('.breadcrumb-nav').removeClass('active');
            });

            if ($('.topTap').length) {
                $('.breadcrumb-nav-open').removeClass('d-none');
            }
        })(jQuery);
    </script>

    @stack('script')


</body>

</html>
