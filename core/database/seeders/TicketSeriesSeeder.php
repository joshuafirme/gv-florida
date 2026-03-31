<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TicketSeriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $start = 100000;

        $records = DB::table('booked_tickets')->orderBy('id')->get();

        foreach ($records as $index => $record) {
            DB::table('booked_tickets')
                ->where('id', $record->id)
                ->update([
                    'series_number' => $start + $index
                ]);
        }
    }
}
