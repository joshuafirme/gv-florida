@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two" id="datatable">
                            <thead>
                                <tr>
                                    <th>@lang('Name')</th>
                                    <th>@lang('Seat Layout')</th>
                                    <th>@lang('No of Deck')</th>
                                    <th>@lang('Total Seat')</th>
                                    <th>@lang('Facilities')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($fleetType as $item)
                                    <tr>
                                        <td>{{ __($item->name) }}</td>
                                        <td>{{ __($item->seat_layout) }}</td>
                                        <td>{{ __($item->deck) }}</td>
                                        <td>{{ array_sum($item->deck_seats) }}</td>
                                        <td>
                                            @if ($item->facilities)
                                                {{ __(implode(',', $item->facilities)) }}
                                            @else
                                                @lang('No facilities')
                                            @endif
                                        </td>
                                        <td>@php echo $item->statusBadge; @endphp</td>

                                        <td>
                                            <div class="button--group">
                                                {{-- <a class="btn btn-sm btn-outline--primary" target="_blank"
                                                    href="{{ route('admin.fleet.type.seatLayoutDetails', $item->id) }}">
                                                    <i class="la la-eye"></i>@lang('Layout Preview')
                                                </a> --}}
                                                <button type="button" class="btn btn-sm btn-outline--primary cuModalBtn"
                                                    data-resource="{{ $item }}"
                                                    data-modal_title="@lang('Edit Type')">
                                                    <i class="la la-pencil"></i>@lang('Edit')
                                                </button>

                                                @if (!$item->status)
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline--success confirmationBtn"
                                                        data-action="{{ route('admin.fleet.type.status', $item->id) }}"
                                                        data-question="@lang('Are you sure to enable this type?')">
                                                        <i class="la la-eye"></i>@lang('Enable')
                                                    </button>
                                                @else
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline--danger  confirmationBtn"
                                                        data-action="{{ route('admin.fleet.type.status', $item->id) }}"
                                                        data-question="@lang('Are you sure to disable this type?')">
                                                        <i class="la la-eye-slash"></i>@lang('Disable')
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($fleetType->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($fleetType) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <x-confirmation-modal />

    <div id="cuModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form id="postForm" action="{{ route('admin.fleet.type.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <input type="hidden" id="fleet_id">
                                <div class="form-group">
                                    <label> @lang('Name')</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="form-group">
                                    <label> @lang('Seat Layout')</label>
                                    <select name="seat_layout" class="form-control select2"
                                        data-minimum-results-for-search="-1">
                                        <option value="">@lang('Select an option')</option>
                                        @foreach ($seatLayouts as $item)
                                            <option value="{{ $item->layout }}">{{ __($item->layout) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="container-floating-label mb-2 mt-3">
                                    <div class="border-container position-relative">
                                        <label class="floating-label">Comfort Room</label>
                                        <div class="content p-3">

                                            <div class="form-group">
                                                <label>Position</label>
                                                <select name="cr_position" class="form-control select2">
                                                    <option value="">N/A</option>
                                                    <option value="Left">Left</option>
                                                    <option value="Center">Center</option>
                                                    <option value="Right">Right</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Row Insert</label>
                                                <input type="number" class="form-control" placeholder="@lang('Enter Number of Row (Where to insert)')"
                                                    name="cr_row">
                                            </div>
                                            <div class="form-group">
                                                <label>Row Covered</label>
                                                <input type="number" class="form-control" min="1" max="3"
                                                    name="cr_row_covered">
                                            </div>
                                            <div class="form-group">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="cr_override_seat">
                                                    <label class="form-check-label">
                                                        Override Seat
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="container-floating-label mb-2 mt-3">
                                    <div class="border-container position-relative">
                                        <label class="floating-label">Deck</label>
                                        <div class="content p-3">
                                            <div class="form-group">
                                                <label> @lang('No of Deck')</label>
                                                <input type="number" min="0" class="form-control" name="deck"
                                                    required>
                                            </div>
                                            <div class="showSeat"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="facilities">@lang('Facilities')</label>
                                    <select class="select2-auto-tokenize" name="facilities[]" id="facilities"
                                        multiple="multiple">
                                        @foreach ($facilities as $item)
                                            <option value="{{ $item->data_values->title }}">
                                                {{ $item->data_values->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group" id="non-operational-seats">
                                </div>

                                <div class="form-group">
                                    <label for="inputName">@lang('AC status')</label>
                                    <input type="checkbox" data-width="100%" data-height="40px" data-onstyle="-success"
                                        data-offstyle="-danger" data-bs-toggle="toggle" data-on="@lang('YES')"
                                        data-off="@lang('NO')" name="has_ac">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div id="seat-layout-container"></div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn--primary h-45 w-100">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <button type="button" class="btn btn-sm btn-outline--primary cuModalBtn" data-modal_title="@lang('Add New layout')">
        <i class="las la-plus"></i> @lang('Add New')
    </button>
@endpush

@push('script-lib')
    <script src="{{ asset('assets/admin/js/cu-modal.js?v=' . buildVer()) }}"></script>
@endpush

@push('script')
    <script>
        (function($) {

            "use strict";

            class BusLayout {
                constructor(fleet) {
                    this.fleet = fleet;
                    this.sitLayoutsData = this.sitLayouts();
                    this.totalRow = 0;
                }

                /**
                 * Parses seat layouts for both 2-column (e.g., "2x2")
                 * and 3-column (e.g., "2x1x2") configurations.
                 * @returns {object} An object with left, center, and right seat counts.
                 */
                sitLayouts() {
                    const seatLayout = this.fleet.seat_layout.replace(/ /g, '').split('x').map(Number);
                    const layout = {
                        left: 0,
                        center: 0,
                        right: 0
                    };

                    layout.left = seatLayout[0] || 0;

                    if (seatLayout.length === 2) {
                        // Handles 2-column layout (e.g., "2x2")
                        layout.right = seatLayout[1] || 0;
                    } else if (seatLayout.length === 3) {
                        // Handles 3-column layout (e.g., "2x1x2")
                        layout.center = seatLayout[1] || 0;
                        layout.right = seatLayout[2] || 0;
                    }

                    return layout;
                }

                /**
                 * Generates the HTML for the header of a deck (Front, Rear, Driver, etc.).
                 * @param {number} deckNumber - The index of the deck (0 for the first).
                 * @returns {string} The HTML string for the deck header.
                 */
                getDeckHeader(deckNumber) {
                    let html = `
                    <span class="front">Front</span>
                    <span class="rear">Rear</span>
                `;
                    if (deckNumber === 0) {
                        html += `
                        <span class="driver"><img src="{{ url('assets/templates/basic/images/icon/wheel.svg') }}" alt="wheel"></span>
                        <span class="lower">Door</span>
                    `;
                    } else {
                        html += `<span class="driver">Deck: ${deckNumber + 1}</span>`;
                    }
                    return html;
                }

                /**
                 * Generates the HTML for a single seat.
                 * @param {string} label - The label for the seat (e.g., "A1", "B12").
                 * @param {number} deckIndex - The 1-based index of the deck.
                 * @returns {string} The HTML string for a single seat.
                 */
                generateSeats(label, deckIndex) {
                    const isDisabled = label.includes('<del>');
                    const cleanLabel = label.replace(/<\/?del>/g, '');
                    const disabledClass = isDisabled ? 'disabled-seat' : '';

                    return `<div>
                            <span class='seat ${disabledClass}' data-seat='${deckIndex}-${cleanLabel}'>
                                ${label}
                            </span>
                        </div>`;
                }

                /**
                 * Calculates the total number of full rows.
                 * @param {number} seatCount - Total number of seats on the deck.
                 * @returns {number} The total number of full rows.
                 */
                getTotalRow(seatCount) {
                    const rowItem = this.sitLayoutsData.left + this.sitLayoutsData.center + this.sitLayoutsData
                        .right;
                    if (rowItem === 0) return 0; // Prevent division by zero
                    this.totalRow = Math.floor(seatCount / rowItem);
                    return this.totalRow;
                }

                /**
                 * Calculates the number of seats in the last, possibly incomplete, row.
                 * @param {number} seatCount - Total number of seats on the deck.
                 * @returns {number} The number of seats in the last row.
                 */
                getLastRowSit(seatCount) {
                    const rowItem = this.sitLayoutsData.left + this.sitLayoutsData.center + this.sitLayoutsData
                        .right;
                    if (rowItem === 0) return seatCount;
                    return seatCount - this.getTotalRow(seatCount) * rowItem;
                }
            }


            /**
             * Main function to render the entire bus layout.
             * This function takes a fleet configuration and renders it into a container.
             * @param {object} fleetType - The configuration object for the bus fleet.
             * @param {string} containerId - The ID of the HTML element to render the layout in.
             */
            function renderBusLayout(fleetType, containerId) {
                const $container = $(`#${containerId}`);
                $container.empty(); // Clear previous layout
                console.log(fleetType)
                const busLayout = new BusLayout(fleetType);
                const disabled_seats = fleetType.disabled_seats || [];

                fleetType.deck_seats.forEach((seatCount, key) => {
                    const deckHtml = $('<div>', {
                        class: 'seat-plan-inner'
                    }).append(
                        $('<h4>', {
                            class: 'text-xl font-bold mb-4 text-gray-700',
                            text: `${fleetType.name} - Deck ${key + 1}`
                        }),
                        $('<div>', {
                            class: 'single'
                        })
                    );
                    const $singleDeck = deckHtml.find('.single');

                    // Add Deck Header
                    $singleDeck.append(busLayout.getDeckHeader(key));

                    const totalRow = busLayout.getTotalRow(seatCount);
                    const lastRowSeat = busLayout.getLastRowSit(seatCount);
                    const deckIndex = key + 1;
                    const seatlayout = busLayout.sitLayoutsData;
                    let seatCounter = 1;
                    const prefix = fleetType.prefixes ? fleetType.prefixes[key] : '';
                    let has_cr = false;
                    let cr_row_covered = fleetType.cr_row_covered ? fleetType.cr_row_covered - 1 : 0;

                    let position = 'absolute';
                    let offset = '25px';

                    let seatColumns = busLayout.sitLayouts().left + busLayout.sitLayouts().right + busLayout.sitLayouts().center;
                    console.log('seatColumns', seatColumns)
                    if (seatColumns == 3) {
                        position = 'relative';
                        offset = 0
                    }

                    // Main Rows
                    for (let row = 1; row <= totalRow; row++) {
                        const $seatWrapper = $('<div>', {
                            class: 'seat-wrapper'
                        });

                        // --- Left Side ---
                        const $leftSide = $('<div>', {
                            class: 'left-side'
                        });
                        for (let ls = 1; ls <= seatlayout.left; ls++) {
                            const isCrSpot = (row === fleetType.cr_row || row === fleetType.cr_row +
                                cr_row_covered) && fleetType.cr_position === 'Left';
                            if (isCrSpot) {
                                if (!has_cr) {
                                    let cr_height = getCRHeight(fleetType.cr_row_covered);
                                    const cr_width = (seatlayout.left >= 2) ? '70px' : '30px';
                                    const $crSpan = $('<span>', {
                                        class: 'seat comfort-room cr-left',
                                        text: 'CR'
                                    }).css({
                                        height: cr_height,
                                        lineHeight: cr_height,
                                        width: cr_width,
                                        position: position,
                                        right: offset
                                    });
                                    $leftSide.append($('<div>').append($crSpan));
                                    has_cr = true;
                                }
                                if (!fleetType.cr_override_seat) seatCounter--;
                            } else {
                                let offset = seatCount - (fleetType.last_row ? fleetType.last_row[key] : 0);
                                if (fleetType.last_row && seatCounter > offset) continue;
                                let label = `${prefix}${seatCounter}`;
                                if (disabled_seats.includes(label)) label = `<del>${label}</del>`;
                                $leftSide.append(busLayout.generateSeats(label, deckIndex));
                            }
                            seatCounter++;
                        }
                        $seatWrapper.append($leftSide);

                        // --- Center Side ---
                        const $centerSide = $('<div>', {
                            class: 'center-side'
                        });
                        for (let cs = 1; cs <= seatlayout.center; cs++) {
                            const isCrSpot = (row === fleetType.cr_row || row === fleetType.cr_row +
                                cr_row_covered) && fleetType.cr_position === 'Center';
                            if (isCrSpot) {
                                if (!has_cr) {
                                    let cr_height = getCRHeight(fleetType.cr_row_covered);
                                    const cr_width = (seatlayout.center >= 2) ? '70px' : '30px';
                                    const $crSpan = $('<span>', {
                                        class: 'seat comfort-room cr-center',
                                        text: 'CR'
                                    }).css({
                                        height: cr_height,
                                        lineHeight: cr_height,
                                        width: cr_width
                                    });
                                    $centerSide.append($('<div>').append($crSpan));
                                    has_cr = true;
                                }
                                if (!fleetType.cr_override_seat) seatCounter--;
                            } else {
                                let offset = seatCount - (fleetType.last_row ? fleetType.last_row[key] : 0);
                                if (fleetType.last_row && seatCounter > offset) continue;
                                let label = `${prefix}${seatCounter}`;
                                if (disabled_seats.includes(label)) label = `<del>${label}</del>`;
                                $centerSide.append(busLayout.generateSeats(label, deckIndex));
                            }
                            seatCounter++;
                        }
                        $seatWrapper.append($centerSide);

                        // --- Right Side ---
                        const $rightSide = $('<div>', {
                            class: 'right-side'
                        });
                        for (let rs = 1; rs <= seatlayout.right; rs++) {
                            const isCrSpot = (row === fleetType.cr_row || row === fleetType.cr_row +
                                cr_row_covered) && fleetType.cr_position === 'Right' && key == 0;
                            if (isCrSpot) {
                                if (!has_cr) {
                                    let cr_height = getCRHeight(fleetType.cr_row_covered);
                                    const cr_width = (seatlayout.right >= 2) ? '70px' : '30px';
                                    const $crSpan = $('<span>', {
                                        class: 'seat comfort-room cr-right',
                                        text: 'CR'
                                    }).css({
                                        height: cr_height,
                                        lineHeight: cr_height,
                                        width: cr_width,
                                        position: position,
                                        right: offset
                                    });
                                    $rightSide.append($('<div>').append($crSpan));
                                }
                                has_cr = true;
                                if (!fleetType.cr_override_seat) seatCounter--;
                            } else {
                                let offset = seatCount - (fleetType.last_row ? fleetType.last_row[key] : 0);
                                if (fleetType.last_row && seatCounter > offset) continue;
                                let label = `${prefix}${seatCounter}`;
                                if (disabled_seats.includes(label)) label = `<del>${label}</del>`;
                                $rightSide.append(busLayout.generateSeats(label, deckIndex));
                            }
                            seatCounter++;
                        }
                        $seatWrapper.append($rightSide);
                        $singleDeck.append($seatWrapper);
                    }

                    // Last Row Logic
                    if (fleetType.last_row && fleetType.last_row[key] > 0) {
                        const $lastRowWrapper = $('<div>', {
                            class: 'seat-wrapper justify-content-center'
                        });
                        for (let lsr = 1; lsr <= fleetType.last_row[key]; lsr++) {
                            let label = `${prefix}${seatCounter}`;
                            $lastRowWrapper.append(busLayout.generateSeats(label, deckIndex));
                            seatCounter++;
                        }
                        $singleDeck.append($lastRowWrapper);
                    } else if (lastRowSeat > 0) {
                        const $lastRowWrapper = $('<div>', {
                            class: 'seat-wrapper justify-content-center'
                        });
                        for (let l = 1; l <= lastRowSeat; l++) {
                            let label = `${prefix}${seatCounter}`;
                            $lastRowWrapper.append(busLayout.generateSeats(label, deckIndex));
                            seatCounter++;
                        }
                        $singleDeck.append($lastRowWrapper);
                    }


                    $container.append(deckHtml);
                });

                // Add click event listener to the seats
                $container.on('click', '.seat:not(.disabled-seat, .comfort-room)', function() {
                    $(this).toggleClass('selected');
                    const seatId = $(this).data('seat');
                    console.log(
                        `Seat ${seatId} was ${$(this).hasClass('selected') ? 'selected' : 'deselected'}.`);
                });
            }

            function getCRHeight(row_covered) {
                let height = (row_covered == 2) ? '85px' : '40px';
                height = (row_covered == 3) ? '130px' : height;
                return height;
            }

            $('#postForm').on('submit', function(e) {
                e.preventDefault();

                let form = document.getElementById("postForm");
                let url = "{{ url('/admin/fleet/type/store') }}";
                let id = $('#fleet_id').val();

                if (id) {
                    url = "{{ url('/admin/fleet/type/store') }}/" + id;
                }
                let formData = new FormData(form);

                $.ajax({
                    url: url,
                    type: 'POST', // ✅ should be POST
                    data: formData,
                    processData: false, // ✅ prevent jQuery from processing data
                    contentType: false, // ✅ let browser set the content type
                    success: function(data) {
                        let fleetType = {
                            name: data.name,
                            seat_layout: data.seat_layout, // left-center-right
                            deck_seats: data.deck_seats, // per deck seat count
                            prefixes: data.prefixes,
                            disabled_seats: data.disabled_seats,
                            last_row: data.last_row,
                            cr_row: parseInt(data.cr_row),
                            cr_position: data.cr_position,
                            cr_override_seat: data.cr_override_seat,
                            cr_row_covered: parseInt(data.cr_row_covered)
                        };
                        renderBusLayout(fleetType, 'seat-layout-container');
                    },
                    error: function(err) {
                        console.error(err);
                    }
                });

                return false;
            });

            $('input[name=deck]').on('input', function() {
                //$('.showSeat').empty();
                for (var deck = 2; deck <= $(this).val(); deck++) {
                    $('.showSeat').append(`
                        <div class="form-group">
                            <label> Seats of Deck - ${deck} </label>
                            <input type="text" class="form-control hasArray" placeholder="@lang('Enter Number of Seat')" name="deck_seats[]" required>
                        </div>
                            <div class="form-group">
                                <label> Last Row of Deck - ${deck} </label>
                                <input type="number" class="form-control hasArray" placeholder="@lang('Enter Number of Last Row (Backseat)')" name="last_row[]" required>
                            </div>
                            <div class="form-group">
                                <label> Prefix of Deck - ${deck} </label>
                                <input type="text" class="form-control hasArray" name="prefixes[]">
                            </div>
                            <hr>
                    `);
                }
            })

            $('.cuModalBtn').on('click', function() {
                let modal = $('#cuModal');
                let data = $(this).data('resource');

                if ($(this).attr('data-modal_title').includes('Add')) {
                    $('#fleet_id').val('');
                } else {
                    $('#fleet_id').val(data.id);
                }

                console.log(data)
                let fleetType = {
                    name: data.name,
                    seat_layout: data.seat_layout, // left-center-right
                    deck_seats: data.deck_seats, // per deck seat count
                    prefixes: data.prefixes,
                    disabled_seats: data.disabled_seats,
                    last_row: data.last_row,
                    cr_row: data.cr_row,
                    cr_position: data.cr_position,
                    cr_override_seat: data.cr_override_seat,
                    cr_row_covered: parseInt(data.cr_row_covered)
                };

                renderBusLayout(fleetType, 'seat-layout-container');

                if (data.has_ac) {
                    modal.find('input[name=has_ac]').bootstrapToggle('on');
                } else {
                    modal.find('input[name=has_ac]').bootstrapToggle('off');
                }

                $('.showSeat').empty();
                let opts = '';
                if (data.deck) {
                    for (var i = 1; i <= data.deck; i++) {
                        let last_row = data.last_row ? data.last_row[i - 1] : 0;
                        let prefix = data.prefixes ? data.prefixes[i - 1] : '';
                        let total_seats = data.deck_seats[i - 1];
                        for (let index = 1; index <= total_seats; index++) {
                            let seat = `${[prefix]}${index}`;
                            opts += `<option value="${seat}">${seat}</option>`;
                        }
                        console.log('opts', opts)
                        $('.showSeat').append(`
                            <div class="form-group">
                                <label> Seats of Deck - ${i} </label>
                                <input type="text" class="form-control hasArray" placeholder="@lang('Enter Number of Seat')" value="${total_seats}" name="deck_seats[]" required>
                            </div>
                            <div class="form-group">
                                <label> Last Row of Deck - ${i} </label>
                                <input type="number" class="form-control hasArray" placeholder="@lang('Enter Number of Last Row (Backseat)')" value="${last_row}" name="last_row[]" required>
                            </div>
                            <div class="form-group">
                                <label> Prefix of Deck - ${i} </label>
                                <input type="text" class="form-control hasArray" value="${prefix}" name="prefixes[]">
                            </div>
                            <hr>
                        `);

                    }
                }
                $("#non-operational-seats").html(`
                    <div class="form-group mt-3">
                        <label for="disabled_seats">@lang('Non-Operational Seats')</label>
                        <select class="select2-auto-tokenize disabled_seats" name="disabled_seats[]"
                            multiple="multiple">
                            ${opts}
                        </select>
                    </div>
                `);



                if (data.disabled_seats) {
                    $('.disabled_seats').val(data.disabled_seats).trigger("change");
                } else {
                    $('.disabled_seats').val('').trigger("change");
                }

                if (data.facilities) {
                    $('#facilities').val(data.facilities).trigger("change");
                } else {
                    $('#facilities').val('').trigger("change");
                }


                $.each($('.select2-auto-tokenize'), function() {
                    $(this)
                        .wrap(`<div class="position-relative"></div>`)
                        .select2({
                            tags: true,
                            tokenSeparators: [','],
                            dropdownParent: $(this).parent()
                        });
                });
            });
        })(jQuery);
    </script>
@endpush
