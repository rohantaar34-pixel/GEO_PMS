<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SystemSetting extends Model
{
    private const DEFAULTS = [
        'settings_key' => 'default',
        'system_name' => 'GEO CORP. Project Budget Tracking',
        'system_short_name' => 'GEO CORP.',
        'system_tagline' => 'Budget Tracking',
        'primary_color' => '#0F6FB0',
        'logo_path' => null,
    ];

    private const BUNDLED_LOGO_CANDIDATES = [
        'images/logo.png',
        'images/Logo.jpg',
        'images/logo.jpg',
    ];

    protected $fillable = [
        'settings_key',
        'system_name',
        'system_short_name',
        'system_tagline',
        'primary_color',
        'logo_path',
    ];

    public static function current(): self
    {
        if (! Schema::hasTable('system_settings')) {
            return static::make(static::defaults());
        }

        return static::query()->firstOrCreate(
            ['settings_key' => 'default'],
            static::defaults()
        );
    }

    public static function defaults(): array
    {
        return self::DEFAULTS;
    }

    public function getResolvedNameAttribute(): string
    {
        return trim((string) ($this->system_name ?: static::defaults()['system_name']));
    }

    public function getResolvedShortNameAttribute(): string
    {
        $value = trim((string) $this->system_short_name);

        return $value !== '' ? $value : static::defaults()['system_short_name'];
    }

    public function getResolvedTaglineAttribute(): string
    {
        $value = trim((string) $this->system_tagline);

        return $value !== '' ? $value : static::defaults()['system_tagline'];
    }

    public function getResolvedPrimaryColorAttribute(): string
    {
        return $this->normalizeHex($this->primary_color ?: static::defaults()['primary_color']);
    }

    public function getPrimaryColorDarkAttribute(): string
    {
        return $this->mixHex($this->resolved_primary_color, '#000000', 0.18);
    }

    public function getPrimaryColorLightAttribute(): string
    {
        return $this->mixHex($this->resolved_primary_color, '#FFFFFF', 0.86);
    }

    public function getPrimaryColorRgbAttribute(): string
    {
        [$red, $green, $blue] = $this->hexToRgb($this->resolved_primary_color);

        return $red . ', ' . $green . ', ' . $blue;
    }

    public function getLogoUrlAttribute(): string
    {
        if ($this->logo_path && Storage::disk('public')->exists($this->logo_path)) {
            return Storage::disk('public')->url($this->logo_path);
        }

        $bundledLogoPath = $this->resolveBundledLogoPath();

        if ($bundledLogoPath) {
            return asset($bundledLogoPath);
        }

        return asset(self::BUNDLED_LOGO_CANDIDATES[0]);
    }

    public function getLogoFilePathAttribute(): ?string
    {
        if ($this->logo_path && Storage::disk('public')->exists($this->logo_path)) {
            return Storage::disk('public')->path($this->logo_path);
        }

        $bundledLogoPath = $this->resolveBundledLogoPath();

        return $bundledLogoPath ? public_path($bundledLogoPath) : null;
    }

    private function normalizeHex(string $color): string
    {
        $color = strtoupper(trim($color));

        if (! preg_match('/^#?[0-9A-F]{6}$/', $color)) {
            return static::defaults()['primary_color'];
        }

        return str_starts_with($color, '#') ? $color : '#' . $color;
    }

    private function hexToRgb(string $color): array
    {
        $color = ltrim($this->normalizeHex($color), '#');

        return [
            hexdec(substr($color, 0, 2)),
            hexdec(substr($color, 2, 2)),
            hexdec(substr($color, 4, 2)),
        ];
    }

    private function mixHex(string $base, string $mixWith, float $weight): string
    {
        [$baseRed, $baseGreen, $baseBlue] = $this->hexToRgb($base);
        [$mixRed, $mixGreen, $mixBlue] = $this->hexToRgb($mixWith);

        $red = (int) round(($baseRed * (1 - $weight)) + ($mixRed * $weight));
        $green = (int) round(($baseGreen * (1 - $weight)) + ($mixGreen * $weight));
        $blue = (int) round(($baseBlue * (1 - $weight)) + ($mixBlue * $weight));

        return sprintf('#%02X%02X%02X', $red, $green, $blue);
    }

    private function resolveBundledLogoPath(): ?string
    {
        foreach (self::BUNDLED_LOGO_CANDIDATES as $path) {
            if (file_exists(public_path($path))) {
                return $path;
            }
        }

        return null;
    }
}
