<!doctype html>
<html lang="en" itemscope itemtype="http://schema.org/WebPage">

@php
    $v = buildVer();
@endphp

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title> {{ gs()->sitename(__($pageTitle)) }}</title>
    @include('partials.seo')

    <!-- BootStrap Link -->
    <link rel="stylesheet" href="{{ asset('assets/global/css/bootstrap.min.css') }}">
    <!-- Icon Link -->
    <link rel="stylesheet" href="{{ asset('assets/global/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/global/css/line-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset($activeTemplateTrue . 'css/flaticon.css') }}">

    <!-- Custom Link -->
    <link rel="stylesheet" href="{{ asset($activeTemplateTrue . 'css/main.css?v=' . $v) }}">
    <link rel="stylesheet" href="{{ asset($activeTemplateTrue . 'css/custom.css?v=' . $v) }}">
    <link rel="stylesheet" href="{{ asset($activeTemplateTrue . 'css/color.php?color=' . gs('base_color')) }}">
    @stack('style-lib')

    @stack('style')
</head>
@php echo loadExtension('google-analytics') @endphp

<body>
    <div class="overlay"></div>
    @include($activeTemplate . 'partials.preloader')

    @yield('app')

    <script src="{{ asset('assets/global/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset($activeTemplateTrue . 'js/main.js?v=' . $v) }}"></script>

    @stack('script-lib')

    @php echo loadExtension('tawk-chat') @endphp

    @include('partials.notify')

    @if (gs('pn'))
        @include('partials.push_script')
    @endif

    @stack('script')

    <script>
        (function($) {

            "use strict";
            var inputElements = $('[type=text],select,textarea');

            $.each(inputElements, function(index, element) {
                element = $(element);
                element.closest('.form-group').find('label').attr('for', element.attr('name'));
                element.attr('id', element.attr('name'));
            });

            $.each($('input, select, textarea'), function(i, element) {
                var elementType = $(element);
                if (elementType.attr('type') != 'checkbox') {
                    if (element.hasAttribute('required')) {
                        $(element).closest('.form-group').find('label').addClass('required');
                    }
                }
            });

            let disableSubmission = false;
            $('.disableSubmission').on('submit', function(e) {
                if (disableSubmission) {
                    e.preventDefault()
                } else {
                    disableSubmission = true;
                }
            });

            $("#confirmationModal").find('.btn--primary').removeClass('btn--primary').addClass('btn--base');
        })(jQuery);
    </script>
</body>

</html>
