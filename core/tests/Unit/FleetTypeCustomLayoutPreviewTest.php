<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\ManageFleetController;
use App\Models\FleetType;
use App\Services\SeatLayoutService;
use Illuminate\Http\Request;
use Tests\TestCase;

class FleetTypeCustomLayoutPreviewTest extends TestCase
{
    public function test_fleet_type_restores_custom_geometry_from_the_saved_json_column(): void
    {
        $saved = [
            'version' => 1,
            'decks' => [[
                'name' => 'Lower Deck',
                'rows' => [[['type' => 'seat', 'id' => '1-1', 'label' => '1']]],
            ]],
        ];
        $fleetType = new FleetType();
        $fleetType->setRawAttributes(['custom_seat_layout' => json_encode($saved)]);

        $this->assertSame($saved, $fleetType->custom_seat_layout);
    }

    public function test_preview_renders_the_saved_custom_geometry_for_the_shared_seat_map(): void
    {
        $customLayout = [
            'version' => 1,
            'decks' => [[
                'name' => 'Lower Deck',
                'rows' => [[
                    ['type' => 'seat', 'id' => '1-D2', 'label' => 'D2', 'priority' => true],
                    ['type' => 'seat', 'id' => '1-D1', 'label' => 'D1', 'priority' => true],
                    ['type' => 'aisle'],
                    ['type' => 'seat', 'id' => '1-D3', 'label' => 'D3', 'priority' => true],
                ]],
            ]],
        ];
        $request = Request::create('/admin/fleet/type/preview', 'POST', [
            'name' => 'Custom Executive',
            'seat_layout' => '2x1',
            'deck_seats' => [3],
            'last_row' => [0],
            'prefixes' => ['D'],
            'custom_seat_layout' => json_encode($customLayout),
        ]);

        $response = app(ManageFleetController::class)->typePreview(
            $request,
            app(SeatLayoutService::class)
        );
        $payload = $response->getData(true);

        $this->assertSame(['1-D2', '1-D1', '1-D3'], $payload['seat_ids']);
        $this->assertSame('Lower Deck', $payload['editor_layout']['decks'][0]['name']);
        $this->assertSame(
            ['seat', 'seat', 'aisle', 'seat'],
            array_column($payload['editor_layout']['decks'][0]['rows'][0], 'type')
        );
        $this->assertStringContainsString('data-seat="1-D2"', $payload['html']);
        $this->assertStringContainsString('data-group="custom-1"', $payload['html']);
        $this->assertSame('D2', $payload['seat_options'][0]['label']);
    }
}
