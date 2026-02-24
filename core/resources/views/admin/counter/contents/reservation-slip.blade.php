@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-md-12 mb-30">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.counter.reservation-slip.udpate') }}" class="disableSubmission"
                        method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            {{-- <div class="col-md-12">
                                <div class="form-group">
                                    <label>{{ __(keyToTitle($k)) }}</label>
                                    <textarea rows="10" class="form-control" name="{{ $k }}" required>{{ old($k, @$data->data_values->$k) }}</textarea>
                                </div>
                            </div> --}}

                            <input type="hidden" name="counter_id" value="{{ $counter->id }}">

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Heading</label>
                                    <input type="text" class="form-control" name="heading" value="{{ isset($data->heading) ? $data->heading : '' }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Subheading</label>
                                    <input type="text" class="form-control" name="subheading" value="{{ isset($data->subheading) ? $data->subheading : '' }}">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Sub header</label>
                                    <textarea rows="10" class="form-control nicEdit" name="terms_and_conditions">{{ isset($data->terms_and_conditions) ? $data->terms_and_conditions : '' }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
