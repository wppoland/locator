<?php

declare(strict_types=1);

namespace Locator\Repository;

defined('ABSPATH') || exit;

use Locator\Model\Store;
use Locator\PostType\StoreLocation;
use WP_Post;
use WP_Query;

/**
 * Reads published store locations from the CPT and hydrates them into Store value
 * objects. Uses WP_Query / post meta only — no custom tables, no external API.
 */
final class StoreRepository
{
    /**
     * Return all published stores, ordered by menu_order then title.
     *
     * @param int $limit Maximum number of stores to return (-1 for all).
     * @return list<Store>
     */
    public function all(int $limit = -1): array
    {
        $query = new WP_Query([
            'post_type'              => StoreLocation::POST_TYPE,
            'post_status'            => 'publish',
            'posts_per_page'         => $limit,
            'orderby'                => ['menu_order' => 'ASC', 'title' => 'ASC'],
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
            'ignore_sticky_posts'    => true,
        ]);

        $stores = [];

        foreach ($query->posts as $post) {
            if ($post instanceof WP_Post) {
                $stores[] = $this->hydrate($post);
            }
        }

        return $stores;
    }

    /**
     * Count published stores.
     */
    public function count(): int
    {
        $query = new WP_Query([
            'post_type'              => StoreLocation::POST_TYPE,
            'post_status'            => 'publish',
            'posts_per_page'         => 1,
            'fields'                 => 'ids',
            'no_found_rows'          => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'ignore_sticky_posts'    => true,
        ]);

        return (int) $query->found_posts;
    }

    private function hydrate(WP_Post $post): Store
    {
        $thumbnailUrl = (string) get_the_post_thumbnail_url($post, 'medium');

        return new Store(
            id: $post->ID,
            name: get_the_title($post),
            description: (string) $post->post_content,
            address: (string) get_post_meta($post->ID, StoreLocation::META_ADDRESS, true),
            city: (string) get_post_meta($post->ID, StoreLocation::META_CITY, true),
            postcode: (string) get_post_meta($post->ID, StoreLocation::META_POSTCODE, true),
            country: (string) get_post_meta($post->ID, StoreLocation::META_COUNTRY, true),
            phone: (string) get_post_meta($post->ID, StoreLocation::META_PHONE, true),
            email: (string) get_post_meta($post->ID, StoreLocation::META_EMAIL, true),
            hours: (string) get_post_meta($post->ID, StoreLocation::META_HOURS, true),
            lat: $this->coordinate($post->ID, StoreLocation::META_LAT),
            lng: $this->coordinate($post->ID, StoreLocation::META_LNG),
            thumbnailUrl: $thumbnailUrl,
        );
    }

    /**
     * Read a coordinate meta value as a nullable float. Returns null when the
     * meta is absent or not a valid number, so un-geocoded stores stay null.
     */
    private function coordinate(int $postId, string $metaKey): ?float
    {
        $raw = get_post_meta($postId, $metaKey, true);

        if ('' === $raw || null === $raw || ! is_numeric($raw)) {
            return null;
        }

        return (float) $raw;
    }
}
