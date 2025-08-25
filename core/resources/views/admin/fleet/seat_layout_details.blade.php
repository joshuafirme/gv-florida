@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="row">
                        <div class="col-md-4">
                            @include('templates.basic.partials.seat_layout', [
                                'fleetType' => $fleetType,
                            ])
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
