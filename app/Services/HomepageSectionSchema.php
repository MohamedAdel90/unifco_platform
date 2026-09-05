<?php

namespace App\Services;

class HomepageSectionSchema
{
    public static function fields(string $sectionKey): array
    {
        return match ($sectionKey) {
            'hero' => [
                'scalars' => ['display_mode', 'full_section_image', 'image', 'eyebrow', 'title', 'text', 'button'],
                'items' => [
                    'proofs' => ['icon', 'label', 'sub'],
                ],
            ],
            'capabilities' => [
                'scalars' => ['display_mode', 'full_section_image'],
                'items' => [
                    'items' => ['icon', 'title', 'subtitle'],
                ],
            ],
            'about' => [
                'scalars' => ['display_mode', 'full_section_image', 'image', 'kicker', 'title', 'text', 'button'],
                'items' => [
                    'profile_images' => ['image'],
                    'points' => ['icon', 'title', 'sub'],
                    'stats' => ['value', 'label', 'icon'],
                ],
            ],
            'services' => [
                'scalars' => ['display_mode', 'full_section_image', 'kicker', 'title', 'text', 'more', 'button'],
                'items' => [
                    'items' => ['number', 'image', 'title', 'desc'],
                ],
            ],
            'process' => [
                'scalars' => ['display_mode', 'full_section_image', 'kicker', 'title'],
                'items' => [
                    'items' => ['number', 'title', 'desc'],
                ],
            ],
            'industries' => [
                'scalars' => ['display_mode', 'full_section_image', 'title', 'button'],
                'items' => [
                    'items' => ['image', 'label'],
                ],
            ],
            'operations' => [
                'scalars' => [
                    'maintenance_display_mode', 'maintenance_full_section_image',
                    'maintenance_image', 'maintenance_title', 'maintenance_text', 'maintenance_button',
                    'portal_display_mode', 'portal_full_section_image',
                    'portal_image', 'portal_title', 'portal_text', 'portal_button',
                ],
                'checks' => ['maintenance_checks', 'portal_checks'],
            ],
            'why' => [
                'scalars' => ['display_mode', 'full_section_image', 'title'],
                'items' => [
                    'items' => ['icon', 'title', 'desc'],
                ],
            ],
            'showcase' => [
                'scalars' => ['display_mode', 'full_section_image', 'kicker', 'title', 'text', 'previous', 'next'],
                'items' => [
                    'metrics' => ['icon', 'value', 'label'],
                ],
            ],
            'clients' => [
                'scalars' => ['display_mode', 'full_section_image', 'title', 'text', 'more', 'button'],
                'items' => [
                    'logos' => ['image'],
                ],
            ],
            'emergency' => [
                'scalars' => ['display_mode', 'full_section_image', 'title', 'text', 'button', 'contact', 'photo_alt', 'support', 'call', 'email'],
            ],
            'footer_cta' => [
                'scalars' => ['display_mode', 'full_section_image', 'title', 'text', 'quote', 'contact'],
            ],
            'footer' => [
                'scalars' => ['display_mode', 'full_section_image', 'about', 'company', 'services_label', 'contact_label'],
                'checks' => ['contact_lines'],
            ],
            default => ['scalars' => [], 'items' => []],
        };
    }

    public static function isRepeater(string $sectionKey, string $field): bool
    {
        $schema = self::fields($sectionKey);

        return isset($schema['items'][$field]) || in_array($field, $schema['checks'] ?? [], true);
    }

    public static function itemFields(string $sectionKey, string $field): array
    {
        $schema = self::fields($sectionKey);
        if (isset($schema['items'][$field])) {
            return $schema['items'][$field];
        }

        return [];
    }

    public static function localized(string $field): bool
    {
        return str_ends_with($field, '_ar') || str_ends_with($field, '_en');
    }
}
