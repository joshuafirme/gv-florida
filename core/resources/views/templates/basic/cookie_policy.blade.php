@extends($activeTemplate.'layouts.frontend')
@section('content')

<section class="privacy-policy padding-top padding-bottom">
    <div class="container">
        <div class="row gy-5">
            <div class="col-lg-12">
                <div class="privacy-policy-content">
                    <p>
                        @php
                            echo @$cookie->data_values->details
                        @endphp
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
