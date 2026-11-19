<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\VehicleTicketController;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

class BookedTicketPredefinedReasonsTest extends TestCase
{
    /**
     * @dataProvider reasonLists
     */
    public function test_booked_ticket_actions_use_the_required_predefined_reasons(
        string $methodName,
        array $expected
    ): void {
        $method = new ReflectionMethod(VehicleTicketController::class, $methodName);

        $this->assertSame($expected, $method->invoke(app(VehicleTicketController::class)));
    }

    public static function reasonLists(): array
    {
        return [
            'cancellation' => ['cancellationReasons', [
                'Passenger Request',
                'Change of Travel Plans',
                'Incorrect Booking Details',
                'Duplicate Booking',
                'Medical Emergency',
                'Operational / Trip Cancellation',
            ]],
            'refund' => ['refundReasons', [
                'Passenger Cancellation',
                'Duplicate Payment',
                'Booking Error',
                'Trip Cancelled by Operator',
                'Schedule Change by Operator',
                'Other / Special Circumstances',
            ]],
            'void' => ['voidReasons', [
                'Incorrect Passenger Details',
                'Incorrect Trip / Schedule',
                'Incorrect Seat Selection',
                'Duplicate Transaction',
                'Cashier / Staff Error',
                'System / Payment Error',
            ]],
            'rebooking' => ['rebookingReasons', [
                'Passenger Schedule Change',
                'Trip Schedule Change',
                'Seat Change',
                'Operational Adjustment',
                'Missed Trip',
                'Other',
            ]],
            'transfer' => ['transferReasons', [
                'Higher Fare',
                'Lower Fare',
                'Different Route',
                'Bus Class Change',
                'Operational Adjustment',
                'Passenger Request',
            ]],
        ];
    }

    public function test_rebooking_and_transfer_modals_render_the_new_reason_controls(): void
    {
        $view = file_get_contents(resource_path('views/admin/ticket/log.blade.php'));

        $this->assertStringContainsString('id="rebookReasonChips"', $view);
        $this->assertStringContainsString('data.reasons.map(reason', $view);
        $this->assertStringContainsString('data.transfer_reasons.map(reason', $view);
        $this->assertStringContainsString("const transferReasonValid = Boolean($('#transferManagementReason').val());", $view);
        $this->assertStringNotContainsString('Other Company Operational Reasons', $view);
    }

    public function test_transfer_reason_is_validated_and_preserves_passenger_request_details(): void
    {
        $method = new ReflectionMethod(VehicleTicketController::class, 'transferReason');
        $controller = app(VehicleTicketController::class);

        $this->assertSame('Higher Fare', $method->invoke($controller, [
            'management_reason' => 'Higher Fare',
        ]));
        $this->assertSame('Passenger Request: Family emergency', $method->invoke($controller, [
            'management_reason' => 'Passenger Request',
            'passenger_reason' => 'Family emergency',
        ]));

        $this->expectException(ValidationException::class);
        $method->invoke($controller, ['management_reason' => 'Legacy reason']);
    }
}
