@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body p-3">
                    <form action="{{ route('admin.advance.booking.update-allowed-days') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="allowed_days">Allowed Advance Booking Days</label>
                            <input type="number" name="allowed_days" id="allowed_days" class="form-control"
                                value="{{ $data['allowed_days'] }}" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection