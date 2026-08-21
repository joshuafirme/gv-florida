<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripChannelAvailability;
use App\Services\TripChannelAccessService;
use App\Services\TransactionAuthorizationService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TripChannelAccessController extends Controller
{
    public function index(Trip $trip)
    {
        $trip->load(['route', 'schedule', 'fleetType']);

        return response()->json([
            'trip' => [
                'id' => $trip->id,
                'title' => $trip->route?->name ?: $trip->title,
                'departure' => $trip->schedule?->start_from
                    ? date('h:i A', strtotime($trip->schedule->start_from))
                    : null,
                'defaults' => [
                    TripChannelAvailability::ONLINE => (bool) $trip->online_booking_enabled,
                    TripChannelAvailability::KIOSK => (bool) $trip->kiosk_booking_enabled,
                ],
            ],
            'rules' => $this->rules($trip),
        ]);
    }

    public function store(
        Request $request,
        Trip $trip,
        TripChannelAccessService $channelAccessService
    ) {
        $validated = $request->validate([
            'selection_mode' => ['required', Rule::in(['single', 'multiple', 'range'])],
            'single_date' => 'nullable|required_if:selection_mode,single|date|after_or_equal:today',
            'dates' => 'nullable|required_if:selection_mode,multiple|array|min:1',
            'dates.*' => 'required|date|after_or_equal:today',
            'date_from' => 'nullable|required_if:selection_mode,range|date|after_or_equal:today',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'online_state' => ['nullable', Rule::in(['enabled', 'disabled'])],
            'kiosk_state' => ['nullable', Rule::in(['enabled', 'disabled'])],
            'reason' => 'nullable|string|max:1000',
            'authorization_code' => 'required|string|max:100',
        ]);

        $channelStates = [
            TripChannelAvailability::ONLINE => $this->enabledState($validated['online_state'] ?? null),
            TripChannelAvailability::KIOSK => $this->enabledState($validated['kiosk_state'] ?? null),
        ];

        if (collect($channelStates)->every(fn ($state) => $state === null)) {
            throw ValidationException::withMessages([
                'channels' => 'Select Available or Blocked for at least one booking channel.',
            ]);
        }

        $dates = $this->selectedDates($validated);
        $records = $channelAccessService->apply(
            $trip->loadMissing(['route', 'schedule', 'fleetType']),
            $dates,
            $channelStates,
            isset($validated['reason']) ? trim($validated['reason']) : null,
            $validated['authorization_code'],
            $request->user('admin')
        );

        return response()->json([
            'success' => true,
            'message' => $records->count() . ' dated channel setting(s) saved successfully.',
            'rules' => $this->rules($trip),
        ]);
    }

    public function authorizeCode(
        Request $request,
        Trip $trip,
        TransactionAuthorizationService $authorizationService
    ) {
        $validated = $request->validate([
            'authorization_code' => 'required|string|max:100',
            'reason' => 'nullable|string|max:1000',
        ]);

        $authorizedBy = $authorizationService->authorize(
            $validated['authorization_code'],
            TransactionAuthorizationService::CHANNEL_ACCESS,
            [
                'reason' => isset($validated['reason']) ? trim($validated['reason']) : null,
            ]
        );
        $authorizedByName = $authorizedBy->name ?: $authorizedBy->username ?: 'Authorized personnel';

        return response()->json([
            'authorized' => true,
            'authorized_by' => $authorizedByName,
            'message' => 'Authorized by ' . $authorizedByName . '.',
        ]);
    }

    public function destroy(
        Request $request,
        Trip $trip,
        TripChannelAvailability $availability,
        TripChannelAccessService $channelAccessService
    ) {
        abort_unless((int) $availability->trip_id === (int) $trip->id, 404);

        $validated = $request->validate([
            'authorization_code' => 'required|string|max:100',
        ]);

        $channelAccessService->remove(
            $trip->loadMissing(['route', 'schedule', 'fleetType']),
            $availability,
            $validated['authorization_code'],
            $request->user('admin')
        );

        return response()->json([
            'success' => true,
            'message' => 'The dated channel override was removed successfully.',
            'rules' => $this->rules($trip),
        ]);
    }

    private function selectedDates(array $validated): Collection
    {
        if ($validated['selection_mode'] === 'single') {
            return collect([Carbon::parse($validated['single_date'])]);
        }

        if ($validated['selection_mode'] === 'multiple') {
            return collect($validated['dates'])->map(fn ($date) => Carbon::parse($date));
        }

        $from = Carbon::parse($validated['date_from'])->startOfDay();
        $to = Carbon::parse($validated['date_to'] ?? $validated['date_from'])->startOfDay();

        return collect(CarbonPeriod::create($from, $to))->map(fn ($date) => Carbon::instance($date));
    }

    private function enabledState(?string $state): ?bool
    {
        return match ($state) {
            'enabled' => true,
            'disabled' => false,
            default => null,
        };
    }

    private function rules(Trip $trip): array
    {
        return $trip->channelAvailabilities()
            ->whereDate('journey_date', '>=', today())
            ->with([
                'performedBy:id,name,username',
                'authorizedBy:id,name,username',
            ])
            ->orderBy('journey_date')
            ->orderBy('channel')
            ->get()
            ->map(fn (TripChannelAvailability $rule) => [
                'id' => $rule->id,
                'date' => $rule->journey_date->format('Y-m-d'),
                'date_label' => $rule->journey_date->format('D, M j, Y'),
                'channel' => $rule->channel,
                'is_enabled' => (bool) $rule->is_enabled,
                'state' => $rule->is_enabled ? 'Available' : 'Blocked',
                'reason' => $rule->reason,
                'performed_by' => $rule->performedBy?->name ?: $rule->performedBy?->username,
                'authorized_by' => $rule->authorizedBy?->name ?: $rule->authorizedBy?->username,
                'updated_at' => $rule->updated_at?->format('M j, Y h:i A'),
                'delete_url' => route('admin.trip.channel-access.destroy', [$trip->id, $rule->id]),
            ])
            ->all();
    }
}
