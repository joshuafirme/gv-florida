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
    $progress = count($stepKeys) > 1 ? ($currentIndex / (count($stepKeys) - 1)) * 100 : 0;
@endphp

<div class="booking-flow-stepper" style="--booking-flow-progress: {{ $progress }}%;">
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

@once
    @push('style')
        <style>
            .booking-flow-stepper {
                --booking-flow-progress: 0%;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 1px 8px rgba(15, 23, 42, .06);
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                margin-bottom: 16px;
                padding: 18px 44px 12px;
                position: relative;
            }

            .booking-flow-stepper::before,
            .booking-flow-stepper::after {
                content: "";
                height: 3px;
                left: 48px;
                position: absolute;
                right: 48px;
                top: 34px;
            }

            .booking-flow-stepper::before {
                background: #e5e7eb;
            }

            .booking-flow-stepper::after {
                background: #df2a82;
                right: auto;
                width: calc((100% - 96px) * var(--booking-flow-progress) / 100);
            }

            .booking-flow-step {
                align-items: center;
                color: #98a1ad;
                display: flex;
                flex-direction: column;
                font-size: 11px;
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
                height: 34px;
                justify-content: center;
                width: 34px;
            }

            .booking-flow-step__label {
                color: inherit;
                display: block;
                margin-top: 9px;
                max-width: 100%;
                overflow-wrap: anywhere;
            }

            .booking-flow-step.is-active,
            .booking-flow-step.is-complete {
                color: #111827;
            }

            .booking-flow-step.is-active .booking-flow-step__marker,
            .booking-flow-step.is-complete .booking-flow-step__marker {
                background: #df2a82;
                border-color: #df2a82;
                color: #fff;
                box-shadow: 0 0 0 4px rgba(223, 42, 130, .12);
            }

            @media (max-width: 575px) {
                .booking-flow-stepper {
                    padding-left: 16px;
                    padding-right: 16px;
                }

                .booking-flow-stepper::before,
                .booking-flow-stepper::after {
                    left: 28px;
                    right: 28px;
                }

                .booking-flow-stepper::after {
                    right: auto;
                    width: calc((100% - 56px) * var(--booking-flow-progress) / 100);
                }
            }
        </style>
    @endpush
@endonce
