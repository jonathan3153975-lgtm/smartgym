<?php

namespace App\Support;

use App\Models\Network;
use Illuminate\Support\Facades\Storage;

class NetworkTheme
{
    /**
     * Default palettes used when the network has not customized a color.
     *
     * @var array<string, array<string, string>>
     */
    public const DEFAULTS = [
        'dark' => [
            'background_primary' => '#090D18',
            'background_secondary' => '#111827',
            'surface' => '#192337',
            'surface_elevated' => '#222F46',
            'border' => '#2A3850',
            'text_primary' => '#F1F5F9',
            'text_secondary' => '#A6B0C3',
            'primary' => '#00E5FF',
            'on_primary' => '#090D18',
            'secondary' => '#A855F7',
            'on_secondary' => '#FFFFFF',
            'accent' => '#C6FF00',
            'on_accent' => '#090D18',
            'success' => '#00E676',
            'warning' => '#FFB300',
            'danger' => '#FF3864',
        ],
        'light' => [
            'background_primary' => '#F8FAFC',
            'background_secondary' => '#FFFFFF',
            'surface' => '#F1F5F9',
            'surface_elevated' => '#FFFFFF',
            'border' => '#E2E8F0',
            'text_primary' => '#0F172A',
            'text_secondary' => '#64748B',
            'primary' => '#0891B2',
            'on_primary' => '#FFFFFF',
            'secondary' => '#7C3AED',
            'on_secondary' => '#FFFFFF',
            'accent' => '#84CC16',
            'on_accent' => '#0F172A',
            'success' => '#16A34A',
            'warning' => '#CA8A04',
            'danger' => '#DC2626',
        ],
    ];

    /**
     * CSS variables controlled by each palette key.
     *
     * @var array<string, array<int, string>>
     */
    private const CSS_VARIABLES = [
        'background_primary' => ['--color-bg-primary', '--color-background'],
        'background_secondary' => ['--color-bg-secondary'],
        'surface' => ['--color-surface'],
        'surface_elevated' => ['--color-surface-elevated'],
        'border' => ['--color-border'],
        'text_primary' => ['--color-text-primary', '--color-text'],
        'text_secondary' => ['--color-text-secondary', '--color-text-muted'],
        'primary' => ['--color-cyan-neon', '--color-primary'],
        'on_primary' => ['--color-on-primary'],
        'secondary' => ['--color-purple-neon', '--color-secondary'],
        'on_secondary' => ['--color-on-secondary'],
        'accent' => ['--color-lime-green', '--color-accent'],
        'on_accent' => ['--color-on-accent'],
        'success' => ['--color-green', '--color-success'],
        'warning' => ['--color-yellow', '--color-warning'],
        'danger' => ['--color-red', '--color-danger'],
    ];

    /**
     * @return array<string, array<string, string>>
     */
    public static function forNetwork(?Network $network): array
    {
        $stored = $network?->settings['theme'] ?? [];
        $palettes = [];

        foreach (self::DEFAULTS as $mode => $defaults) {
            $palettes[$mode] = self::normalizePalette($defaults, $stored[$mode] ?? []);
        }

        return $palettes;
    }

    /**
     * Build the CSS overrides for both themes.
     */
    public static function css(?Network $network): string
    {
        if (! $network) {
            return '';
        }

        $palettes = self::forNetwork($network);

        return ':root.dark{'.self::paletteToCss($palettes['dark']).'}'
            .':root:not(.dark){'.self::paletteToCss($palettes['light']).'}';
    }

    public static function mode(?Network $network): string
    {
        $mode = $network?->settings['theme_mode'] ?? 'system';

        return in_array($mode, ['light', 'dark', 'system'], true) ? $mode : 'system';
    }

    public static function companyName(?Network $network): string
    {
        $name = trim((string) ($network?->settings['company_name'] ?? ''));

        return $name !== '' ? $name : ($network?->name ?? config('app.name'));
    }

    public static function logoUrl(?Network $network): ?string
    {
        $logo = $network?->settings['logo'] ?? null;

        return $logo ? Storage::disk('public')->url($logo) : null;
    }

    /**
     * @param  array<string, string>  $defaults
     * @param  array<string, mixed>  $stored
     * @return array<string, string>
     */
    private static function normalizePalette(array $defaults, array $stored): array
    {
        $palette = [];

        foreach ($defaults as $key => $default) {
            $value = $stored[$key] ?? $default;
            $palette[$key] = self::isValidHex($value) ? $value : $default;
        }

        return $palette;
    }

    /**
     * @param  array<string, string>  $palette
     */
    private static function paletteToCss(array $palette): string
    {
        $rules = '';

        foreach (self::CSS_VARIABLES as $key => $variables) {
            if (! isset($palette[$key])) {
                continue;
            }

            foreach ($variables as $variable) {
                $rules .= "{$variable}:{$palette[$key]};";
            }
        }

        return $rules;
    }

    private static function isValidHex(mixed $value): bool
    {
        return is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1;
    }
}
