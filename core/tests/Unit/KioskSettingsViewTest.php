<?php

namespace Tests\Unit;

use App\Services\KioskSettingsService;
use Tests\TestCase;

class KioskSettingsViewTest extends TestCase
{
    public function test_image_uploader_accepts_a_custom_path_without_a_file_type(): void
    {
        $html = view('components.image-uploader', [
            'imagePath' => '/assets/images/kiosk/kiosk-hero.png',
            'required' => false,
        ])->render();

        $this->assertStringContainsString('/assets/images/kiosk/kiosk-hero.png', $html);
        $this->assertStringNotContainsString(' required', $html);
    }

    public function test_kiosk_copy_has_all_idle_screen_fields(): void
    {
        $settings = app(KioskSettingsService::class)->get();

        $this->assertSame(
            ['button_text', 'benefit_one', 'benefit_two', 'benefit_three'],
            array_keys($settings)
        );

        $this->assertArrayNotHasKey('headline', $settings);
        $this->assertArrayNotHasKey('tagline', $settings);
    }

    public function test_headline_and_tagline_are_removed_from_settings_and_overlay(): void
    {
        $settingsView = file_get_contents(resource_path('views/admin/setting/kiosk.blade.php'));
        $ticketView = file_get_contents(resource_path('views/templates/basic/ticket.blade.php'));

        $this->assertStringNotContainsString('name="headline"', $settingsView);
        $this->assertStringNotContainsString('name="tagline"', $settingsView);
        $this->assertStringNotContainsString('kiosk-idle-hero__headline', $ticketView);
        $this->assertStringNotContainsString('kiosk-idle-hero__tagline', $ticketView);
        $this->assertStringContainsString('@media(orientation:portrait)', $ticketView);
    }
}
