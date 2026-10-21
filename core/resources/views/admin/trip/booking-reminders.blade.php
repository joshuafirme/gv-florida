@extends('admin.layouts.app')

@section('panel')
    <div class="booking-reminders-page">
        <div class="reminder-page-intro">
            <h4>@lang('Booking Reminders')</h4>
            <p>@lang('Manage the important reminders shown on online booking when a visitor first selects Find Tickets.')</p>
        </div>

        <form action="{{ route('admin.trip.booking-reminders.store') }}" method="POST" class="reminder-panel reminder-create-panel">
            @csrf
            <div class="reminder-panel-label">@lang('New reminder')</div>
            <div class="reminder-icon-picker" role="radiogroup" aria-label="@lang('Reminder icon')">
                @foreach ($icons as $iconKey => $iconClass)
                    <label class="reminder-icon-option" title="{{ __(ucfirst($iconKey)) }}">
                        <input type="radio" name="icon" value="{{ $iconKey }}"
                            {{ old('icon', 'info') === $iconKey ? 'checked' : '' }}>
                        <span><i class="{{ $iconClass }}"></i></span>
                    </label>
                @endforeach
            </div>
            <textarea name="content" class="form-control reminder-content-input" rows="4" required
                placeholder="@lang('Reminder text... (leave a blank line between paragraphs)')">{{ old('content') }}</textarea>
            <div class="reminder-create-actions">
                <button type="submit" class="btn btn--primary reminder-primary-btn">
                    <i class="las la-plus"></i> @lang('Add Reminder')
                </button>
            </div>
        </form>

        <section class="reminder-panel reminder-list-panel">
            <header class="reminder-list-header">
                <strong>{{ trans_choice(':count reminder|:count reminders', $reminders->count(), ['count' => $reminders->count()]) }}</strong>
                <span>@lang('Only active reminders appear online. Order matches the display order.')</span>
            </header>

            @forelse ($reminders as $index => $reminder)
                @php
                    $paragraphs = preg_split('/\R\s*\R/', trim($reminder->content)) ?: [];
                @endphp
                <article class="reminder-list-item {{ $reminder->status ? '' : 'is-inactive' }}">
                    <div class="reminder-item-icon">
                        <i class="{{ $icons[$reminder->icon] ?? $icons['info'] }}"></i>
                    </div>
                    <div class="reminder-item-copy">
                        @foreach ($paragraphs as $paragraph)
                            <p>{!! nl2br(e(trim($paragraph))) !!}</p>
                        @endforeach
                        @unless ($reminder->status)
                            <span class="reminder-inactive-label">@lang('Inactive')</span>
                        @endunless
                    </div>
                    <div class="reminder-item-actions">
                        <form action="{{ route('admin.trip.booking-reminders.move', $reminder) }}" method="POST">
                            @csrf
                            <input type="hidden" name="direction" value="up">
                            <button class="reminder-icon-btn" title="@lang('Move up')" aria-label="@lang('Move up')"
                                {{ $loop->first ? 'disabled' : '' }}><i class="las la-angle-up"></i></button>
                        </form>
                        <form action="{{ route('admin.trip.booking-reminders.move', $reminder) }}" method="POST">
                            @csrf
                            <input type="hidden" name="direction" value="down">
                            <button class="reminder-icon-btn" title="@lang('Move down')" aria-label="@lang('Move down')"
                                {{ $loop->last ? 'disabled' : '' }}><i class="las la-angle-down"></i></button>
                        </form>
                        <form action="{{ route('admin.trip.booking-reminders.status', $reminder) }}" method="POST">
                            @csrf
                            <button class="reminder-icon-btn reminder-status-btn" title="{{ $reminder->status ? __('Deactivate') : __('Activate') }}"
                                aria-label="{{ $reminder->status ? __('Deactivate') : __('Activate') }}">
                                <i class="las {{ $reminder->status ? 'la-eye' : 'la-eye-slash' }}"></i>
                            </button>
                        </form>
                        <button type="button" class="reminder-icon-btn edit-reminder-btn" data-id="{{ $reminder->id }}"
                            title="@lang('Edit')" aria-label="@lang('Edit')"><i class="las la-pen"></i></button>
                        <button type="button" class="reminder-icon-btn text-danger confirmationBtn"
                            data-action="{{ route('admin.trip.booking-reminders.destroy', $reminder) }}"
                            data-question="@lang('Delete this booking reminder?')" title="@lang('Delete')" aria-label="@lang('Delete')">
                            <i class="las la-trash"></i>
                        </button>
                    </div>
                </article>
            @empty
                <div class="reminder-empty-state">
                    <i class="las la-bell-slash"></i>
                    <span>@lang('No booking reminders have been added yet.')</span>
                </div>
            @endforelse
        </section>

        <div class="reminder-page-intro reminder-codes-intro">
            <h5>@lang('Pet-Friendly Trip Codes')</h5>
            <p>@lang('These active place and trip codes are shown below the reminders in the passenger modal.')</p>
        </div>

        <section class="reminder-panel trip-code-panel">
            <form action="{{ route('admin.trip.booking-reminders.trip-codes.store') }}" method="POST" class="trip-code-create-form">
                @csrf
                <div class="reminder-panel-label">@lang('New trip code')</div>
                <div class="trip-code-fields">
                    <div>
                        <label for="tripCodePlace">@lang('Place')</label>
                        <input id="tripCodePlace" type="text" name="place" class="form-control" maxlength="120"
                            value="{{ old('place') }}" placeholder="@lang('e.g. LAGONOY')" required>
                    </div>
                    <div>
                        <label for="tripCodeValue">@lang('Code')</label>
                        <input id="tripCodeValue" type="text" name="code" class="form-control" maxlength="60"
                            value="{{ old('code') }}" placeholder="@lang('e.g. Z9 or E/L/R/T')" required>
                    </div>
                    <button type="submit" class="btn btn--primary reminder-primary-btn">
                        <i class="las la-plus"></i> @lang('Add Code')
                    </button>
                </div>
            </form>

            <div class="trip-code-list">
                @forelse ($tripCodes as $tripCode)
                    <article class="trip-code-item {{ $tripCode->status ? '' : 'is-inactive' }}">
                        <span class="trip-code-bus"><i class="las la-bus"></i></span>
                        <div class="trip-code-copy">
                            <strong>{{ $tripCode->place }}</strong>
                            <span>{{ $tripCode->code }}</span>
                        </div>
                        @unless ($tripCode->status)
                            <span class="reminder-inactive-label">@lang('Inactive')</span>
                        @endunless
                        <div class="reminder-item-actions">
                            <form action="{{ route('admin.trip.booking-reminders.trip-codes.move', $tripCode) }}" method="POST">
                                @csrf
                                <input type="hidden" name="direction" value="up">
                                <button class="reminder-icon-btn" title="@lang('Move up')" {{ $loop->first ? 'disabled' : '' }}>
                                    <i class="las la-angle-up"></i>
                                </button>
                            </form>
                            <form action="{{ route('admin.trip.booking-reminders.trip-codes.move', $tripCode) }}" method="POST">
                                @csrf
                                <input type="hidden" name="direction" value="down">
                                <button class="reminder-icon-btn" title="@lang('Move down')" {{ $loop->last ? 'disabled' : '' }}>
                                    <i class="las la-angle-down"></i>
                                </button>
                            </form>
                            <form action="{{ route('admin.trip.booking-reminders.trip-codes.status', $tripCode) }}" method="POST">
                                @csrf
                                <button class="reminder-icon-btn reminder-status-btn" title="{{ $tripCode->status ? __('Deactivate') : __('Activate') }}">
                                    <i class="las {{ $tripCode->status ? 'la-eye' : 'la-eye-slash' }}"></i>
                                </button>
                            </form>
                            <button type="button" class="reminder-icon-btn edit-trip-code-btn" data-id="{{ $tripCode->id }}" title="@lang('Edit')">
                                <i class="las la-pen"></i>
                            </button>
                            <button type="button" class="reminder-icon-btn text-danger confirmationBtn"
                                data-action="{{ route('admin.trip.booking-reminders.trip-codes.destroy', $tripCode) }}"
                                data-question="@lang('Delete this pet-friendly trip code?')" title="@lang('Delete')">
                                <i class="las la-trash"></i>
                            </button>
                        </div>
                    </article>
                @empty
                    <div class="reminder-empty-state reminder-empty-state--compact">
                        <span>@lang('No pet-friendly trip codes have been added.')</span>
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    <div class="modal fade" id="editReminderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content reminder-edit-modal">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Edit Booking Reminder')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="@lang('Close')"></button>
                </div>
                <form method="POST" id="editReminderForm">
                    @csrf
                    <div class="modal-body">
                        <label class="form-label">@lang('Icon')</label>
                        <div class="reminder-icon-picker reminder-edit-icons">
                            @foreach ($icons as $iconKey => $iconClass)
                                <label class="reminder-icon-option" title="{{ __(ucfirst($iconKey)) }}">
                                    <input type="radio" name="icon" value="{{ $iconKey }}">
                                    <span><i class="{{ $iconClass }}"></i></span>
                                </label>
                            @endforeach
                        </div>
                        <label for="editReminderContent" class="form-label mt-3">@lang('Reminder text')</label>
                        <textarea id="editReminderContent" name="content" class="form-control reminder-content-input" rows="7" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('Cancel')</button>
                        <button type="submit" class="btn btn--primary reminder-primary-btn">@lang('Save Changes')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editTripCodeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content reminder-edit-modal">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Edit Pet-Friendly Trip Code')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="@lang('Close')"></button>
                </div>
                <form method="POST" id="editTripCodeForm">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="editTripCodePlace">@lang('Place')</label>
                            <input id="editTripCodePlace" type="text" name="place" class="form-control" maxlength="120" required>
                        </div>
                        <div class="form-group mb-0">
                            <label for="editTripCodeValue">@lang('Code')</label>
                            <input id="editTripCodeValue" type="text" name="code" class="form-control" maxlength="60" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('Cancel')</button>
                        <button type="submit" class="btn btn--primary reminder-primary-btn">@lang('Save Changes')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-confirmation-modal />
@endsection

@push('style')
    <style>
        .booking-reminders-page {
            color: #24262c;
        }

        .reminder-page-intro {
            margin-bottom: 18px;
        }

        .reminder-page-intro h4,
        .reminder-page-intro h5 {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .reminder-page-intro p {
            color: #747984;
            font-size: 13px;
            margin: 0;
        }

        .reminder-panel {
            background: #fff;
            border: 1px solid #dfe1e5;
            border-radius: 8px;
        }

        .reminder-create-panel {
            margin-bottom: 18px;
            padding: 20px;
        }

        .reminder-panel-label {
            color: #4c515b;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .reminder-icon-picker {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-bottom: 12px;
        }

        .reminder-icon-option {
            margin: 0;
        }

        .reminder-icon-option input {
            height: 1px;
            opacity: 0;
            position: absolute;
            width: 1px;
        }

        .reminder-icon-option span {
            align-items: center;
            border: 1px solid #d3d6dc;
            border-radius: 6px;
            color: #5f6570;
            cursor: pointer;
            display: inline-flex;
            font-size: 17px;
            height: 38px;
            justify-content: center;
            width: 38px;
        }

        .reminder-icon-option input:checked + span {
            background: #fff0f7;
            border-color: #e7277d;
            color: #d91c67;
        }

        .reminder-icon-option input:focus-visible + span {
            outline: 2px solid #e7277d;
            outline-offset: 2px;
        }

        .reminder-content-input {
            background: #f4f4f6;
            border-color: #d4d6db;
            border-radius: 7px;
            font-size: 13px;
            line-height: 1.55;
            min-height: 92px;
            padding: 13px;
            resize: vertical;
        }

        .reminder-create-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 12px;
        }

        .reminder-primary-btn {
            align-items: center;
            border-radius: 7px;
            display: inline-flex;
            font-size: 13px;
            gap: 6px;
            justify-content: center;
            min-height: 38px;
        }

        .reminder-list-panel {
            margin-bottom: 28px;
            overflow: hidden;
        }

        .reminder-list-header {
            align-items: center;
            border-bottom: 1px solid #e2e3e6;
            display: flex;
            justify-content: space-between;
            min-height: 45px;
            padding: 10px 18px;
        }

        .reminder-list-header strong {
            font-size: 13px;
        }

        .reminder-list-header span {
            color: #8a8f99;
            font-size: 11px;
        }

        .reminder-list-item,
        .trip-code-item {
            align-items: flex-start;
            display: grid;
            gap: 12px;
        }

        .reminder-list-item {
            grid-template-columns: 34px minmax(0, 1fr) auto;
            padding: 16px 18px;
        }

        .reminder-list-item + .reminder-list-item,
        .trip-code-item + .trip-code-item {
            border-top: 1px solid #e5e6e9;
        }

        .reminder-list-item.is-inactive,
        .trip-code-item.is-inactive {
            background: #fafafa;
            opacity: .68;
        }

        .reminder-item-icon,
        .trip-code-bus {
            align-items: center;
            background: #eef0f2;
            border-radius: 50%;
            color: #535964;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 16px;
            height: 34px;
            justify-content: center;
            width: 34px;
        }

        .reminder-item-copy {
            font-size: 13px;
            line-height: 1.55;
            min-width: 0;
        }

        .reminder-item-copy p {
            margin: 0 0 12px;
        }

        .reminder-item-copy p:last-child {
            margin-bottom: 0;
        }

        .reminder-item-actions {
            align-items: center;
            display: flex;
            gap: 5px;
        }

        .reminder-item-actions form {
            margin: 0;
        }

        .reminder-icon-btn {
            align-items: center;
            background: transparent;
            border: 0;
            color: #727782;
            display: inline-flex;
            font-size: 15px;
            height: 30px;
            justify-content: center;
            padding: 0;
            width: 30px;
        }

        .reminder-icon-btn:hover,
        .reminder-icon-btn:focus {
            color: #d91c67;
        }

        .reminder-icon-btn:disabled {
            color: #d1d3d8;
            cursor: not-allowed;
        }

        .reminder-status-btn {
            color: #22a55a;
        }

        .reminder-inactive-label {
            background: #eceef1;
            border-radius: 4px;
            color: #666c77;
            display: inline-block;
            font-size: 10px;
            font-weight: 600;
            margin-top: 7px;
            padding: 2px 6px;
            text-transform: uppercase;
        }

        .reminder-empty-state {
            align-items: center;
            color: #9297a0;
            display: flex;
            flex-direction: column;
            gap: 8px;
            justify-content: center;
            min-height: 150px;
            padding: 24px;
        }

        .reminder-empty-state i {
            font-size: 28px;
        }

        .reminder-codes-intro {
            margin-bottom: 10px;
        }

        .trip-code-panel {
            overflow: hidden;
        }

        .trip-code-create-form {
            border-bottom: 1px solid #e2e3e6;
            padding: 18px;
        }

        .trip-code-fields {
            align-items: end;
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(220px, 1fr) minmax(160px, 240px) auto;
        }

        .trip-code-fields label {
            color: #686e78;
            display: block;
            font-size: 11px;
            margin-bottom: 5px;
        }

        .trip-code-fields .form-control {
            background: #f4f4f6;
            border-color: #d4d6db;
            border-radius: 7px;
            height: 40px;
        }

        .trip-code-item {
            align-items: center;
            grid-template-columns: 34px minmax(0, 1fr) auto auto;
            padding: 13px 18px;
        }

        .trip-code-copy {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .trip-code-copy strong {
            font-size: 13px;
        }

        .trip-code-copy span {
            color: #787e88;
            font-size: 12px;
        }

        .reminder-empty-state--compact {
            min-height: 90px;
        }

        .reminder-edit-modal {
            border: 0;
            border-radius: 8px;
        }

        @media (max-width: 767.98px) {
            .reminder-list-header {
                align-items: flex-start;
                flex-direction: column;
                gap: 3px;
            }

            .reminder-list-item {
                grid-template-columns: 34px minmax(0, 1fr);
            }

            .reminder-item-actions {
                grid-column: 1 / -1;
                justify-content: flex-end;
            }

            .trip-code-fields {
                grid-template-columns: 1fr;
            }

            .trip-code-fields .reminder-primary-btn {
                width: 100%;
            }

            .trip-code-item {
                grid-template-columns: 34px minmax(0, 1fr) auto;
            }

            .trip-code-item .reminder-item-actions {
                grid-column: 1 / -1;
            }
        }
    </style>
@endpush

@push('script')
    <script>
        (function($) {
            'use strict';

            const reminders = @json($reminders->keyBy('id'));
            const tripCodes = @json($tripCodes->keyBy('id'));
            const reminderUpdateUrl = @json(route('admin.trip.booking-reminders.update', '__ID__'));
            const tripCodeUpdateUrl = @json(route('admin.trip.booking-reminders.trip-codes.update', '__ID__'));
            const reminderModal = new bootstrap.Modal(document.getElementById('editReminderModal'));
            const tripCodeModal = new bootstrap.Modal(document.getElementById('editTripCodeModal'));

            $('.edit-reminder-btn').on('click', function() {
                const reminder = reminders[String($(this).data('id'))];
                if (!reminder) return;

                $('#editReminderForm').attr('action', reminderUpdateUrl.replace('__ID__', reminder.id));
                $('#editReminderContent').val(reminder.content);
                $(`#editReminderForm input[name="icon"][value="${reminder.icon}"]`).prop('checked', true);
                reminderModal.show();
            });

            $('.edit-trip-code-btn').on('click', function() {
                const tripCode = tripCodes[String($(this).data('id'))];
                if (!tripCode) return;

                $('#editTripCodeForm').attr('action', tripCodeUpdateUrl.replace('__ID__', tripCode.id));
                $('#editTripCodePlace').val(tripCode.place);
                $('#editTripCodeValue').val(tripCode.code);
                tripCodeModal.show();
            });
        })(jQuery);
    </script>
@endpush
