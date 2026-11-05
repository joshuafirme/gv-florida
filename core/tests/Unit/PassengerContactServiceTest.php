<?php

namespace Tests\Unit;

use App\Services\PassengerContactService;
use PHPUnit\Framework\TestCase;

class PassengerContactServiceTest extends TestCase
{
    public function test_it_normalizes_supported_philippine_mobile_formats(): void
    {
        $service = new PassengerContactService;

        $this->assertSame('09171234567', $service->normalize('0917 123 4567'));
        $this->assertSame('09171234567', $service->normalize('+63 917 123 4567'));
        $this->assertSame('09171234567', $service->normalize('9171234567'));
    }

    public function test_it_requires_a_complete_philippine_mobile_number(): void
    {
        $service = new PassengerContactService;

        $this->assertTrue($service->isValid('0917-123-4567'));
        $this->assertFalse($service->isValid('0917-123'));
        $this->assertFalse($service->isValid('08171234567'));
        $this->assertFalse($service->isValid([]));
    }
}
