<?php

namespace App\Services;

class HomepageSectionMapper
{
    /**
     * Convert section-local CMS keys into the legacy public homepage contract.
     *
     * The CMS deliberately stores simple keys such as title/text/items inside
     * each section. The public homepage, however, still consumes a flat legacy
     * array. Mapping here prevents one section's generic keys from overwriting
     * another section when HomepageContentService merges active sections.
     */
    public static function toPublic(string $sectionKey, array $data): array
    {
        $mapped = $data;

        return match ($sectionKey) {
            'hero' => self::map($mapped, [
                'image' => 'hero_image',
                'eyebrow' => 'hero_eyebrow',
                'title' => 'hero_title',
                'text' => 'hero_text',
                'button' => 'explore',
                'proofs' => 'hero_proofs',
            ], ['proofs' => ['icon', 'label', 'sub']]),

            'capabilities' => self::map($mapped, [
                'items' => 'capabilities',
            ], ['items' => ['icon', 'title', 'subtitle']]),

            'about' => self::map($mapped, [
                'kicker' => 'about_kicker',
                'title' => 'about_title',
                'text' => 'about_text',
                'button' => 'about_button',
                'points' => 'about_points',
            ], [
                'points' => ['icon', 'title', 'sub'],
                'stats' => ['value', 'label', 'icon'],
            ]),

            'services' => self::map($mapped, [
                'kicker' => 'services_kicker',
                'title' => 'services_title',
                'text' => 'services_text',
                'more' => 'services_more',
                'button' => 'services_button',
                'items' => 'services',
            ], ['items' => ['number', 'image', 'title', 'desc']]),

            'process' => self::map($mapped, [
                'kicker' => 'process_kicker',
                'title' => 'process_title',
                'items' => 'process',
            ], ['items' => ['number', 'title', 'desc']]),

            'industries' => self::map($mapped, [
                'title' => 'industries_title',
                'button' => 'industries_button',
                'items' => 'industries',
            ], ['items' => ['image', 'label']]),

            'operations' => $mapped,

            'why' => self::map($mapped, [
                'title' => 'why_title',
                'items' => 'why',
            ], ['items' => ['icon', 'title', 'desc']]),

            'showcase' => self::map($mapped, [
                'kicker' => 'showcase_kicker',
                'title' => 'showcase_title',
                'text' => 'showcase_text',
                'previous' => 'carousel_previous',
                'next' => 'carousel_next',
                'metrics' => 'showcase_metrics',
            ], ['metrics' => ['icon', 'value', 'label']]),

            'clients' => self::map($mapped, [
                'title' => 'clients_title',
                'text' => 'clients_text',
                'more' => 'more_clients',
                'button' => 'all_clients',
            ]),

            'emergency' => self::map($mapped, [
                'title' => 'emergency_title',
                'text' => 'emergency_text',
                'button' => 'emergency_button',
                'contact' => 'emergency_contact',
                'photo_alt' => 'emergency_photo_alt',
                'support' => 'operations_support',
                'call' => 'contact_now',
                'email' => 'email_us',
            ]),

            'footer_cta' => self::map($mapped, [
                'title' => 'final_title',
                'text' => 'final_text',
                'quote' => 'quote',
                'contact' => 'contact',
            ]),

            'footer' => self::map($mapped, [
                'about' => 'footer_about',
                'contact_lines' => 'footer_contact',
            ]),

            default => $mapped,
        };
    }

    private static function map(array $data, array $aliases, array $listFields = []): array
    {
        foreach ($aliases as $source => $target) {
            if (! array_key_exists($source, $data)) {
                continue;
            }

            $value = $data[$source];
            if (isset($listFields[$source]) && is_array($value)) {
                $value = self::normalizeRows($value, $listFields[$source]);
            }

            // A section-specific/legacy key already stored in the same record
            // wins over the generic alias. This keeps existing production data
            // stable while the CMS is migrated incrementally.
            if (! array_key_exists($target, $data) || $target === $source) {
                $data[$target] = $value;
            }

            if ($target !== $source) {
                unset($data[$source]);
            }
        }

        // Some lists keep their public key but still need conversion from the
        // editor's associative rows to the positional arrays used by old views.
        foreach ($listFields as $field => $columns) {
            if (isset($aliases[$field])) {
                continue;
            }
            if (array_key_exists($field, $data) && is_array($data[$field])) {
                $data[$field] = self::normalizeRows($data[$field], $columns);
            }
        }

        return $data;
    }

    private static function normalizeRows(array $rows, array $columns): array
    {
        return array_values(array_map(function ($row) use ($columns) {
            if (! is_array($row)) {
                return $row;
            }

            // Already positional: keep it exactly as stored.
            if (array_is_list($row)) {
                return array_values($row);
            }

            return array_map(fn ($column) => $row[$column] ?? '', $columns);
        }, $rows));
    }
}
