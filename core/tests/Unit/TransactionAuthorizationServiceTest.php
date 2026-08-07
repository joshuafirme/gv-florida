<?php

namespace Tests\Unit;

use App\Constants\Status;
use App\Models\Admin;
use App\Models\UserRole;
use App\Services\TransactionAuthorizationService;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TransactionAuthorizationServiceTest extends TestCase
{
    public function test_authorization_codes_are_hashed_and_have_a_deterministic_private_lookup(): void
    {
        $code = 'Secure-4821';

        $this->assertTrue(Hash::check($code, TransactionAuthorizationService::hash($code)));
        $this->assertSame(
            TransactionAuthorizationService::lookup($code),
            TransactionAuthorizationService::lookup("  {$code}  ")
        );
        $this->assertNotSame(
            TransactionAuthorizationService::lookup($code),
            TransactionAuthorizationService::lookup('Secure-4822')
        );
    }

    public function test_each_transaction_requires_its_assigned_authorization_permission(): void
    {
        $service = app(TransactionAuthorizationService::class);
        $admin = new Admin(['status' => Status::ENABLE]);
        $admin->setRelation('role', new UserRole([
            'status' => Status::ENABLE,
            'permissions' => json_encode([
                'admin.vehicle.ticket.authorize.cancel',
                'admin.vehicle.ticket.authorize.refund',
            ]),
        ]));

        $this->assertTrue($service->canAuthorize($admin, TransactionAuthorizationService::CANCELLATION));
        $this->assertTrue($service->canAuthorize($admin, TransactionAuthorizationService::REFUND));
        $this->assertFalse($service->canAuthorize($admin, TransactionAuthorizationService::REBOOKING));
        $this->assertFalse($service->canAuthorize($admin, TransactionAuthorizationService::SEAT_LOCKING));
        $this->assertFalse($service->canAuthorize($admin, TransactionAuthorizationService::VOID));
    }
}
