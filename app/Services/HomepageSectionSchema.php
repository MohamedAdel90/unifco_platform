<?php

namespace App\Services;

class HomepageSectionSchema
{
    public static function fields(string $sectionKey): array
    {
        return match ($sectionKey) {
            'hero' => [
                'scalars' => ['eyebrow', 'title', 'text', 'button'],
                'items' => [
                    'proofs' => ['icon', 'label', 'sub'],
                ],
            ],
            'capabilities' => [
                'scalars' => [],
                'items' => [
                    'items' => ['icon', 'title', 'subtitle'],
                ],
            ],
            'about' => [
                'scalars' => ['kicker', 'title', 'text', 'button'],
                'items' => [
                    'points' => ['icon', 'title', 'sub'],
                    'stats' => ['value', 'label', 'icon'],
                ],
            ],
            'services' => [
                'scalars' => ['kicker', 'title', 'text', 'more', 'button'],
                'items' => [
                    'items' => ['number', 'image', 'title', 'desc'],
                ],
            ],
            'process' => [
                'scalars' => ['kicker', 'title'],
                'items' => [
                    'items' => ['number', 'title', 'desc'],
                ],
            ],
            'industries' => [
                'scalars' => ['title', 'button'],
                'items' => [
                    'items' => ['image', 'label'],
                ],
            ],
            'operations' => [
                'scalars' => ['maintenance_title', 'maintenance_text', 'maintenance_button', 'portal_title', 'portal_text', 'portal_button'],
                'checks' => ['maintenance_checks', 'portal_checks'],
            ],
            'why' => [
                'scalars' => ['title'],
                'items' => [
                    'items' => ['icon', 'title', 'desc'],
                ],
            ],
            'showcase' => [
                'scalars' => ['kicker', 'title', 'text', 'previous', 'next'],
                'items' => [
                    'metrics' => ['icon', 'value', 'label'],
                ],
            ],
            'clients' => [
                'scalars' => ['title', 'text', 'more', 'button'],
            ],
            'emergency' => [
                'scalars' => ['title', 'text', 'button', 'contact', 'photo_alt', 'support', 'call', 'email'],
            ],
            'footer_cta' => [
                'scalars' => ['title', 'text', 'quote', 'contact'],
            ],
            'footer' => [
                'scalars' => ['about', 'company', 'services_label', 'contact_label'],
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
        $suffix = ['_ar', '_en'];

        return str_ends_with($field, '_ar') || str_ends_with($field, '_en');
    }
}
