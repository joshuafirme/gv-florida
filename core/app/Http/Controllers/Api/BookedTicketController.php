<?php

namespace App\Http\Controllers\Api;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\BookedTicket;
use App\Models\Deposit;
use Illuminate\Http\Request;

class BookedTicketController extends Controller
{
    public function updateExpiredTicket()
    {
        $deposits = Deposit::where('status', Status::PAYMENT_PENDING)
            ->where('updated_at', '<=', now()->subMinutes(15))
            ->get();

        foreach ($deposits as $deposit) {
            $deposit = Deposit::find($deposit->id);
            $deposit->status = Status::PAYMENT_EXPIRED;
            $deposit->save();

            $deposit->bookedTicket->status = Status::BOOKED_EXPIRED;
            $deposit->bookedTicket->save();
        }

        if (count($deposits) > 0) {
            return response()->json([
                'success' => true,
                'message' => count($deposits) . " expired tickets has been updated."
            ]);
        }   return response()->json([
                'success' => true,
                'message' =>  "All goods."
            ]);
    }
}
