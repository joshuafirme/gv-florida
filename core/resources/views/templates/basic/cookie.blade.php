@extends('Template::layouts.frontend')
@section('content')
    <section class="about-section padding-top padding-bottom">
        <div class="container">
            <div class="row mb-4 mb-md-5 gy-4">
                <div class="col-lg-12">
                    @php
                        echo @$cookie->data_values->description;
                    @endphp
                </div>
            </div>
        </div>
    </section>
@endsection
