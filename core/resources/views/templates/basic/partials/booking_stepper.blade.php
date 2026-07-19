@php
    $currentStep = $currentStep ?? 'seat';
    $steps = [
        'seat' => 'Seat',
        'details' => 'Details',
        'payment' => 'Payment',
        'done' => 'Done',
    ];
    $stepKeys = array_keys($steps);
    $currentIndex = array_search($currentStep, $stepKeys, true);
    $currentIndex = $currentIndex === false ? 0 : $currentIndex;
    $progress = count($stepKeys) > 1 ? $currentIndex / (count($stepKeys) - 1) : 0;
@endphp

<div class="booking-flow-stepper-shell">
    <div class="booking-flow-stepper" style="--booking-flow-progress: {{ $progress }};">
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
                grid-template-columns: repeat(4, minmax(0, 1fr));
                isolation: isolate;
                left: 0;
                margin: 0;
                padding: 9px clamp(18px, 6vw, 64px) 8px;
                position: fixed;
                right: 0;
                top: 97px;
                z-index: 1045;
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

            @media (max-width: 575px) {
                .booking-flow-stepper {
                    padding-left: 16px;
                    padding-right: 16px;
                }

                .booking-flow-stepper::before,
                .booking-flow-stepper::after {
                    left: 30px;
                    right: 30px;
                }
            }
        </style>
    @endpush
@endonce
