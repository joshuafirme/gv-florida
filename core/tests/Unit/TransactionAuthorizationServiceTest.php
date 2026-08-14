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

    public function test_authorization_assignment_requires_both_secure_code_values(): void
    {
        $admin = new Admin();

        $this->assertFalse($admin->has_authorization_code);

        $admin->authorization_code_hash = 'hashed-code';
        $this->assertFalse($admin->has_authorization_code);

        $admin->authorization_code_lookup = 'private-lookup';
        $this->assertFalse($admin->has_viewable_authorization_code);

        $admin->authorization_code_encrypted = 'Visible-4821';
        $this->assertTrue($admin->has_authorization_code);
        $this->assertTrue($admin->has_viewable_authorization_code);
        $this->assertSame('Visible-4821', $admin->authorization_code_encrypted);
        $this->assertNotSame('Visible-4821', $admin->getAttributes()['authorization_code_encrypted']);

        $serialized = $admin->toArray();
        $this->assertTrue($serialized['has_authorization_code']);
        $this->assertTrue($serialized['has_viewable_authorization_code']);
        $this->assertArrayNotHasKey('authorization_code_hash', $serialized);
        $this->assertArrayNotHasKey('authorization_code_lookup', $serialized);
        $this->assertArrayNotHasKey('authorization_code_encrypted', $serialized);
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
