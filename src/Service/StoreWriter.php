<?php

declare(strict_types=1);

namespace Locator\Service;

defined('ABSPATH') || exit;

use Locator\PostType\StoreLocation;

/**
 * Creates or updates store locations programmatically (CSV import, add-ons).
 */
final class StoreWriter
{
    /**
     * @param array<string, string> $fields
     */
    public function import(array $fields, int $postId = 0): int
    {
        /**
         * Filters store fields before a programmatic import creates or updates a location.
         *
         * @param array<string, string> $fields
         * @param int                   $postId Existing post ID when updating, otherwise 0.
         */
        $fields = apply_filters('locator/import_store_fields', $fields, $postId);

        if (! is_array($fields)) {
            return 0;
        }

        $title = sanitize_text_field((string) ($fields['name'] ?? ''));

        if ('' === $title) {
            return 0;
        }

        $postData = [
            'post_type'    => StoreLocation::POST_TYPE,
            'post_status'  => 'publish',
            'post_title'   => $title,
            'post_content' => wp_kses_post((string) ($fields['description'] ?? '')),
        ];

        if ($postId > 0) {
            $existing = get_post($postId);

            if (! $existing instanceof \WP_Post || StoreLocation::POST_TYPE !== $existing->post_type) {
                return 0;
            }

            $postData['ID'] = $postId;
            $result         = wp_update_post($postData, true);
        } else {
            $result = wp_insert_post($postData, true);
        }

        if (is_wp_error($result) || ! is_int($result) || $result <= 0) {
            return 0;
        }

        $this->saveMeta($result, $fields);

        /**
         * Fires after a store location is created or updated programmatically.
         *
         * @param int                   $postId Imported store post ID.
         * @param array<string, string> $fields Sanitized import fields.
         */
        do_action('locator/store_imported', $result, $fields);

        return $result;
    }

    /**
     * @param array<string, string> $fields
     */
    private function saveMeta(int $postId, array $fields): void
    {
        $textMap = [
            'city'     => StoreLocation::META_CITY,
            'postcode' => StoreLocation::META_POSTCODE,
            'country'  => StoreLocation::META_COUNTRY,
            'phone'    => StoreLocation::META_PHONE,
        ];

        foreach ($textMap as $key => $metaKey) {
            $value = isset($fields[$key]) ? sanitize_text_field((string) $fields[$key]) : '';
            update_post_meta($postId, $metaKey, $value);
        }

        $email = isset($fields['email']) ? sanitize_email((string) $fields['email']) : '';
        update_post_meta($postId, StoreLocation::META_EMAIL, $email);

        $address = isset($fields['address']) ? sanitize_textarea_field((string) $fields['address']) : '';
        update_post_meta($postId, StoreLocation::META_ADDRESS, $address);

        $hours = isset($fields['hours']) ? sanitize_textarea_field((string) $fields['hours']) : '';
        update_post_meta($postId, StoreLocation::META_HOURS, $hours);

        $this->saveCoordinate($postId, StoreLocation::META_LAT, $fields['lat'] ?? '', -90.0, 90.0);
        $this->saveCoordinate($postId, StoreLocation::META_LNG, $fields['lng'] ?? '', -180.0, 180.0);
    }

    private function saveCoordinate(int $postId, string $metaKey, string $raw, float $min, float $max): void
    {
        $raw = trim($raw);

        if ('' === $raw || ! is_numeric($raw)) {
            delete_post_meta($postId, $metaKey);
            return;
        }

        $value = (float) $raw;

        if ($value < $min || $value > $max) {
            delete_post_meta($postId, $metaKey);
            return;
        }

        update_post_meta($postId, $metaKey, (string) $value);
    }
}
