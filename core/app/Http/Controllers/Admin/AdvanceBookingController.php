<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdvanceBookingController extends Controller
{
    public function advanceBookingDaysSettings()
    {
        $pageTitle = 'Booking Settings';
        $data = getBookingSettings();

        return view('admin.advance-booking.settings', compact('pageTitle', 'data'));
    }

    public function updateAllowedAdvanceBookingDays(Request $request)
    {
        $data = $request->validate([
            'online_advance_booking_days' => 'required|integer|min:0|max:365',
            'kiosk_advance_booking_days' => 'required|integer|min:0|max:365',
            'online_booking_cutoff_minutes' => 'required|integer|min:0|max:1440',
            'kiosk_booking_cutoff_minutes' => 'required|integer|min:0|max:1440',
        ]);

        Storage::put('settings/advance_booking.json', json_encode($data, JSON_PRETTY_PRINT));

        $notify[] = ['success', 'Booking settings updated successfully.'];
        return back()->withNotify($notify);
    }
}
