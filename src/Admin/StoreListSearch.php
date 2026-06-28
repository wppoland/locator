<?php

declare(strict_types=1);

namespace Locator\Admin;

defined('ABSPATH') || exit;

use Locator\Contract\HasHooks;
use Locator\PostType\StoreLocation;
use WP_Query;

/**
 * Makes the Store Locations admin list search match location meta, not just the
 * title. By default WordPress only searches post_title/post_content, so an admin
 * with many stores cannot find one by city, postcode, phone or email. This
 * extends the admin list-table search (and nothing else) to also match those
 * fields, via the standard posts_join / posts_search / posts_distinct filters.
 */
final class StoreListSearch implements HasHooks
{
    private const JOIN_ALIAS = 'locator_search_meta';

    /** Meta keys included in the admin search. */
    private const SEARCH_META = [
        StoreLocation::META_CITY,
        StoreLocation::META_POSTCODE,
        StoreLocation::META_ADDRESS,
        StoreLocation::META_COUNTRY,
        StoreLocation::META_PHONE,
        StoreLocation::META_EMAIL,
    ];

    public function registerHooks(): void
    {
        add_filter('posts_join', [$this, 'join'], 10, 2);
        add_filter('posts_search', [$this, 'search'], 10, 2);
        add_filter('posts_distinct', [$this, 'distinct'], 10, 2);
    }

    /**
     * Only the admin Store Locations list query, when an actual search term is
     * present. Everything else (front end, other post types, sub-queries) is
     * left untouched.
     */
    private function applies(WP_Query $query): bool
    {
        return is_admin()
            && $query->is_main_query()
            && $query->is_search()
            && StoreLocation::POST_TYPE === $query->get('post_type')
            && '' !== trim((string) $query->get('s'));
    }

    public function join(string $join, WP_Query $query): string
    {
        global $wpdb;

        if (! $this->applies($query)) {
            return $join;
        }

        $alias = self::JOIN_ALIAS;

        return $join . " LEFT JOIN {$wpdb->postmeta} AS {$alias} ON ({$wpdb->posts}.ID = {$alias}.post_id) ";
    }

    /**
     * Replace the default search clause with one that also matches our meta.
     */
    public function search(string $search, WP_Query $query): string
    {
        global $wpdb;

        if (! $this->applies($query)) {
            return $search;
        }

        $alias = self::JOIN_ALIAS;

        // A single, safely quoted LIKE literal (e.g. '%krakow%') we can reuse.
        $like = '%' . $wpdb->esc_like((string) $query->get('s')) . '%';
        $likeSql = $wpdb->prepare('%s', $like);

        // Meta keys are our own constants (no user input); quote them safely.
        $keys = "'" . implode("', '", array_map('esc_sql', self::SEARCH_META)) . "'";

        return " AND ( {$wpdb->posts}.post_title LIKE {$likeSql}"
            . " OR {$wpdb->posts}.post_content LIKE {$likeSql}"
            . " OR ( {$alias}.meta_key IN ({$keys}) AND {$alias}.meta_value LIKE {$likeSql} ) ) ";
    }

    public function distinct(string $distinct, WP_Query $query): string
    {
        if (! $this->applies($query)) {
            return $distinct;
        }

        return 'DISTINCT';
    }
}
