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

        $this->removeAutomaticTimestampUpdate();

        if (DB::getDriverName() === 'mysql') {
            $this->repairMySqlTimestamps();

            return;
        }

        $this->repairPortableTimestamps();
    }

    public function down(): void
    {
        // Do not restore ON UPDATE behavior or the corrupted historical timestamps.
    }

    private function removeAutomaticTimestampUpdate(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $originalTimeout = (int) DB::selectOne(
            'SELECT @@SESSION.lock_wait_timeout AS lock_wait_timeout'
        )->lock_wait_timeout;

        DB::statement('SET SESSION lock_wait_timeout = 15');

        try {
            // An explicit default prevents legacy MySQL configurations from adding
            // ON UPDATE CURRENT_TIMESTAMP to the first non-null TIMESTAMP column.
            DB::statement(
                'ALTER TABLE `cashier_transaction_events` '
                . 'MODIFY COLUMN `processed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
            );
        } finally {
            DB::statement("SET SESSION lock_wait_timeout = {$originalTimeout}");
        }
    }

    private function repairMySqlTimestamps(): void
    {
        DB::statement(<<<'SQL'
            UPDATE `cashier_transaction_events` AS `event`
            INNER JOIN `deposits` AS `deposit` ON `deposit`.`id` = `event`.`deposit_id`
            SET `event`.`processed_at` = COALESCE(`deposit`.`completed_at`, `deposit`.`created_at`)
            WHERE `event`.`status` = 'Sold'
              AND COALESCE(`deposit`.`completed_at`, `deposit`.`created_at`) IS NOT NULL
              AND `event`.`processed_at` <> COALESCE(`deposit`.`completed_at`, `deposit`.`created_at`)
        SQL);

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

        DB::statement(<<<'SQL'
            UPDATE `cashier_transaction_events`
            SET `processed_at` = `created_at`
            WHERE `status` = 'Rebooked'
              AND `created_at` IS NOT NULL
              AND `processed_at` <> `created_at`
        SQL);
    }

    private function repairMySqlAction(
        string $status,
        string $eventPrefix,
        string $sourceTable,
        string $sourceTimestamp
    ): void {
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
                        ->update([
                            'processed_at' => $deposit->completed_at ?: $deposit->created_at,
                        ]);
                }
            });

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
            ->update(['processed_at' => DB::raw('created_at')]);
    }

    private function repairPortableAction(
        string $status,
        string $eventPrefix,
        string $sourceTable,
        string $sourceTimestamp
    ): void {
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
                        ->update(['processed_at' => $source->{$sourceTimestamp}]);
                }
            });
    }
};
