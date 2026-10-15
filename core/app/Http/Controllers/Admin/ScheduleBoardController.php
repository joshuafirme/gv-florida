<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Counter;
use App\Models\ScheduleBoardAdvertisement;
use App\Models\ScheduleBoardNotice;
use App\Models\ScheduleBoardTrip;
use App\Models\Trip;
use App\Services\ScheduleBoardBroadcaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleBoardController extends Controller
{
    private const IMAGE_PATH = 'assets/images/schedule-board';

    public function __construct(private readonly ScheduleBoardBroadcaster $broadcaster)
    {
    }

    public function index(Counter $counter)
    {
        $pageTitle = "Manage Schedule Board - {$counter->name}";
        $setting = $counter->scheduleBoardSetting()->firstOrCreate([], [
            'show_all_trips' => true,
            'departure_grace_minutes' => 60,
        ]);
        $trips = Trip::with(['route.endTo', 'fleetType', 'schedule'])
            ->where('start_from', $counter->id)
            ->active()
            ->get()
            ->sortBy(fn (Trip $trip) => $trip->schedule?->start_from ?: '99:99:99')
            ->values();
        $storedTrips = $counter->scheduleBoardTrips()->get()->keyBy('trip_id');
        $nextSortOrder = ((int) $storedTrips->max('sort_order')) + 10;

        foreach ($trips as $trip) {
            if ($storedTrips->has($trip->id)) {
                $storedTrip = $storedTrips->get($trip->id);

                if ($setting->show_all_trips && !$storedTrip->is_visible) {
                    $storedTrip->update(['is_visible' => true]);
                }

                continue;
            }

            $storedTrips->put($trip->id, ScheduleBoardTrip::create([
                'counter_id' => $counter->id,
                'trip_id' => $trip->id,
                'sort_order' => $nextSortOrder,
                'is_visible' => (bool) $setting->show_all_trips,
            ]));
            $nextSortOrder += 10;
        }

        $selectedTrips = $storedTrips->sortBy('sort_order')->keyBy('trip_id');
        $advertisements = $counter->scheduleBoardAdvertisements()->orderBy('sort_order')->orderBy('id')->get();
        $notices = $counter->scheduleBoardNotices()->orderBy('sort_order')->orderBy('id')->get();

        return view('admin.counter.schedule-board-manage', compact(
            'pageTitle',
            'counter',
            'setting',
            'trips',
            'selectedTrips',
            'advertisements',
            'notices'
        ));
    }

    public function updateSettings(Request $request, Counter $counter)
    {
        $validated = $request->validate([
            'show_all_trips' => ['required', 'boolean'],
            'departure_grace_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
        ]);

        $counter->scheduleBoardSetting()->updateOrCreate([], $validated);
        $this->broadcast($counter, 'settings');

        return back()->withNotify([['success', 'Schedule board settings updated.']]);
    }

    public function updateTrips(Request $request, Counter $counter)
    {
        $validated = $request->validate([
            'trip_order' => ['required', 'array', 'min:1'],
            'trip_order.*' => ['integer', 'distinct'],
            'trip_ids' => ['nullable', 'array'],
            'trip_ids.*' => ['integer', 'distinct'],
        ]);
        $orderedIds = collect($validated['trip_order'])->map(fn ($id) => (int) $id)->values();
        $selectedIds = collect($validated['trip_ids'] ?? [])->map(fn ($id) => (int) $id)->values();
        $validIds = Trip::where('start_from', $counter->id)
            ->active()
            ->whereIn('id', $orderedIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        if ($orderedIds->diff($validIds)->isNotEmpty() || $selectedIds->diff($orderedIds)->isNotEmpty()) {
            return back()->withErrors(['trip_ids' => 'One or more selected trips do not belong to this counter.']);
        }

        DB::transaction(function () use ($counter, $orderedIds, $selectedIds) {
            $orderedIds->each(function (int $tripId, int $index) use ($counter, $selectedIds) {
                ScheduleBoardTrip::updateOrCreate(
                    [
                        'counter_id' => $counter->id,
                        'trip_id' => $tripId,
                    ],
                    [
                        'sort_order' => ($index + 1) * 10,
                        'is_visible' => $selectedIds->contains($tripId),
                    ]
                );
            });
        });
        $this->broadcast($counter, 'trips');

        return back()->withNotify([['success', 'Displayed trips updated.']]);
    }

    public function storeAdvertisement(Request $request, Counter $counter)
    {
        $validated = $this->validateAdvertisement($request, true);
        $validated['counter_id'] = $counter->id;
        $validated['image'] = fileUploader($request->file('image'), public_path(self::IMAGE_PATH));
        $validated['status'] = $request->boolean('status');
        $validated['sort_order'] ??= (int) $counter->scheduleBoardAdvertisements()->max('sort_order') + 10;
        ScheduleBoardAdvertisement::create($validated);
        $this->broadcast($counter, 'advertisement');

        return back()->withNotify([['success', 'Advertisement added to the schedule board.']]);
    }

    public function updateAdvertisement(
        Request $request,
        Counter $counter,
        ScheduleBoardAdvertisement $advertisement
    ) {
        $this->ensureCounterOwnership($counter, $advertisement->counter_id);
        $validated = $this->validateAdvertisement($request, false);

        if ($request->hasFile('image')) {
            $validated['image'] = fileUploader(
                $request->file('image'),
                public_path(self::IMAGE_PATH),
                old: $advertisement->image
            );
        }

        $validated['status'] = $request->boolean('status');
        $validated['sort_order'] ??= $advertisement->sort_order;
        $advertisement->update($validated);
        $this->broadcast($counter, 'advertisement');

        return back()->withNotify([['success', 'Advertisement updated.']]);
    }

    public function destroyAdvertisement(Counter $counter, ScheduleBoardAdvertisement $advertisement)
    {
        $this->ensureCounterOwnership($counter, $advertisement->counter_id);
        fileManager()->removeFile(public_path(self::IMAGE_PATH) . '/' . $advertisement->image);
        $advertisement->delete();
        $this->broadcast($counter, 'advertisement');

        return back()->withNotify([['success', 'Advertisement removed.']]);
    }

    public function storeNotice(Request $request, Counter $counter)
    {
        $validated = $this->validateNotice($request);
        $validated['counter_id'] = $counter->id;
        $validated['status'] = $request->boolean('status');
        $validated['sort_order'] ??= (int) $counter->scheduleBoardNotices()->max('sort_order') + 10;
        ScheduleBoardNotice::create($validated);
        $this->broadcast($counter, 'notice');

        return back()->withNotify([['success', 'Notice added to the schedule board.']]);
    }

    public function updateNotice(Request $request, Counter $counter, ScheduleBoardNotice $notice)
    {
        $this->ensureCounterOwnership($counter, $notice->counter_id);
        $validated = $this->validateNotice($request);
        $validated['status'] = $request->boolean('status');
        $validated['sort_order'] ??= $notice->sort_order;
        $notice->update($validated);
        $this->broadcast($counter, 'notice');

        return back()->withNotify([['success', 'Notice updated.']]);
    }

    public function destroyNotice(Counter $counter, ScheduleBoardNotice $notice)
    {
        $this->ensureCounterOwnership($counter, $notice->counter_id);
        $notice->delete();
        $this->broadcast($counter, 'notice');

        return back()->withNotify([['success', 'Notice removed.']]);
    }

    private function validateAdvertisement(Request $request, bool $imageRequired): array
    {
        return $request->validate([
            'title' => ['nullable', 'string', 'max:160'],
            'image' => [$imageRequired ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => array_values(array_filter([
                'nullable',
                'date',
                $request->filled('starts_at') ? 'after:starts_at' : null,
            ])),
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'status' => ['nullable', 'boolean'],
        ]);
    }

    private function validateNotice(Request $request): array
    {
        return $request->validate([
            'title' => ['nullable', 'string', 'max:160'],
            'content' => ['required', 'string', 'max:2000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => array_values(array_filter([
                'nullable',
                'date',
                $request->filled('starts_at') ? 'after:starts_at' : null,
            ])),
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'status' => ['nullable', 'boolean'],
        ]);
    }

    private function ensureCounterOwnership(Counter $counter, int $counterId): void
    {
        abort_unless($counter->id === $counterId, 404);
    }

    private function broadcast(Counter $counter, string $contentType): void
    {
        $this->broadcaster->scheduleDataUpdated([
            'source' => 'ScheduleBoardContent',
            'counter_id' => $counter->id,
            'content_type' => $contentType,
            'action' => 'updated',
        ]);
    }
}
