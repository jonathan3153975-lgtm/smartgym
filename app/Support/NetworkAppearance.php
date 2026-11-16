<?php

namespace App\Support;

use App\Models\Network;

class NetworkAppearance
{
    /**
     * @var array<string, array<string, mixed>>
     */
    public const DEFAULTS = [
        'menu' => [
            'show_name' => true,
            'center_logo' => true,
            'logo_height' => 40,
            'background' => '#0D1B30',
            'border' => '#263B57',
            'text' => '#F8FAFC',
            'sidebar_background' => '#0D1B30',
            'sidebar_text' => '#A9B8CC',
            'hover_background' => '#142944',
            'hover_text' => '#F8FAFC',
            'active_background' => '#142944',
            'active_text' => '#0D6EFD',
        ],
        'login' => [
            'eyebrow' => 'Acesso do aluno',
            'headline' => 'Seu treino, sua evolução, no seu ritmo.',
            'description' => 'Acesse seus treinos, acompanhe suas medidas e converse com a equipe da sua unidade.',
            'welcome_title' => 'Bem-vindo de volta',
            'welcome_subtitle' => 'Entre com seu e-mail e senha para acessar o portal.',
            'button_text' => 'Entrar',
            'logo_height' => 80,
            'background' => '#071426',
            'panel' => '#0D1B30',
            'text' => '#F8FAFC',
            'muted' => '#A9B8CC',
            'accent' => '#0D6EFD',
        ],
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function forNetwork(?Network $network): array
    {
        $stored = $network?->settings['appearance'] ?? [];
        $defaults = self::DEFAULTS;
        $mode = NetworkTheme::mode($network) === 'light' ? 'light' : 'dark';
        $palette = NetworkTheme::forNetwork($network)[$mode];

        $defaults['menu']['background'] = $palette['surface'];
        $defaults['menu']['border'] = $palette['border'];
        $defaults['menu']['text'] = $palette['text_primary'];
        $defaults['menu']['sidebar_background'] = $palette['surface'];
        $defaults['menu']['sidebar_text'] = $palette['text_secondary'];
        $defaults['menu']['hover_background'] = $palette['surface_elevated'];
        $defaults['menu']['hover_text'] = $palette['text_primary'];
        $defaults['menu']['active_background'] = $palette['surface_elevated'];
        $defaults['menu']['active_text'] = $palette['primary'];
        $defaults['login']['background'] = $palette['background_primary'];
        $defaults['login']['panel'] = $palette['surface'];
        $defaults['login']['text'] = $palette['text_primary'];
        $defaults['login']['muted'] = $palette['text_secondary'];
        $defaults['login']['accent'] = $palette['primary'];

        return [
            'menu' => self::normalizeMenu($stored['menu'] ?? [], $defaults['menu']),
            'login' => self::normalizeLogin($stored['login'] ?? [], $defaults['login']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function menu(?Network $network): array
    {
        return self::forNetwork($network)['menu'];
    }

    /**
     * @return array<string, mixed>
     */
    public static function login(?Network $network): array
    {
        return self::forNetwork($network)['login'];
    }

    /**
     * @param  array<string, mixed>  $stored
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    private static function normalizeMenu(array $stored, array $defaults): array
    {
        return [
            'show_name' => filter_var($stored['show_name'] ?? $defaults['show_name'], FILTER_VALIDATE_BOOL),
            'center_logo' => filter_var($stored['center_logo'] ?? $defaults['center_logo'], FILTER_VALIDATE_BOOL),
            'logo_height' => self::clampInt($stored['logo_height'] ?? $defaults['logo_height'], 24, 120),
            'background' => self::hex($stored['background'] ?? null, $defaults['background']),
            'border' => self::hex($stored['border'] ?? null, $defaults['border']),
            'text' => self::hex($stored['text'] ?? null, $defaults['text']),
            'sidebar_background' => self::hex($stored['sidebar_background'] ?? null, $defaults['sidebar_background']),
            'sidebar_text' => self::hex($stored['sidebar_text'] ?? null, $defaults['sidebar_text']),
            'hover_background' => self::hex($stored['hover_background'] ?? null, $defaults['hover_background']),
            'hover_text' => self::hex($stored['hover_text'] ?? null, $defaults['hover_text']),
            'active_background' => self::hex($stored['active_background'] ?? null, $defaults['active_background']),
            'active_text' => self::hex($stored['active_text'] ?? null, $defaults['active_text']),
        ];
    }

    /**
     * @param  array<string, mixed>  $stored
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    private static function normalizeLogin(array $stored, array $defaults): array
    {
        return [
            'eyebrow' => self::text($stored['eyebrow'] ?? null, $defaults['eyebrow'], 80),
            'headline' => self::text($stored['headline'] ?? null, $defaults['headline'], 180),
            'description' => self::text($stored['description'] ?? null, $defaults['description'], 400),
            'welcome_title' => self::text($stored['welcome_title'] ?? null, $defaults['welcome_title'], 120),
            'welcome_subtitle' => self::text($stored['welcome_subtitle'] ?? null, $defaults['welcome_subtitle'], 240),
            'button_text' => self::text($stored['button_text'] ?? null, $defaults['button_text'], 40),
            'logo_height' => self::clampInt($stored['logo_height'] ?? $defaults['logo_height'], 32, 180),
            'background' => self::hex($stored['background'] ?? null, $defaults['background']),
            'panel' => self::hex($stored['panel'] ?? null, $defaults['panel']),
            'text' => self::hex($stored['text'] ?? null, $defaults['text']),
            'muted' => self::hex($stored['muted'] ?? null, $defaults['muted']),
            'accent' => self::hex($stored['accent'] ?? null, $defaults['accent']),
        ];
    }

    private static function hex(mixed $value, string $fallback): string
    {
        return is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1
            ? $value
            : $fallback;
    }

    private static function text(mixed $value, string $fallback, int $maxLength): string
    {
        $text = trim((string) ($value ?? ''));

        return $text !== '' ? mb_substr($text, 0, $maxLength) : $fallback;
    }

    private static function clampInt(mixed $value, int $min, int $max): int
    {
        return max($min, min($max, (int) $value));
    }
}
