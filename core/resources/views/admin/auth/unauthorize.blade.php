@extends('admin.layouts.app')
@section('panel')

    <section class="bg-white py-5">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="text-center mt-4">
                        <img class="img-fluid p-4" src="{{ asset('assets/images/401-error-unauthorized.svg') }}" alt="" />
                        <p class="lead">You are not authorize to access <b>{{ decodeSlug(request()->page, '.') }}</b>.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
