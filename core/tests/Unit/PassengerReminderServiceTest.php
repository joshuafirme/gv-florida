<?php

namespace Tests\Unit;

use App\Models\BookingReminder;
use App\Models\PetFriendlyTripCode;
use App\Services\PassengerReminderService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PassengerReminderServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('booking_reminders', function (Blueprint $table) {
            $table->id();
            $table->string('icon');
            $table->text('content');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('pet_friendly_trip_codes', function (Blueprint $table) {
            $table->id();
            $table->string('place');
            $table->string('code')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function test_public_content_contains_only_active_records_in_display_order(): void
    {
        BookingReminder::create([
            'icon' => 'pet',
            'content' => 'Second reminder',
            'sort_order' => 20,
            'status' => true,
        ]);
        BookingReminder::create([
            'icon' => 'discount',
            'content' => 'First reminder',
            'sort_order' => 10,
            'status' => true,
        ]);
        BookingReminder::create([
            'icon' => 'warning',
            'content' => 'Hidden reminder',
            'sort_order' => 5,
            'status' => false,
        ]);
        PetFriendlyTripCode::create([
            'place' => 'SAMPALOC',
            'code' => 'DLX001',
            'sort_order' => 10,
            'status' => true,
        ]);
        PetFriendlyTripCode::create([
            'place' => 'HIDDEN',
            'code' => 'OFF001',
            'sort_order' => 5,
            'status' => false,
        ]);

        $content = app(PassengerReminderService::class)->activeContent();

        $this->assertSame(['First reminder', 'Second reminder'], $content['reminders']->pluck('content')->all());
        $this->assertSame(['DLX001'], $content['tripCodes']->pluck('code')->all());
        $this->assertSame('las la-paw', $content['icons']['pet']);
    }

    public function test_cache_can_be_invalidated_after_an_admin_change(): void
    {
        $service = app(PassengerReminderService::class);
        $initialContent = $service->activeContent();
        $this->assertCount(0, $initialContent['reminders']);

        BookingReminder::create([
            'icon' => 'info',
            'content' => 'New reminder',
            'sort_order' => 10,
            'status' => true,
        ]);

        $this->assertCount(0, $service->activeContent()['reminders']);
        $service->forget();
        $updatedContent = $service->activeContent();

        $this->assertCount(1, $updatedContent['reminders']);
        $this->assertNotSame($initialContent['version'], $updatedContent['version']);
    }
}
