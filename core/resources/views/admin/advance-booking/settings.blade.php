@extends('admin.layouts.app')

@section('panel')
    <form action="{{ route('admin.advance.booking.update-allowed-days') }}" method="POST" class="booking-settings-form">
        @csrf

        <section class="booking-setting-panel">
            <div class="booking-setting-heading">
                <span class="booking-setting-icon"><i class="las la-clock"></i></span>
                <div>
                    <h5>Booking Cutoff</h5>
                    <p>When sales close before a trip departs.</p>
                </div>
            </div>

            <div class="booking-setting-row">
                <label for="online_booking_cutoff_minutes">Online</label>
                <div class="booking-setting-control">
                    <input type="number" name="online_booking_cutoff_minutes" id="online_booking_cutoff_minutes"
                        value="{{ old('online_booking_cutoff_minutes', $data['online_booking_cutoff_minutes']) }}"
                        min="0" max="1440" step="1" required>
                    <span>min before<br>departure</span>
                </div>
            </div>

            <div class="booking-setting-row">
                <label for="kiosk_booking_cutoff_minutes">Kiosk / Counter</label>
                <div class="booking-setting-control">
                    <input type="number" name="kiosk_booking_cutoff_minutes" id="kiosk_booking_cutoff_minutes"
                        value="{{ old('kiosk_booking_cutoff_minutes', $data['kiosk_booking_cutoff_minutes']) }}"
                        min="0" max="1440" step="1" required>
                    <span>min before<br>departure</span>
                </div>
            </div>
        </section>

        <section class="booking-setting-panel">
            <div class="booking-setting-heading">
                <span class="booking-setting-icon"><i class="las la-calendar"></i></span>
                <div>
                    <h5>Advance Window</h5>
                    <p>How far ahead each channel can sell.</p>
                </div>
            </div>

            <div class="booking-setting-row">
                <label for="online_advance_booking_days">Online</label>
                <div class="booking-setting-control">
                    <input type="number" name="online_advance_booking_days" id="online_advance_booking_days"
                        value="{{ old('online_advance_booking_days', $data['online_advance_booking_days']) }}"
                        min="0" max="365" step="1" required>
                    <span>days ahead</span>
                </div>
            </div>

            <div class="booking-setting-row">
                <label for="kiosk_advance_booking_days">Kiosk / Counter</label>
                <div class="booking-setting-control">
                    <input type="number" name="kiosk_advance_booking_days" id="kiosk_advance_booking_days"
                        value="{{ old('kiosk_advance_booking_days', $data['kiosk_advance_booking_days']) }}"
                        min="0" max="365" step="1" required>
                    <span>days ahead</span>
                </div>
            </div>
        </section>

        <div class="booking-settings-actions">
            <button type="submit" class="btn btn--primary px-4">
                <i class="las la-save"></i> Save Settings
            </button>
        </div>
    </form>
@endsection

@push('style')
    <style>
        .booking-settings-form {
            width: min(100%, 650px);
        }

        .booking-setting-panel {
            margin-bottom: 18px;
            padding: 24px 22px 8px;
            background: #fff;
            border: 1px solid #dfe2e8;
            border-radius: 8px;
        }

        .booking-setting-heading {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 2px;
        }

        .booking-setting-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 34px;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            color: #df2278;
            background: #fbe0ec;
            font-size: 21px;
        }

        .booking-setting-heading h5 {
            margin: 0 0 2px;
            color: #252934;
            font-size: 16px;
            font-weight: 600;
        }

        .booking-setting-heading p {
            margin: 0;
            color: #747985;
            font-size: 13px;
        }

        .booking-setting-row {
            display: grid;
            grid-template-columns: minmax(150px, 1fr) 228px;
            align-items: center;
            min-height: 72px;
            border-bottom: 1px solid #eceef2;
        }

        .booking-setting-row:last-child {
            border-bottom: 0;
        }

        .booking-setting-row label {
            margin: 0;
            color: #4d5260;
            font-size: 15px;
        }

        .booking-setting-control {
            display: grid;
            grid-template-columns: 90px 1fr;
            align-items: center;
            gap: 12px;
        }

        .booking-setting-control input {
            width: 90px;
            height: 48px;
            padding: 8px 10px;
            color: #20232b;
            background: #f5f5f7;
            border: 1px solid #d2d5dc;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
        }

        .booking-setting-control input:focus {
            border-color: #df2278;
            box-shadow: 0 0 0 3px rgba(223, 34, 120, .12);
            outline: 0;
        }

        .booking-setting-control span {
            color: #717682;
            font-size: 13px;
            line-height: 1.35;
        }

        .booking-settings-actions {
            display: flex;
            justify-content: flex-end;
        }

        @media (max-width: 575px) {
            .booking-setting-panel {
                padding: 20px 16px 6px;
            }

            .booking-setting-row {
                grid-template-columns: 1fr;
                gap: 8px;
                padding: 16px 0;
            }

            .booking-setting-control {
                grid-template-columns: 90px 1fr;
            }
        }
    </style>
@endpush
