<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Storage;

class AdvanceBookingController extends Controller
{
    public function advanceBookingDaysSettings()
    {
        $pageTitle = 'Advance Booking';

        $message = 'Allowed advance booking days updated successfully';

        $allowedDays = request()->input('allowed_days');
        $data = [
            'allowed_days' => $allowedDays,
        ];
        $jsonPayload = json_encode($data);
        $path = "settings/advance_booking.json";
        Storage::put($path, $jsonPayload);

        $notify[] = ['success', $message];
        return back()->withNotify($notify);
    }

    public function updateAllowedAdvanceBookingDays()
    {
        $pageTitle = 'Advance Booking';

        $message = 'Allowed advance booking days updated successfully';

        $allowedDays = request()->input('allowed_days');
        $data = [
            'allowed_days' => $allowedDays,
        ];
        $jsonPayload = json_encode($data);
        $path = "settings/advance_booking.json";
        Storage::put($path, $jsonPayload);

        $notify[] = ['success', $message];
        return back()->withNotify($notify);
    }
}
