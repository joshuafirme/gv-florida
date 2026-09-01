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
            ['headline', 'tagline', 'button_text', 'benefit_one', 'benefit_two', 'benefit_three'],
            array_keys($settings)
        );
    }
}
