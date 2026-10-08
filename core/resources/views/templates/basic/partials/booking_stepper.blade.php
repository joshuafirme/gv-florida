@php
    $currentStep = $currentStep ?? 'seat';
    $steps = [
        'trip' => 'Select Trip',
        'seat' => 'Select Seat',
        'details' => 'Passenger Details',
        'payment' => 'Payment',
        'done' => 'Confirmation',
    ];
    $stepKeys = array_keys($steps);
    $currentIndex = array_search($currentStep, $stepKeys, true);
    $currentIndex = $currentIndex === false ? 0 : $currentIndex;
    $progress = count($stepKeys) > 1 ? $currentIndex / (count($stepKeys) - 1) : 0;
    $isKioskFlow = $isKioskFlow
        ?? ($isKioskBooking ?? false)
        || ($layout ?? null) === 'layouts.kiosk'
        || request()->filled('kiosk_id');
    $showNavigation = $showNavigation ?? ($isKioskFlow ? $currentStep !== 'trip' : $currentStep === 'trip');
    $navigationTicket = $bookedTicket ?? $ticket ?? null;
    $navigationKioskId = $kiosk_id
        ?? request('kiosk_id')
        ?? data_get($navigationTicket, 'kiosk_id')
        ?? session('kiosk_id');
    $navigationCounterId = request('counter_id')
        ?? request('start_from')
        ?? data_get($navigationTicket, 'trip.start_from');
    $startOverUrl = route('ticket', array_filter([
        'kiosk_id' => $navigationKioskId,
        'counter_id' => $navigationCounterId,
    ], fn ($value) => $value !== null && $value !== ''));
@endphp

<div class="booking-flow-stepper-shell {{ $isKioskFlow ? 'is-kiosk' : 'is-online' }} {{ $showNavigation ? 'has-navigation' : '' }}">
    <div class="booking-flow-stepper {{ $showNavigation ? 'has-navigation' : '' }}"
        style="--booking-flow-progress: {{ $progress }};">
        @if ($showNavigation)
            @if ($isKioskFlow)
                <a class="booking-flow-navigation" href="{{ $startOverUrl }}">
                    <i class="las la-arrow-left"></i>
                    <span>@lang('Start Over')</span>
                </a>
            @else
                <a class="booking-flow-navigation" href="{{ route('home') }}">
                    <i class="las la-home"></i>
                    <span>@lang('Home')</span>
                </a>
            @endif
        @endif
        @foreach ($steps as $key => $label)
            @php
                $index = $loop->index;
                $stateClass = $index < $currentIndex ? 'is-complete' : ($index === $currentIndex ? 'is-active' : '');
            @endphp
            <div class="booking-flow-step flow-step {{ $stateClass }}" data-step="{{ $key }}">
                <span class="booking-flow-step__marker">
                    @if ($index < $currentIndex)
                        <i class="las la-check"></i>
                    @else
                        {{ $index + 1 }}
                    @endif
                </span>
                <strong class="booking-flow-step__label">{{ $label }}</strong>
            </div>
        @endforeach
    </div>
</div>

@once
    @push('style')
        <style>
            .booking-flow-stepper-shell {
                height: 68px;
                margin-bottom: 10px;
            }

            .booking-flow-stepper {
                --booking-flow-progress: 0;
                background: #fff;
                border-bottom: 1px solid #e5e7eb;
                box-shadow: 0 1px 8px rgba(15, 23, 42, .08);
                display: grid;
                grid-template-columns: repeat(5, minmax(0, 1fr));
                isolation: isolate;
                left: 0;
                margin: 0;
                padding: 9px clamp(18px, 6vw, 64px) 8px;
                position: fixed;
                right: 0;
                top: 97px;
                z-index: 1045;
            }

            .booking-flow-stepper.has-navigation {
                padding-left: clamp(132px, 17vw, 210px);
            }

            .booking-flow-navigation {
                align-items: center;
                background: transparent;
                border: 0;
                color: var(--booking-primary);
                display: inline-flex;
                font-size: 13px;
                font-weight: 900;
                gap: 7px;
                left: clamp(14px, 3vw, 36px);
                min-height: 42px;
                padding: 6px;
                position: absolute;
                text-decoration: none;
                top: 9px;
                z-index: 3;
            }

            .booking-flow-navigation i {
                align-items: center;
                border: 2px solid currentColor;
                border-radius: 50%;
                display: inline-flex;
                font-size: 17px;
                height: 30px;
                justify-content: center;
                width: 30px;
            }

            .booking-flow-stepper-shell.is-online .booking-flow-stepper {
                top: var(--booking-online-stepper-top, 64px);
            }

            .booking-flow-stepper-shell.is-kiosk .booking-flow-stepper {
                top: var(--booking-kiosk-stepper-top, 97px);
            }

            .booking-flow-stepper::before,
            .booking-flow-stepper::after {
                content: "";
                height: 3px;
                left: clamp(34px, 7vw, 76px);
                position: absolute;
                right: clamp(34px, 7vw, 76px);
                top: 25px;
                z-index: 0;
            }

            .booking-flow-stepper.has-navigation::before,
            .booking-flow-stepper.has-navigation::after {
                left: clamp(151px, 19vw, 230px);
            }

            .booking-flow-stepper::before {
                background: #e5e7eb;
            }

            .booking-flow-stepper::after {
                background: var(--booking-primary);
                transform: scaleX(var(--booking-flow-progress));
                transform-origin: left center;
            }

            .booking-flow-step {
                align-items: center;
                color: #98a1ad;
                display: flex;
                flex-direction: column;
                font-size: 10px;
                font-weight: 800;
                line-height: 1.15;
                min-width: 0;
                position: relative;
                text-align: center;
                z-index: 1;
            }

            .booking-flow-step__marker {
                align-items: center;
                background: #fff;
                border: 2px solid #e5e7eb;
                border-radius: 999px;
                display: inline-flex;
                height: 31px;
                justify-content: center;
                position: relative;
                width: 31px;
                z-index: 2;
                box-shadow: 0 0 0 5px #fff;
            }

            .booking-flow-step__label {
                color: inherit;
                display: block;
                margin-top: 6px;
                max-width: 100%;
                overflow-wrap: anywhere;
            }

            .booking-flow-step.is-active,
            .booking-flow-step.is-complete {
                color: #111827;
            }

            .booking-flow-step.is-active .booking-flow-step__marker,
            .booking-flow-step.is-complete .booking-flow-step__marker {
                background: var(--booking-primary);
                border-color: var(--booking-primary);
                color: var(--booking-on-primary);
                box-shadow: 0 0 0 5px #fff, 0 0 0 8px var(--booking-primary-focus);
            }

            @media (max-width: 767px) {
                .booking-flow-stepper-shell {
                    height: 68px !important;
                }
            }

            @media (max-width: 575px) {
                .booking-flow-stepper-shell.has-navigation {
                    height: 105px !important;
                }

                .booking-flow-stepper {
                    padding-left: 16px;
                    padding-right: 16px;
                }

                .booking-flow-stepper.has-navigation {
                    min-height: 105px;
                    padding: 48px 8px 8px;
                }

                .booking-flow-navigation {
                    left: 12px;
                    padding: 3px 4px;
                    top: 5px;
                }

                .booking-flow-navigation i {
                    font-size: 14px;
                    height: 25px;
                    width: 25px;
                }

                .booking-flow-stepper::before,
                .booking-flow-stepper::after {
                    left: 30px;
                    right: 30px;
                }

                .booking-flow-stepper.has-navigation::before,
                .booking-flow-stepper.has-navigation::after {
                    left: 30px;
                    right: 30px;
                    top: 64px;
                }

                .booking-flow-step__label {
                    font-size: 7px;
                }
            }
        </style>
    @endpush

    @push('script')
        <script>
            (function() {
                "use strict";

                const shell = document.querySelector('.booking-flow-stepper-shell');
                const isKiosk = shell?.classList.contains('is-kiosk');
                const header = document.querySelector(isKiosk ? '.kiosk-navbar' : '.header-bottom');

                if (!shell || !header) return;

                let updateQueued = false;

                function positionOnlineStepper() {
                    const headerBottom = Math.max(0, Math.round(header.getBoundingClientRect().bottom));
                    const property = isKiosk ? '--booking-kiosk-stepper-top' : '--booking-online-stepper-top';
                    shell.style.setProperty(property, `${headerBottom}px`);
                    updateQueued = false;
                }

                function queueStepperPosition() {
                    if (updateQueued) return;
                    updateQueued = true;
                    window.requestAnimationFrame(positionOnlineStepper);
                }

                window.addEventListener('load', queueStepperPosition, { once: true });
                window.addEventListener('resize', queueStepperPosition);
                window.addEventListener('scroll', queueStepperPosition, { passive: true });

                if (typeof ResizeObserver !== 'undefined') {
                    new ResizeObserver(queueStepperPosition).observe(header);
                }

                queueStepperPosition();
            })();
        </script>
    @endpush
@endonce
