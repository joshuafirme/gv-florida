<?php

namespace Database\Seeders;

use App\Services\PaynamicsPaymentMethodImporter;
use Illuminate\Database\Seeder;

class PaynamicsPaymentMethodSeeder extends Seeder
{
    public function run(PaynamicsPaymentMethodImporter $importer): void
    {
        $importer->importFile();
    }
}
