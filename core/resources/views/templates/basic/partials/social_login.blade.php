@php
    $text = isset($register) ? "Register" : "Login";
@endphp
@if (@gs("socialite_credentials")->google->status == Status::ENABLE)
    <div class="continue-google mb-3">
        <a class="btn w-100 social-login-btn" href="{{ route("user.social.login", "google") }}">
            <span class="google-icon">
                <img src="{{ asset($activeTemplateTrue . "images/google.svg") }}" alt="Google">
            </span> @lang("$text with Google")
        </a>
    </div>
@endif
@if (@gs("socialite_credentials")->facebook->status == Status::ENABLE)
    <div class="continue-facebook mb-3">
        <a class="btn w-100 social-login-btn" href="{{ route("user.social.login", "facebook") }}">
            <span class="facebook-icon">
                <img src="{{ asset($activeTemplateTrue . "images/facebook.svg") }}" alt="Facebook">
            </span> @lang("$text with Facebook")
        </a>
    </div>
@endif
@if (@gs("socialite_credentials")->linkedin->status == Status::ENABLE)
    <div class="continue-facebook mb-3">
        <a class="btn w-100 social-login-btn" href="{{ route("user.social.login", "linkedin") }}">
            <span class="facebook-icon">
                <img src="{{ asset($activeTemplateTrue . "images/linkdin.svg") }}" alt="Linkedin">
            </span> @lang("$text with Linkedin")
        </a>
    </div>
@endif

@if (@gs("socialite_credentials")->linkedin->status || @gs("socialite_credentials")->facebook->status == Status::ENABLE || @gs("socialite_credentials")->google->status == Status::ENABLE)
    <div class="w-100 another-login mb-4 text-center">
        <span class="another-login__or">@lang("OR")</span>
    </div>
@endif
@push("style")
    <style>
        .social-login-btn {
            border: 1px solid #cbc4c4;
            color: hsl(var(--black)) !important;
            display: flex !important;
            justify-content: center;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            padding: 7px 10px;
            transition: .2s linear;
            line-height: 1;
        }

        .social-login-btn:hover {
            border-color: var(--main-color) !important;
            color: var(--main-color) !important;
        }

        .another-login {
            position: relative;
            z-index: 1;
        }

        .another-login__or {
            background-color: #fff;
            padding: 0 7px;
        }

        .another-login::after {
            position: absolute;
            content: '';
            top: 50%;
            left: 0;
            width: 100%;
            border-bottom: 1px dashed #cbc4c4;
            z-index: -1;
        }
    </style>
@endpush
