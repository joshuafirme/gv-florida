<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TEMPLATE_ACTIONS = ['PAYMENT_COMPLETE', 'PAYMENT_APPROVE'];

    private const DEPARTURE_ROW = '<div>Departure Time : {{departure_time}}</div>';

    public function up(): void
    {
        DB::table('notification_templates')
            ->whereIn('act', self::TEMPLATE_ACTIONS)
            ->orderBy('id')
            ->each(function ($template): void {
                $shortcodes = json_decode($template->shortcodes ?: '{}', true) ?: [];
                $shortcodes['departure_time'] = 'Scheduled departure time';

                $emailBody = (string) $template->email_body;

                if (!str_contains($emailBody, '{{departure_time}}')) {
                    $journeyRow = '<div>Date of Journey : {{journey_date}}</div>';
                    $emailBody = str_contains($emailBody, $journeyRow)
                        ? str_replace($journeyRow, $journeyRow.self::DEPARTURE_ROW, $emailBody)
                        : $emailBody.self::DEPARTURE_ROW;
                }

                DB::table('notification_templates')
                    ->where('id', $template->id)
                    ->update([
                        'shortcodes' => json_encode($shortcodes, JSON_UNESCAPED_UNICODE),
                        'email_body' => $emailBody,
                    ]);
            });
    }

    public function down(): void
    {
        DB::table('notification_templates')
            ->whereIn('act', self::TEMPLATE_ACTIONS)
            ->orderBy('id')
            ->each(function ($template): void {
                $shortcodes = json_decode($template->shortcodes ?: '{}', true) ?: [];
                unset($shortcodes['departure_time']);

                DB::table('notification_templates')
                    ->where('id', $template->id)
                    ->update([
                        'shortcodes' => json_encode($shortcodes, JSON_UNESCAPED_UNICODE),
                        'email_body' => str_replace(self::DEPARTURE_ROW, '', (string) $template->email_body),
                    ]);
            });
    }
};
