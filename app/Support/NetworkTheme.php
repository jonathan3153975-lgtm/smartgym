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
            'background_primary' => '#071426',
            'background_secondary' => '#0D1B30',
            'surface' => '#0D1B30',
            'surface_elevated' => '#142944',
            'overlay' => '#040B14',
            'border' => '#263B57',
            'text_primary' => '#F8FAFC',
            'text_secondary' => '#A9B8CC',
            'primary' => '#0D6EFD',
            'on_primary' => '#FFFFFF',
            'secondary' => '#64748B',
            'on_secondary' => '#FFFFFF',
            'accent' => '#FFD600',
            'on_accent' => '#071426',
            'success' => '#10B981',
            'on_success' => '#071426',
            'success_soft' => '#0B2A22',
            'warning' => '#FFD600',
            'on_warning' => '#071426',
            'warning_soft' => '#2E2A00',
            'danger' => '#EF4444',
            'on_danger' => '#FFFFFF',
            'danger_soft' => '#2E1114',
            'info' => '#00D4FF',
            'on_info' => '#071426',
            'info_soft' => '#04262E',
        ],
        'light' => [
            'background_primary' => '#F4F7FB',
            'background_secondary' => '#FFFFFF',
            'surface' => '#FFFFFF',
            'surface_elevated' => '#F1F5F9',
            'overlay' => '#0F172A',
            'border' => '#CBD5E1',
            'text_primary' => '#0F172A',
            'text_secondary' => '#475569',
            'primary' => '#0D6EFD',
            'on_primary' => '#FFFFFF',
            'secondary' => '#64748B',
            'on_secondary' => '#FFFFFF',
            'accent' => '#D97706',
            'on_accent' => '#FFFFFF',
            'success' => '#059669',
            'on_success' => '#FFFFFF',
            'success_soft' => '#D1FAE5',
            'warning' => '#B45309',
            'on_warning' => '#FFFFFF',
            'warning_soft' => '#FEF3C7',
            'danger' => '#DC2626',
            'on_danger' => '#FFFFFF',
            'danger_soft' => '#FEE2E2',
            'info' => '#0284C7',
            'on_info' => '#FFFFFF',
            'info_soft' => '#E0F2FE',
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
        'overlay' => ['--color-overlay'],
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
        'on_success' => ['--color-on-success'],
        'success_soft' => ['--color-success-soft'],
        'warning' => ['--color-yellow', '--color-warning'],
        'on_warning' => ['--color-on-warning'],
        'warning_soft' => ['--color-warning-soft'],
        'danger' => ['--color-red', '--color-danger'],
        'on_danger' => ['--color-on-danger'],
        'danger_soft' => ['--color-danger-soft'],
        'info' => ['--color-info'],
        'on_info' => ['--color-on-info'],
        'info_soft' => ['--color-info-soft'],
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

    /**
     * Public URL of the network logo.
     *
     * The file is streamed by a dedicated route instead of relying on the
     * storage symlink (which is not always available, especially on Windows),
     * so the logo is served even when `APP_URL` points to a different host.
     */
    public static function logoUrl(?Network $network): ?string
    {
        $logo = $network?->settings['logo'] ?? null;

        if (! $logo || ! Storage::disk('public')->exists($logo)) {
            return null;
        }

        return route('network.logo', [
            'network' => $network,
            'v' => substr(md5($logo), 0, 8),
        ]);
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
