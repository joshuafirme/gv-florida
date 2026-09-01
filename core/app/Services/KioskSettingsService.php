<?php

namespace App\Services;

use RuntimeException;

class KioskSettingsService
{
    public const DEFAULTS = [
        'headline' => "BOOK\nHERE",
        'tagline' => 'YOUR TRIP STARTS HERE',
        'button_text' => 'TOUCH TO START',
        'benefit_one' => "EXECUTIVE\nSLEEPER",
        'benefit_two' => "SAFE &\nRELIABLE",
        'benefit_three' => "WIDE ROUTE\nNETWORK",
    ];

    public function get(): array
    {
        $path = $this->path();
        if (!is_file($path)) {
            return self::DEFAULTS;
        }

        $stored = json_decode((string) file_get_contents($path), true);

        return array_merge(self::DEFAULTS, is_array($stored) ? $stored : []);
    }

    public function save(array $settings): void
    {
        $directory = dirname($this->path());
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Could not create the kiosk settings directory.');
        }

        $data = collect(self::DEFAULTS)
            ->mapWithKeys(fn ($default, $key) => [$key => trim((string) ($settings[$key] ?? $default))])
            ->all();

        $written = file_put_contents(
            $this->path(),
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            LOCK_EX
        );

        if ($written === false) {
            throw new RuntimeException('Could not save the kiosk settings.');
        }
    }

    private function path(): string
    {
        return base_path('assets/admin/contents/kiosk-settings.json');
    }
}
