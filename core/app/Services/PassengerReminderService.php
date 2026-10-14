<?php

namespace App\Services;

use App\Models\BookingReminder;
use App\Models\PetFriendlyTripCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PassengerReminderService
{
    private const CACHE_KEY = 'online-booking.passenger-reminders.v2';

    private const LEGACY_CACHE_KEY = 'online-booking.passenger-reminders';

    public function activeContent(): array
    {
        if (!Schema::hasTable('booking_reminders') || !Schema::hasTable('pet_friendly_trip_codes')) {
            return [
                'reminders' => collect(),
                'tripCodes' => collect(),
                'icons' => self::icons(),
                'version' => 'empty',
            ];
        }

        $content = Cache::rememberForever(self::CACHE_KEY, function () {
            $reminders = BookingReminder::active()->ordered()->get();
            $tripCodes = PetFriendlyTripCode::active()->ordered()->get();

            return [
                'reminders' => $reminders,
                'tripCodes' => $tripCodes,
                'version' => sha1(json_encode([
                    'reminders' => $reminders->map(fn (BookingReminder $reminder) => [
                        $reminder->id,
                        $reminder->icon,
                        $reminder->content,
                        $reminder->updated_at?->getTimestamp(),
                    ])->all(),
                    'tripCodes' => $tripCodes->map(fn (PetFriendlyTripCode $tripCode) => [
                        $tripCode->id,
                        $tripCode->place,
                        $tripCode->code,
                        $tripCode->updated_at?->getTimestamp(),
                    ])->all(),
                ])),
            ];
        });

        return $content + ['icons' => self::icons()];
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::LEGACY_CACHE_KEY);
    }

    public function iconClass(string $icon): string
    {
        return self::icons()[$icon] ?? self::icons()['info'];
    }

    public static function icons(): array
    {
        return [
            'discount' => 'las la-percent',
            'luggage' => 'las la-suitcase-rolling',
            'pet' => 'las la-paw',
            'ticket' => 'las la-ticket-alt',
            'payment' => 'las la-credit-card',
            'schedule' => 'las la-clock',
            'document' => 'las la-file-alt',
            'health' => 'las la-globe',
            'safety' => 'las la-shield-alt',
            'warning' => 'las la-exclamation-triangle',
            'info' => 'las la-info-circle',
            'bus' => 'las la-bus',
        ];
    }
}
