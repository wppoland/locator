<?php

declare(strict_types=1);

namespace Locator\Admin;

defined('ABSPATH') || exit;

use Locator\Contract\HasHooks;

use const Locator\PLUGIN_DIR;
use const Locator\VERSION;

/**
 * Admin settings page, registered as a WooCommerce submenu.
 *
 * Stores everything under the `locator_settings` option (array): whether the
 * search box shows, and which detail fields are visible on each store card. All
 * output is escaped; all input is sanitised on save.
 */
final class Settings implements HasHooks
{
    public const OPTION = 'locator_settings';

    private const PAGE  = 'locator-settings';
    private const GROUP = 'locator_settings_group';

    /** Fields the merchant can toggle on the storefront cards. */
    private const TOGGLEABLE_FIELDS = ['address', 'hours', 'phone'];

    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(string $hook): void
    {
        $isStorePost = false;
        $screen      = function_exists('get_current_screen') ? get_current_screen() : null;
        if (null !== $screen && \Locator\PostType\StoreLocation::POST_TYPE === $screen->post_type) {
            $isStorePost = true;
        }

        if ('woocommerce_page_' . self::PAGE !== $hook && ! $isStorePost) {
            return;
        }

        wp_enqueue_style(
            'locator-admin',
            \LOCATOR_URL . 'assets/css/admin.css',
            [],
            VERSION,
        );
    }

    public function addMenuPage(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Locator — Store Locator', 'locator'),
            __('Store Locator', 'locator'),
            'manage_woocommerce',
            self::PAGE,
            [$this, 'renderPage'],
        );
    }

    public function registerSettings(): void
    {
        register_setting(
            self::GROUP,
            self::OPTION,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'sanitize'],
            ],
        );

        add_filter(
            'option_page_capability_' . self::GROUP,
            static fn (): string => 'manage_woocommerce',
        );
    }

    public function renderPage(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        $settings = $this->all();
        /** @var array<string, bool> $fields */
        $fields = is_array($settings['fields'] ?? null) ? $settings['fields'] : [];

        $fieldLabels = [
            'address' => __('Address', 'locator'),
            'hours'   => __('Opening hours', 'locator'),
            'phone'   => __('Phone', 'locator'),
        ];
        ?>
        <div class="wrap locator-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="locator-intro">
                <h2><?php esc_html_e('Show customers where to find you', 'locator'); ?></h2>
                <p>
                    <?php esc_html_e('Add your physical stores under WooCommerce → Store Locations, then place the shortcode below on any page to render a searchable, accessible directory your customers can filter by city, postcode or name.', 'locator'); ?>
                </p>
                <p>
                    <?php
                    printf(
                        /* translators: %s: the [locator] shortcode wrapped in <code>. */
                        esc_html__('Add the %s shortcode to a page to display your locations.', 'locator'),
                        '<code>[locator]</code>',
                    );
                    ?>
                </p>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields(self::GROUP); ?>

                <div class="locator-card">
                    <h2><?php esc_html_e('Display', 'locator'); ?></h2>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><?php esc_html_e('Search box', 'locator'); ?></th>
                                <td>
                                    <label for="locator_show_search">
                                        <input type="checkbox" id="locator_show_search"
                                            name="<?php echo esc_attr(self::OPTION); ?>[show_search]" value="1"
                                            <?php checked((bool) ($settings['show_search'] ?? true), true); ?> />
                                        <?php esc_html_e('Show the search box above the results.', 'locator'); ?>
                                    </label>
                                    <p class="description">
                                        <?php esc_html_e('Visitors can instantly filter locations by city, postcode or name. Filtering happens in the browser — no data leaves the page.', 'locator'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="locator-card">
                    <h2><?php esc_html_e('Fields shown on each card', 'locator'); ?></h2>
                    <p class="description">
                        <?php esc_html_e('The store name is always shown. Pick which extra details appear beneath it.', 'locator'); ?>
                    </p>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><?php esc_html_e('Visible fields', 'locator'); ?></th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text">
                                            <?php esc_html_e('Visible fields', 'locator'); ?>
                                        </legend>
                                        <?php foreach ($fieldLabels as $key => $label) :
                                            $id = 'locator_field_' . sanitize_key($key);
                                            ?>
                                            <label for="<?php echo esc_attr($id); ?>" class="locator-checkbox-row">
                                                <input type="checkbox" id="<?php echo esc_attr($id); ?>"
                                                    name="<?php echo esc_attr(self::OPTION); ?>[fields][<?php echo esc_attr($key); ?>]"
                                                    value="1" <?php checked((bool) ($fields[$key] ?? false), true); ?> />
                                                <?php echo esc_html($label); ?>
                                            </label><br />
                                        <?php endforeach; ?>
                                    </fieldset>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Sanitise the submitted settings before save.
     *
     * @param mixed $raw
     * @return array<string, mixed>
     */
    public function sanitize(mixed $raw): array
    {
        if (! is_array($raw)) {
            $raw = [];
        }

        $rawFields = isset($raw['fields']) && is_array($raw['fields']) ? $raw['fields'] : [];
        $fields    = [];
        foreach (self::TOGGLEABLE_FIELDS as $field) {
            $fields[$field] = ! empty($rawFields[$field]);
        }

        return [
            'show_search' => ! empty($raw['show_search']),
            'fields'      => $fields,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $stored = get_option(self::OPTION, []);

        if (! is_array($stored)) {
            $stored = [];
        }

        /** @var array<string, mixed> $defaults */
        $defaults = require PLUGIN_DIR . '/config/defaults.php';

        $merged = array_merge($defaults, $stored);

        // Deep-merge the fields map so newly introduced fields keep their default.
        $defaultFields = is_array($defaults['fields'] ?? null) ? $defaults['fields'] : [];
        $storedFields  = is_array($stored['fields'] ?? null) ? $stored['fields'] : [];
        $merged['fields'] = array_merge($defaultFields, $storedFields);

        return $merged;
    }
}
