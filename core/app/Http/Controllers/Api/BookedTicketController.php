<?php

namespace App\Http\Controllers\Api;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\BookedTicket;
use Illuminate\Http\Request;

class BookedTicketController extends Controller
{
    public function updateExpiredTicket()
    {
        BookedTicket::where('status', Status::BOOKED_PENDING)
            ->where('updated_at', '<=', now()->subMinutes(30))
            ->update(['status' => Status::BOOKED_EXPIRED]);

        return response()->json([
            'success' => true,
            'message' => "All expired tickets has been updated."
        ]);
    }
}
