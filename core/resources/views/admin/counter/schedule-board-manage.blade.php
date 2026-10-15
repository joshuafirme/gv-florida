@extends('admin.layouts.app')

@section('panel')
    <div class="schedule-board-admin">
        <div class="board-admin-heading">
            <div>
                <p>{{ $counter->name }}@if($counter->city) · {{ $counter->city }}@endif</p>
            </div>
            <a href="{{ route('admin.counter.scheduleBoard', $counter->id) }}" target="_blank" class="btn btn-outline--primary">
                <i class="las la-external-link-alt"></i> @lang('Open Board')
            </a>
        </div>

        <section class="board-admin-panel">
            <div class="board-panel-heading">
                <div>
                    <h5><i class="las la-sliders-h"></i> @lang('Board Settings')</h5>
                    <p>@lang('Choose which departures appear and when completed departures leave the board.')</p>
                </div>
            </div>
            <form action="{{ route('admin.counter.schedule-board.settings', $counter) }}" method="POST">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-lg-6">
                        <label class="form-label">@lang('Trip Display Mode')</label>
                        <select name="show_all_trips" class="form-control" required>
                            <option value="1" @selected($setting->show_all_trips)>@lang('Show all active trips from this counter')</option>
                            <option value="0" @selected(!$setting->show_all_trips)>@lang('Show only selected trips')</option>
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">@lang('Remove After Departure')</label>
                        <div class="input-group">
                            <input type="number" name="departure_grace_minutes" class="form-control" min="0" max="1440"
                                value="{{ old('departure_grace_minutes', $setting->departure_grace_minutes) }}" required>
                            <span class="input-group-text">@lang('minutes')</span>
                        </div>
                    </div>
                    <div class="col-lg-2">
                        <button class="btn btn--primary w-100 h-45"><i class="las la-save"></i> @lang('Save')</button>
                    </div>
                </div>
            </form>
        </section>

        <section class="board-admin-panel">
            <div class="board-panel-heading">
                <div>
                    <h5><i class="las la-bus"></i> @lang('Displayed Trips')</h5>
                    <p>@lang('Select trips and arrange their display priority. This list is used when the display mode is set to selected trips.')</p>
                </div>
                <span class="board-count">{{ $selectedTrips->where('is_visible', true)->whereIn('trip_id', $trips->pluck('id'))->count() }} @lang('selected')</span>
            </div>
            <form action="{{ route('admin.counter.schedule-board.trips', $counter) }}" method="POST">
                @csrf
                <div id="boardTripList" class="board-trip-list">
                    @forelse($trips as $trip)
                        @php($selected = $selectedTrips->get($trip->id))
                        <div class="board-trip-row" data-selected="{{ $selected?->is_visible ? 1 : 0 }}" data-order="{{ $selected?->sort_order ?? 999999 }}">
                            <input type="hidden" name="trip_order[]" value="{{ $trip->id }}">
                            <span class="trip-drag" title="@lang('Use the arrows to reorder')"><i class="las la-grip-vertical"></i></span>
                            <label class="trip-select">
                                <input type="checkbox" name="trip_ids[]" value="{{ $trip->id }}" @checked($selected?->is_visible)>
                                <span></span>
                            </label>
                            <div class="trip-time">{{ $trip->schedule?->start_from ? showDateTime($trip->schedule->start_from, 'h:i A') : '--' }}</div>
                            <div class="trip-route">
                                <strong>{{ $trip->route?->endTo?->city ?: $trip->route?->endTo?->name ?: __('Destination unavailable') }}</strong>
                                <span>{{ $trip->fleetType?->name ?: __('Bus class unavailable') }}</span>
                            </div>
                            <div class="trip-order-actions">
                                <button type="button" class="icon-action js-trip-up" title="@lang('Move up')" aria-label="@lang('Move up')"><i class="las la-angle-up"></i></button>
                                <button type="button" class="icon-action js-trip-down" title="@lang('Move down')" aria-label="@lang('Move down')"><i class="las la-angle-down"></i></button>
                            </div>
                        </div>
                    @empty
                        <div class="board-empty">@lang('No active trips originate from this counter.')</div>
                    @endforelse
                </div>
                @if($trips->isNotEmpty())
                    <div class="board-panel-footer">
                        <button class="btn btn--primary"><i class="las la-save"></i> @lang('Save Trip Selection')</button>
                    </div>
                @endif
            </form>
        </section>

        <section class="board-admin-panel">
            <div class="board-panel-heading">
                <div>
                    <h5><i class="las la-image"></i> @lang('Advertisements')</h5>
                    <p>@lang('Upload board images and optionally control when each image is visible.')</p>
                </div>
            </div>
            <form action="{{ route('admin.counter.schedule-board.advertisements.store', $counter) }}" method="POST" enctype="multipart/form-data" class="board-create-form">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-xl-3 col-md-6"><label class="form-label">@lang('Title')</label><input name="title" class="form-control" maxlength="160" placeholder="@lang('Optional title')"></div>
                    <div class="col-xl-3 col-md-6"><label class="form-label">@lang('Image')</label><input type="file" name="image" class="form-control" accept="image/png,image/jpeg,image/webp" required></div>
                    <div class="col-xl-2 col-md-6"><label class="form-label">@lang('Starts At')</label><input type="datetime-local" name="starts_at" class="form-control"></div>
                    <div class="col-xl-2 col-md-6"><label class="form-label">@lang('Ends At')</label><input type="datetime-local" name="ends_at" class="form-control"></div>
                    <div class="col-xl-1 col-6"><label class="form-label">@lang('Order')</label><input type="number" name="sort_order" class="form-control" min="0" value="{{ ($advertisements->max('sort_order') ?? 0) + 10 }}"></div>
                    <div class="col-xl-1 col-6"><input type="hidden" name="status" value="1"><button class="btn btn--primary w-100 h-45" title="@lang('Add advertisement')"><i class="las la-plus"></i></button></div>
                </div>
            </form>

            <div class="board-content-list">
                @forelse($advertisements as $advertisement)
                    <div class="board-content-item board-ad-item">
                        <img src="{{ getImage('assets/images/schedule-board/' . $advertisement->image) }}" alt="{{ $advertisement->title ?: __('Advertisement') }}">
                        <form id="adUpdate{{ $advertisement->id }}" action="{{ route('admin.counter.schedule-board.advertisements.update', [$counter, $advertisement]) }}" method="POST" enctype="multipart/form-data" class="board-content-fields">
                            @csrf
                            <input name="title" class="form-control" maxlength="160" value="{{ $advertisement->title }}" placeholder="@lang('Advertisement title')">
                            <input type="file" name="image" class="form-control" accept="image/png,image/jpeg,image/webp" title="@lang('Replace image')">
                            <input type="datetime-local" name="starts_at" class="form-control" value="{{ $advertisement->starts_at?->format('Y-m-d\TH:i') }}" title="@lang('Starts at')">
                            <input type="datetime-local" name="ends_at" class="form-control" value="{{ $advertisement->ends_at?->format('Y-m-d\TH:i') }}" title="@lang('Ends at')">
                            <input type="number" name="sort_order" class="form-control content-order" min="0" value="{{ $advertisement->sort_order }}" title="@lang('Display order')">
                            <label class="status-switch"><input type="checkbox" name="status" value="1" @checked($advertisement->status)><span></span><small>@lang('Active')</small></label>
                        </form>
                        <div class="content-actions">
                            <button form="adUpdate{{ $advertisement->id }}" class="icon-action icon-action--save" title="@lang('Save advertisement')" aria-label="@lang('Save advertisement')"><i class="las la-save"></i></button>
                            <button type="button" class="icon-action icon-action--danger confirmationBtn"
                                data-action="{{ route('admin.counter.schedule-board.advertisements.destroy', [$counter, $advertisement]) }}"
                                data-question="@lang('Remove this advertisement?')" title="@lang('Remove advertisement')"
                                aria-label="@lang('Remove advertisement')"><i class="las la-trash"></i></button>
                        </div>
                    </div>
                @empty
                    <div class="board-empty">@lang('No advertisements have been added.')</div>
                @endforelse
            </div>
        </section>

        <section class="board-admin-panel">
            <div class="board-panel-heading">
                <div>
                    <h5><i class="las la-bullhorn"></i> @lang('Notices & Announcements')</h5>
                    <p>@lang('Publish operational information without changing the board application.')</p>
                </div>
            </div>
            <form action="{{ route('admin.counter.schedule-board.notices.store', $counter) }}" method="POST" class="board-create-form">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-xl-2 col-md-6"><label class="form-label">@lang('Title')</label><input name="title" class="form-control" maxlength="160" placeholder="@lang('Optional title')"></div>
                    <div class="col-xl-4"><label class="form-label">@lang('Announcement')</label><input name="content" class="form-control" maxlength="2000" required placeholder="@lang('Enter the notice shown to passengers')"></div>
                    <div class="col-xl-2 col-md-6"><label class="form-label">@lang('Starts At')</label><input type="datetime-local" name="starts_at" class="form-control"></div>
                    <div class="col-xl-2 col-md-6"><label class="form-label">@lang('Ends At')</label><input type="datetime-local" name="ends_at" class="form-control"></div>
                    <div class="col-xl-1 col-6"><label class="form-label">@lang('Order')</label><input type="number" name="sort_order" class="form-control" min="0" value="{{ ($notices->max('sort_order') ?? 0) + 10 }}"></div>
                    <div class="col-xl-1 col-6"><input type="hidden" name="status" value="1"><button class="btn btn--primary w-100 h-45" title="@lang('Add notice')"><i class="las la-plus"></i></button></div>
                </div>
            </form>

            <div class="board-content-list">
                @forelse($notices as $notice)
                    <div class="board-content-item board-notice-item">
                        <span class="notice-icon"><i class="las la-bullhorn"></i></span>
                        <form id="noticeUpdate{{ $notice->id }}" action="{{ route('admin.counter.schedule-board.notices.update', [$counter, $notice]) }}" method="POST" class="board-content-fields">
                            @csrf
                            <input name="title" class="form-control" maxlength="160" value="{{ $notice->title }}" placeholder="@lang('Notice title')">
                            <textarea name="content" class="form-control" maxlength="2000" rows="2" required>{{ $notice->content }}</textarea>
                            <input type="datetime-local" name="starts_at" class="form-control" value="{{ $notice->starts_at?->format('Y-m-d\TH:i') }}" title="@lang('Starts at')">
                            <input type="datetime-local" name="ends_at" class="form-control" value="{{ $notice->ends_at?->format('Y-m-d\TH:i') }}" title="@lang('Ends at')">
                            <input type="number" name="sort_order" class="form-control content-order" min="0" value="{{ $notice->sort_order }}" title="@lang('Display order')">
                            <label class="status-switch"><input type="checkbox" name="status" value="1" @checked($notice->status)><span></span><small>@lang('Active')</small></label>
                        </form>
                        <div class="content-actions">
                            <button form="noticeUpdate{{ $notice->id }}" class="icon-action icon-action--save" title="@lang('Save notice')" aria-label="@lang('Save notice')"><i class="las la-save"></i></button>
                            <button type="button" class="icon-action icon-action--danger confirmationBtn"
                                data-action="{{ route('admin.counter.schedule-board.notices.destroy', [$counter, $notice]) }}"
                                data-question="@lang('Remove this notice?')" title="@lang('Remove notice')"
                                aria-label="@lang('Remove notice')"><i class="las la-trash"></i></button>
                        </div>
                    </div>
                @empty
                    <div class="board-empty">@lang('No notices have been added.')</div>
                @endforelse
            </div>
        </section>
    </div>

    <x-confirmation-modal />
@endsection

@push('style')
    <style>
        .schedule-board-admin{--board-accent:#4634ff;display:grid;gap:18px}.board-admin-heading{align-items:center;display:flex;justify-content:space-between}.board-admin-heading h4{font-size:22px;margin:0 0 4px}.board-admin-heading p,.board-panel-heading p{color:#7b8494;font-size:13px;margin:0}.board-admin-panel{background:#fff;border:1px solid #e6e9ee;border-radius:8px;padding:20px}.board-panel-heading{align-items:flex-start;border-bottom:1px solid #edf0f4;display:flex;justify-content:space-between;margin:-2px 0 18px;padding-bottom:14px}.board-panel-heading h5{align-items:center;display:flex;font-size:16px;gap:7px;margin:0 0 4px}.board-panel-heading h5 i{color:var(--board-accent)}.board-count{background:#eef0ff;border-radius:20px;color:var(--board-accent);font-size:11px;font-weight:600;padding:6px 10px}.board-trip-list{border:1px solid #e7eaf0;border-radius:7px;overflow:hidden}.board-trip-row{align-items:center;border-bottom:1px solid #edf0f4;display:grid;gap:12px;grid-template-columns:24px 28px 100px minmax(0,1fr) 76px;min-height:64px;padding:8px 12px}.board-trip-row:last-child{border-bottom:0}.board-trip-row:has(input:checked){background:#f4f3ff}.trip-drag{color:#a6aeba;font-size:18px}.trip-select{margin:0}.trip-select input{position:absolute;opacity:0}.trip-select span{border:1px solid #cdd3dc;border-radius:4px;display:block;height:20px;width:20px}.trip-select input:checked+span{background:var(--board-accent);border-color:var(--board-accent);box-shadow:inset 0 0 0 4px #fff}.trip-time{color:#182235;font-size:14px;font-weight:700}.trip-route{display:flex;flex-direction:column}.trip-route strong{color:#242e3f;font-size:13px}.trip-route span{color:#858e9b;font-size:11px}.trip-order-actions,.content-actions{display:flex;gap:6px;justify-content:flex-end}.icon-action{align-items:center;background:#fff;border:1px solid #d8dde5;border-radius:6px;color:#626d7d;display:inline-flex;font-size:17px;height:32px;justify-content:center;padding:0;width:32px}.icon-action:hover{background:#f4f3ff;border-color:var(--board-accent);color:var(--board-accent)}.icon-action--save{border-color:var(--board-accent);color:var(--board-accent)}.icon-action--danger{border-color:#f2c7cb;color:#d83d49}.icon-action--danger:hover{background:#d83d49;border-color:#d83d49;color:#fff}.board-panel-footer{display:flex;justify-content:flex-end;padding-top:16px}.board-create-form{background:#f7f8fa;border:1px solid #e7eaf0;border-radius:7px;padding:15px}.board-content-list{display:grid;gap:10px;margin-top:14px}.board-content-item{align-items:center;border:1px solid #e7eaf0;border-radius:7px;display:grid;gap:12px;padding:10px}.board-ad-item{grid-template-columns:110px minmax(0,1fr) 74px}.board-ad-item>img{aspect-ratio:16/9;border-radius:5px;height:62px;object-fit:cover;width:110px}.board-notice-item{grid-template-columns:42px minmax(0,1fr) 74px}.notice-icon{align-items:center;background:#f4f3ff;border-radius:7px;color:var(--board-accent);display:flex;font-size:21px;height:42px;justify-content:center;width:42px}.board-content-fields{align-items:center;display:grid;gap:8px;grid-template-columns:1.2fr 1.5fr 1fr 1fr 80px 84px}.board-notice-item .board-content-fields{grid-template-columns:1fr 1.8fr 1fr 1fr 80px 84px}.board-content-fields .form-control{font-size:12px;min-height:38px}.status-switch{align-items:center;display:flex;gap:6px;margin:0}.status-switch input{position:absolute;opacity:0}.status-switch span{background:#cdd3dc;border-radius:20px;display:block;height:20px;position:relative;width:36px}.status-switch span:after{background:#fff;border-radius:50%;content:"";height:16px;left:2px;position:absolute;top:2px;transition:left .15s;width:16px}.status-switch input:checked+span{background:var(--board-accent)}.status-switch input:checked+span:after{left:18px}.status-switch small{color:#697384;font-size:11px}.board-empty{color:#8a93a0;padding:24px;text-align:center}
        @media(max-width:1199px){.board-content-fields,.board-notice-item .board-content-fields{grid-template-columns:repeat(2,minmax(0,1fr))}.content-order{max-width:none}}
        @media(max-width:767px){.board-admin-heading{align-items:flex-start;gap:12px}.board-admin-panel{padding:15px}.board-trip-row{grid-template-columns:20px 24px 78px minmax(0,1fr)}.trip-order-actions{display:none}.board-content-item,.board-ad-item,.board-notice-item{grid-template-columns:1fr}.board-ad-item>img{height:auto;width:100%}.content-actions{justify-content:flex-start}.board-content-fields,.board-notice-item .board-content-fields{grid-template-columns:1fr}.notice-icon{display:none}}
    </style>
@endpush

@push('script')
    <script>
        (function($) {
            'use strict';

            const tripList = $('#boardTripList');
            tripList.children('.board-trip-row').sort(function(a, b) {
                return Number(a.dataset.order) - Number(b.dataset.order);
            }).appendTo(tripList);

            $(document).on('click', '.js-trip-up, .js-trip-down', function() {
                const row = $(this).closest('.board-trip-row');
                if ($(this).hasClass('js-trip-up')) {
                    row.prev('.board-trip-row').before(row);
                } else {
                    row.next('.board-trip-row').after(row);
                }
            });

            if (window.bootstrap) {
                document.querySelectorAll('[title]').forEach(function(element) {
                    bootstrap.Tooltip.getOrCreateInstance(element);
                });
            }
        })(jQuery);
    </script>
@endpush
