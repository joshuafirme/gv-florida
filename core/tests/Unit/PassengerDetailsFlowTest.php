<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class PassengerDetailsFlowTest extends TestCase
{
    public function test_booking_flow_requires_each_passenger_name_and_contact_number(): void
    {
        $view = file_get_contents(resource_path('views/templates/basic/user/payment/deposit.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/Gateway/PaymentController.php'));

        $this->assertStringContainsString('Use the same contact number for all passengers.', $view);
        $this->assertStringContainsString('id="useSharedContact"', $view);
        $this->assertStringContainsString('class="flow-input js-passenger-contact js-contact-number"', $view);
        $this->assertStringContainsString("$('.passenger-card[data-seat]').each", $view);
        $this->assertStringContainsString('if (!name) {', $view);
        $this->assertStringContainsString('contact_number: contactNumber', $view);

        $this->assertStringContainsString("if (\$name === '')", $controller);
        $this->assertStringContainsString("'contact_number' => \$contactNumber", $controller);
        $this->assertStringContainsString('PassengerContactService', $controller);
    }

    public function test_switching_contact_modes_preserves_entered_values(): void
    {
        $view = file_get_contents(resource_path('views/templates/basic/user/payment/deposit.blade.php'));

        $this->assertStringContainsString('function syncContactMode(seedEmptyFields = true)', $view);
        $this->assertStringContainsString('if (seedEmptyFields && useSharedContact && !$.trim(sharedInput.val()))', $view);
        $this->assertStringContainsString('if (!$.trim($(this).val()))', $view);
        $this->assertStringContainsString(
            'individualInputs.prop({ disabled: useSharedContact, required: !useSharedContact })',
            $view
        );
    }

    public function test_deposit_template_compiles_all_booking_and_payment_directives(): void
    {
        $view = file_get_contents(resource_path('views/templates/basic/user/payment/deposit.blade.php'));
        $compiled = Blade::compileString($view);

        $this->assertStringNotContainsString('@foreach ($seats as $index => $seat)', $compiled);
        $this->assertStringNotContainsString('@forelse ($gatewayCurrency as $data)', $compiled);
        $this->assertStringContainsString('json_encode($data', $compiled);
    }
}
