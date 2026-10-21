<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cashier_transaction_events')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            $this->repairMySqlTimestamps();

            return;
        }

        $this->repairPortableTimestamps();
    }

    public function down(): void
    {
        // Historical transaction timestamps must not be moved back to the
        // reconciliation date.
    }

    private function repairMySqlTimestamps(): void
    {
        if (Schema::hasTable('deposits')) {
            DB::statement(<<<'SQL'
                UPDATE `cashier_transaction_events` AS `event`
                INNER JOIN `deposits` AS `deposit` ON `deposit`.`id` = `event`.`deposit_id`
                SET `event`.`processed_at` = COALESCE(`deposit`.`completed_at`, `deposit`.`created_at`)
                WHERE `event`.`status` = 'Sold'
                  AND COALESCE(`deposit`.`completed_at`, `deposit`.`created_at`) IS NOT NULL
                  AND `event`.`processed_at` <> COALESCE(`deposit`.`completed_at`, `deposit`.`created_at`)
            SQL);
        }

        $this->repairMySqlAction('Refunded', 'refunded:', 'ticket_refunds', 'created_at');
        $this->repairMySqlAction('Cancelled', 'cancelled:', 'ticket_cancellations', 'created_at');
        $this->repairMySqlAction('Voided', 'voided:', 'ticket_voids', 'created_at');
        $this->repairMySqlAction(
            'Validated',
            'online-validation:',
            'online_ticket_validations',
            'validated_at'
        );
        $this->repairMySqlAction(
            'Discount Override',
            'discount-override:',
            'online_ticket_validations',
            'discount_authorized_at'
        );

        DB::table('cashier_transaction_events')
            ->where('status', 'Rebooked')
            ->whereNotNull('created_at')
            ->whereColumn('processed_at', '<>', 'created_at')
            ->update(['processed_at' => DB::raw('created_at')]);
    }

    private function repairMySqlAction(
        string $status,
        string $eventPrefix,
        string $sourceTable,
        string $sourceTimestamp
    ): void {
        if (!Schema::hasTable($sourceTable)) {
            return;
        }

        DB::statement(
            "UPDATE `cashier_transaction_events` AS `event` "
            . "INNER JOIN `{$sourceTable}` AS `source` "
            . "ON `event`.`event_key` = CONCAT(?, `source`.`id`) "
            . "SET `event`.`processed_at` = `source`.`{$sourceTimestamp}` "
            . "WHERE `event`.`status` = ? "
            . "AND `source`.`{$sourceTimestamp}` IS NOT NULL "
            . "AND `event`.`processed_at` <> `source`.`{$sourceTimestamp}`",
            [$eventPrefix, $status]
        );
    }

    private function repairPortableTimestamps(): void
    {
        if (Schema::hasTable('deposits')) {
            DB::table('deposits')
                ->where(function ($query) {
                    $query->whereNotNull('completed_at')->orWhereNotNull('created_at');
                })
                ->select(['id', 'completed_at', 'created_at'])
                ->orderBy('id')
                ->chunkById(500, function ($deposits) {
                    foreach ($deposits as $deposit) {
                        DB::table('cashier_transaction_events')
                            ->where('status', 'Sold')
                            ->where('deposit_id', $deposit->id)
                            ->where('processed_at', '<>', $deposit->completed_at ?: $deposit->created_at)
                            ->update([
                                'processed_at' => $deposit->completed_at ?: $deposit->created_at,
                            ]);
                    }
                });
        }

        $this->repairPortableAction('Refunded', 'refunded:', 'ticket_refunds', 'created_at');
        $this->repairPortableAction('Cancelled', 'cancelled:', 'ticket_cancellations', 'created_at');
        $this->repairPortableAction('Voided', 'voided:', 'ticket_voids', 'created_at');
        $this->repairPortableAction(
            'Validated',
            'online-validation:',
            'online_ticket_validations',
            'validated_at'
        );
        $this->repairPortableAction(
            'Discount Override',
            'discount-override:',
            'online_ticket_validations',
            'discount_authorized_at'
        );

        DB::table('cashier_transaction_events')
            ->where('status', 'Rebooked')
            ->whereNotNull('created_at')
            ->whereColumn('processed_at', '<>', 'created_at')
            ->update(['processed_at' => DB::raw('created_at')]);
    }

    private function repairPortableAction(
        string $status,
        string $eventPrefix,
        string $sourceTable,
        string $sourceTimestamp
    ): void {
        if (!Schema::hasTable($sourceTable)) {
            return;
        }

        DB::table($sourceTable)
            ->whereNotNull($sourceTimestamp)
            ->select(['id', $sourceTimestamp])
            ->orderBy('id')
            ->chunkById(500, function ($sources) use (
                $status,
                $eventPrefix,
                $sourceTimestamp
            ) {
                foreach ($sources as $source) {
                    DB::table('cashier_transaction_events')
                        ->where('status', $status)
                        ->where('event_key', $eventPrefix . $source->id)
                        ->where('processed_at', '<>', $source->{$sourceTimestamp})
                        ->update(['processed_at' => $source->{$sourceTimestamp}]);
                }
            });
    }
};
