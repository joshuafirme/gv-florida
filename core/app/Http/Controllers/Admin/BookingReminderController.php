<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingReminder;
use App\Models\PetFriendlyTripCode;
use App\Services\PassengerReminderService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BookingReminderController extends Controller
{
    public function __construct(private PassengerReminderService $reminderService)
    {
    }

    public function index()
    {
        $pageTitle = 'Booking Reminders';
        $reminders = BookingReminder::ordered()->get();
        $tripCodes = PetFriendlyTripCode::ordered()->get();
        $icons = PassengerReminderService::icons();

        return view('admin.trip.booking-reminders', compact(
            'pageTitle',
            'reminders',
            'tripCodes',
            'icons'
        ));
    }

    public function store(Request $request)
    {
        $validated = $this->validateReminder($request);
        $validated['sort_order'] = (int) BookingReminder::max('sort_order') + 10;
        $validated['status'] = true;
        BookingReminder::create($validated);
        $this->reminderService->forget();

        return back()->withNotify([['success', 'Booking reminder added successfully.']]);
    }

    public function update(Request $request, BookingReminder $reminder)
    {
        $reminder->update($this->validateReminder($request));
        $this->reminderService->forget();

        return back()->withNotify([['success', 'Booking reminder updated successfully.']]);
    }

    public function status(BookingReminder $reminder)
    {
        $reminder->update(['status' => !$reminder->status]);
        $this->reminderService->forget();

        return back()->withNotify([['success', 'Booking reminder visibility updated.']]);
    }

    public function move(Request $request, BookingReminder $reminder)
    {
        $this->moveItem(BookingReminder::class, $reminder, $request->validate([
            'direction' => ['required', Rule::in(['up', 'down'])],
        ])['direction']);

        return back()->withNotify([['success', 'Booking reminder order updated.']]);
    }

    public function destroy(BookingReminder $reminder)
    {
        $reminder->delete();
        $this->reminderService->forget();

        return back()->withNotify([['success', 'Booking reminder deleted.']]);
    }

    public function storeTripCode(Request $request)
    {
        $validated = $this->validateTripCode($request);
        $validated['sort_order'] = (int) PetFriendlyTripCode::max('sort_order') + 10;
        $validated['status'] = true;
        PetFriendlyTripCode::create($validated);
        $this->reminderService->forget();

        return back()->withNotify([['success', 'Pet-friendly trip code added successfully.']]);
    }

    public function updateTripCode(Request $request, PetFriendlyTripCode $tripCode)
    {
        $tripCode->update($this->validateTripCode($request, $tripCode));
        $this->reminderService->forget();

        return back()->withNotify([['success', 'Pet-friendly trip code updated successfully.']]);
    }

    public function statusTripCode(PetFriendlyTripCode $tripCode)
    {
        $tripCode->update(['status' => !$tripCode->status]);
        $this->reminderService->forget();

        return back()->withNotify([['success', 'Trip code visibility updated.']]);
    }

    public function moveTripCode(Request $request, PetFriendlyTripCode $tripCode)
    {
        $this->moveItem(PetFriendlyTripCode::class, $tripCode, $request->validate([
            'direction' => ['required', Rule::in(['up', 'down'])],
        ])['direction']);

        return back()->withNotify([['success', 'Trip code order updated.']]);
    }

    public function destroyTripCode(PetFriendlyTripCode $tripCode)
    {
        $tripCode->delete();
        $this->reminderService->forget();

        return back()->withNotify([['success', 'Pet-friendly trip code deleted.']]);
    }

    private function validateReminder(Request $request): array
    {
        return $request->validate([
            'icon' => ['required', Rule::in(array_keys(PassengerReminderService::icons()))],
            'content' => ['required', 'string', 'max:5000'],
        ]);
    }

    private function validateTripCode(Request $request, ?PetFriendlyTripCode $tripCode = null): array
    {
        return $request->validate([
            'place' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'max:60',
                Rule::unique('pet_friendly_trip_codes', 'code')->ignore($tripCode?->id),
            ],
        ]);
    }

    private function moveItem(string $modelClass, Model $item, string $direction): void
    {
        DB::transaction(function () use ($modelClass, $item, $direction) {
            $items = $modelClass::query()->ordered()->lockForUpdate()->get();
            $currentIndex = $items->search(fn (Model $candidate) => $candidate->is($item));
            $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

            if ($currentIndex === false || !$items->has($targetIndex)) {
                return;
            }

            $target = $items->get($targetIndex);
            $currentOrder = $item->sort_order;
            $item->sort_order = $target->sort_order;
            $target->sort_order = $currentOrder;
            $item->save();
            $target->save();
        });

        $this->reminderService->forget();
    }
}
