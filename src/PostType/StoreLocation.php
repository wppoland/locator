<?php

declare(strict_types=1);

namespace Locator\PostType;

defined('ABSPATH') || exit;

use Locator\Contract\HasHooks;
use WP_Post;

/**
 * The custom post type that stores physical store locations.
 *
 * Locations are managed in wp-admin under the WooCommerce menu and surfaced on
 * the storefront via the [locator] shortcode. Each location keeps its address,
 * contact details and opening hours as post meta. All meta is sanitised on save
 * behind a nonce + manage_woocommerce capability check.
 */
final class StoreLocation implements HasHooks
{
    public const POST_TYPE = 'locator_store';

    public const META_ADDRESS  = '_locator_address';
    public const META_CITY     = '_locator_city';
    public const META_POSTCODE = '_locator_postcode';
    public const META_COUNTRY  = '_locator_country';
    public const META_PHONE    = '_locator_phone';
    public const META_HOURS    = '_locator_hours';

    private const NONCE_ACTION = 'locator_save_store';
    private const NONCE_FIELD  = 'locator_store_nonce';

    public function registerHooks(): void
    {
        $this->register();

        if (is_admin()) {
            add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'columns']);
            add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'renderColumn'], 10, 2);
            add_action('add_meta_boxes', [$this, 'addMetaBox']);
            add_action('save_post_' . self::POST_TYPE, [$this, 'saveMeta'], 10, 2);
        }
    }

    /**
     * Register the post type. Called directly during boot (on init) as well, so
     * it is available immediately for the shortcode and admin UI.
     */
    public function register(): void
    {
        if (post_type_exists(self::POST_TYPE)) {
            return;
        }

        register_post_type(
            self::POST_TYPE,
            [
                'labels'              => [
                    'name'               => __('Store Locations', 'locator'),
                    'singular_name'      => __('Store Location', 'locator'),
                    'menu_name'          => __('Store Locations', 'locator'),
                    'add_new'            => __('Add Location', 'locator'),
                    'add_new_item'       => __('Add Store Location', 'locator'),
                    'new_item'           => __('New Store Location', 'locator'),
                    'edit_item'          => __('Edit Store Location', 'locator'),
                    'view_item'          => __('View Store Location', 'locator'),
                    'all_items'          => __('Store Locations', 'locator'),
                    'search_items'       => __('Search store locations', 'locator'),
                    'not_found'          => __('No store locations found.', 'locator'),
                    'not_found_in_trash' => __('No store locations in Trash.', 'locator'),
                ],
                'public'              => false,
                'show_ui'             => true,
                'show_in_menu'        => 'woocommerce',
                'show_in_rest'        => false,
                'exclude_from_search' => true,
                'publicly_queryable'  => false,
                'has_archive'         => false,
                'rewrite'             => false,
                'query_var'           => false,
                'hierarchical'        => false,
                'menu_icon'           => 'dashicons-store',
                'supports'            => ['title', 'page-attributes'],
                'capability_type'     => 'post',
                'map_meta_cap'        => true,
                'capabilities'        => [
                    'edit_post'          => 'manage_woocommerce',
                    'read_post'          => 'manage_woocommerce',
                    'delete_post'        => 'manage_woocommerce',
                    'edit_posts'         => 'manage_woocommerce',
                    'edit_others_posts'  => 'manage_woocommerce',
                    'publish_posts'      => 'manage_woocommerce',
                    'read_private_posts' => 'manage_woocommerce',
                    'create_posts'       => 'manage_woocommerce',
                ],
            ],
        );
    }

    /**
     * @param array<string, string> $columns
     * @return array<string, string>
     */
    public function columns(array $columns): array
    {
        $reordered = [];

        foreach ($columns as $key => $label) {
            if ('date' === $key) {
                $reordered['locator_city']  = __('City', 'locator');
                $reordered['locator_phone'] = __('Phone', 'locator');
            }

            $reordered[$key] = $label;
        }

        return $reordered;
    }

    public function renderColumn(string $column, int $postId): void
    {
        switch ($column) {
            case 'locator_city':
                $city     = (string) get_post_meta($postId, self::META_CITY, true);
                $postcode = (string) get_post_meta($postId, self::META_POSTCODE, true);
                $parts    = array_filter([$postcode, $city]);
                echo esc_html('' !== implode(' ', $parts) ? implode(' ', $parts) : '—');
                break;

            case 'locator_phone':
                $phone = (string) get_post_meta($postId, self::META_PHONE, true);
                echo esc_html('' !== $phone ? $phone : '—');
                break;
        }
    }

    public function addMetaBox(): void
    {
        add_meta_box(
            'locator_store_details',
            __('Location details', 'locator'),
            [$this, 'renderMetaBox'],
            self::POST_TYPE,
            'normal',
            'high',
        );
    }

    public function renderMetaBox(WP_Post $post): void
    {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);

        $fields = [
            self::META_ADDRESS  => [__('Street address', 'locator'), 'textarea'],
            self::META_CITY     => [__('City', 'locator'), 'text'],
            self::META_POSTCODE => [__('Postcode / ZIP', 'locator'), 'text'],
            self::META_COUNTRY  => [__('Country', 'locator'), 'text'],
            self::META_PHONE    => [__('Phone', 'locator'), 'text'],
            self::META_HOURS    => [__('Opening hours', 'locator'), 'textarea'],
        ];
        ?>
        <table class="form-table locator-meta" role="presentation">
            <tbody>
            <?php foreach ($fields as $metaKey => [$label, $type]) :
                $value = (string) get_post_meta($post->ID, $metaKey, true);
                $id    = 'field-' . sanitize_key($metaKey);
                ?>
                <tr>
                    <th scope="row">
                        <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label>
                    </th>
                    <td>
                        <?php if ('textarea' === $type) : ?>
                            <textarea id="<?php echo esc_attr($id); ?>" class="large-text" rows="3"
                                name="<?php echo esc_attr($metaKey); ?>"><?php echo esc_textarea($value); ?></textarea>
                            <?php if (self::META_HOURS === $metaKey) : ?>
                                <p class="description">
                                    <?php esc_html_e('One line per day, e.g. "Mon–Fri: 9:00–18:00".', 'locator'); ?>
                                </p>
                            <?php endif; ?>
                        <?php else : ?>
                            <input type="text"
                                id="<?php echo esc_attr($id); ?>" class="regular-text"
                                name="<?php echo esc_attr($metaKey); ?>"
                                value="<?php echo esc_attr($value); ?>" />
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Persist the meta box fields. Guards on nonce + capability + autosave.
     */
    public function saveMeta(int $postId, WP_Post $post): void
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($postId)) {
            return;
        }

        $nonce = isset($_POST[self::NONCE_FIELD])
            ? sanitize_text_field(wp_unslash((string) $_POST[self::NONCE_FIELD]))
            : '';

        if ('' === $nonce || ! wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            return;
        }

        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        $textFields = [
            self::META_CITY,
            self::META_POSTCODE,
            self::META_COUNTRY,
            self::META_PHONE,
        ];

        foreach ($textFields as $key) {
            $raw = isset($_POST[$key]) ? sanitize_text_field(wp_unslash((string) $_POST[$key])) : '';
            update_post_meta($postId, $key, $raw);
        }

        $address = isset($_POST[self::META_ADDRESS])
            ? sanitize_textarea_field(wp_unslash((string) $_POST[self::META_ADDRESS]))
            : '';
        update_post_meta($postId, self::META_ADDRESS, $address);

        $hours = isset($_POST[self::META_HOURS])
            ? sanitize_textarea_field(wp_unslash((string) $_POST[self::META_HOURS]))
            : '';
        update_post_meta($postId, self::META_HOURS, $hours);
    }
}
