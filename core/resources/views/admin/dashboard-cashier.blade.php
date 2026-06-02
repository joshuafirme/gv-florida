@extends('admin.layouts.app')

@section('panel')
    <div class="row gy-4">
        <div class="col-xxl-6 col-sm-6">
            {{-- <x-widget style="6" link="{{ url('admin/deposit/approved') }}" title="Today's Processed Payments"
                icon="fas fa-hand-holding-usd" value="{{ showAmount($cashierWidget['today_processed_amount']) }}"
                bg="success" outline="true" /> --}}
            <x-widget style="2" overlay_icon="0" cover_cursor="1"
                title="Today's Processed Payments" icon="fas fa-hand-holding-usd" value="{{ showAmount($cashierWidget['today_processed_amount']) }}" color="success" />
        </div>
        <div class="col-xxl-6 col-sm-6">
            <x-widget style="3" overlay_icon="0" cover_cursor="1" link="{{ url('admin/deposit/approved') }}"
                title="Today's Processed Transactions" icon="fas fa-receipt"
                value="{{ $cashierWidget['today_processed_count'] }}" bg="primary" outline="true" />
        </div>
    </div>

    <div class="row gy-4 mt-1">
        <div class="col-xl-12">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">@lang('Latest Booking History')</h5>

                    @if ($soldTickets->count())
                        <div class="table-responsive--sm table-responsive">
                            <table class="table table--light style--two">
                                <thead>
                                    <tr>
                                        <th>@lang('User')</th>
                                        <th>@lang('PNR Number')</th>
                                        <th>@lang('Ticket Count')</th>
                                        <th>@lang('Amount')</th>
                                        <th>@lang('Action')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($soldTickets as $item)
                                        <tr>
                                            <td data-label="@lang('User')">
                                                @if ($item->user)
                                                    <span class="font-weight-bold">{{ __($item->user->fullname) }}</span>
                                                    <br>
                                                    <span class="small">
                                                        <a
                                                            href="{{ route('admin.users.detail', $item->user_id) }}"><span>@</span>{{ $item->user->username }}</a>
                                                    </span>
                                                @else
                                                    {{ $item->kiosk->name }}
                                                    <div>{{ $item->kiosk->uid }}</div>
                                                @endif
                                            </td>
                                            <td data-label="@lang('PNR Number')">
                                                <strong>{{ __($item->pnr_number) }}</strong>
                                            </td>
                                            <td data-label="@lang('Ticket Count')">
                                                <strong>{{ $item->seats ? __(sizeof($item->seats)) : '' }}</strong>
                                            </td>
                                            <td data-label="@lang('Amount')">
                                                {{ showAmount($item->deposit->final_amount - $item->deposit?->userDiscount?->amount ?: 0) }}
                                            </td>
                                            <td data-label="@lang('Action')">
                                                <a href="{{ route('admin.vehicle.ticket.booked') }}" class="icon-btn ml-1 "
                                                    data-toggle="tooltip" title=""
                                                    data-original-title="@lang('Detail')">
                                                    <i class="la la-desktop"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table><!-- table end -->
                        </div>
                    @else
                        <div class="empty-list text-center h-100">
                            <img src="{{ getImage('assets/images/empty_list.png') }}" alt="empty">
                            <h5 class="text-muted">@lang('No booking records available')</h5>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .empty-list {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 30px 0;
        }

        .empty-list img {
            width: 150px;
            margin-bottom: 15px;
        }
    </style>
@endpush
