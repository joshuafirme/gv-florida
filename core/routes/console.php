<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('payments:expire-pending', function () {
    $expiredCount = app(\App\Services\PendingPaymentExpirationService::class)->expireDue();

    $this->info("Expired {$expiredCount} pending payment(s).");
})->purpose('Expire overdue pending payments and release their reserved seats');

Schedule::command('payments:expire-pending')
    ->everyMinute()
    ->withoutOverlapping();
