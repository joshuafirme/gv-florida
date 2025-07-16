@extends($activeTemplate.'layouts.frontend')
@section('content')
 <!-- Privacy Policy Section Starts Here -->
<section class="privacy-policy padding-top padding-bottom">
    <div class="container">
        <div class="row gy-5">
                <div class="col-lg-12">
                <div class="privacy-policy-content">
                    <p>
                        @php
                            echo @$policy->data_values->details
                        @endphp
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Privacy Policy Section Ends Here -->
@endsection
