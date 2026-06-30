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
            __('Locator: Store Locator', 'plogins-locator'),
            __('Store Locator', 'plogins-locator'),
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
            'address' => [
                'label' => __('Address', 'plogins-locator'),
                'help'  => __('Adds the street, postcode, city and country block to each card.', 'plogins-locator'),
            ],
            'hours'   => [
                'label' => __('Opening hours', 'plogins-locator'),
                'help'  => __('Shows the hours you entered for the store, so customers know when to visit.', 'plogins-locator'),
            ],
            'phone'   => [
                'label' => __('Phone', 'plogins-locator'),
                'help'  => __('Shows a click-to-call number, tapping it dials the store on mobile.', 'plogins-locator'),
            ],
        ];
        ?>
        <div class="wrap locator-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="locator-intro">
                <h2><?php esc_html_e('Show customers where to find you', 'plogins-locator'); ?></h2>
                <p>
                    <?php esc_html_e('Add your physical stores under WooCommerce → Store Locations, then place the shortcode below on any page to render a searchable, accessible directory your customers can filter by city, postcode or name.', 'plogins-locator'); ?>
                </p>
                <p class="locator-shortcode-hint">
                    <?php
                    printf(
                        /* translators: %s: the [locator] shortcode wrapped in <code>. */
                        esc_html__('Add the %s shortcode to a page to display your locations.', 'plogins-locator'),
                        '<code>[locator]</code>',
                    );
                    ?>
                </p>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields(self::GROUP); ?>

                <div class="locator-card">
                    <h2 class="locator-card__title"><?php esc_html_e('Search', 'plogins-locator'); ?></h2>
                    <p class="locator-card__intro">
                        <?php esc_html_e('Help customers narrow a long list to the store nearest them.', 'plogins-locator'); ?>
                    </p>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><?php esc_html_e('Search box', 'plogins-locator'); ?></th>
                                <td>
                                    <label for="locator_show_search">
                                        <input type="checkbox" id="locator_show_search"
                                            name="<?php echo esc_attr(self::OPTION); ?>[show_search]" value="1"
                                            <?php checked((bool) ($settings['show_search'] ?? true), true); ?> />
                                        <?php esc_html_e('Show the search box above the results.', 'plogins-locator'); ?>
                                    </label>
                                    <p class="description">
                                        <?php esc_html_e('Visitors filter locations as they type, by city, postcode or name. Filtering happens in the browser, so no data leaves the page. Leave off if you only list a handful of stores.', 'plogins-locator'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="locator-card">
                    <h2 class="locator-card__title"><?php esc_html_e('Fields shown on each card', 'plogins-locator'); ?></h2>
                    <p class="locator-card__intro">
                        <?php esc_html_e('The store name is always shown. Choose which extra details appear beneath it, each one is only rendered when that store actually has a value.', 'plogins-locator'); ?>
                    </p>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><?php esc_html_e('Visible fields', 'plogins-locator'); ?></th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text">
                                            <?php esc_html_e('Visible fields', 'plogins-locator'); ?>
                                        </legend>
                                        <?php foreach ($fieldLabels as $key => $field) :
                                            $id = 'locator_field_' . sanitize_key($key);
                                            ?>
                                            <div class="locator-field-row">
                                                <label for="<?php echo esc_attr($id); ?>" class="locator-checkbox-row">
                                                    <input type="checkbox" id="<?php echo esc_attr($id); ?>"
                                                        name="<?php echo esc_attr(self::OPTION); ?>[fields][<?php echo esc_attr($key); ?>]"
                                                        value="1" <?php checked((bool) ($fields[$key] ?? false), true); ?> />
                                                    <span class="locator-field-row__label"><?php echo esc_html($field['label']); ?></span>
                                                </label>
                                                <p class="description locator-field-row__help">
                                                    <?php echo esc_html($field['help']); ?>
                                                </p>
                                            </div>
                                        <?php endforeach; ?>
                                    </fieldset>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="locator-preview" aria-hidden="true">
                        <span class="locator-preview__label"><?php esc_html_e('Example card', 'plogins-locator'); ?></span>
                        <div class="locator-preview__card">
                            <span class="locator-preview__pin">
                                <svg viewBox="0 0 24 24" width="18" height="18" focusable="false" aria-hidden="true">
                                    <path d="M12 2a7 7 0 0 0-7 7c0 4.8 6.2 12.2 6.5 12.5a.7.7 0 0 0 1 0C12.8 21.2 19 13.8 19 9a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z" />
                                </svg>
                            </span>
                            <div class="locator-preview__body">
                                <strong class="locator-preview__name"><?php esc_html_e('Riverside Store', 'plogins-locator'); ?></strong>
                                <?php if (! empty($fields['address'])) : ?>
                                    <span class="locator-preview__line"><?php esc_html_e('12 Mill Lane, EC1A 1BB London', 'plogins-locator'); ?></span>
                                <?php endif; ?>
                                <?php if (! empty($fields['hours'])) : ?>
                                    <span class="locator-preview__line"><?php esc_html_e('Mon–Sat 9:00–18:00', 'plogins-locator'); ?></span>
                                <?php endif; ?>
                                <?php if (! empty($fields['phone'])) : ?>
                                    <span class="locator-preview__line locator-preview__line--accent"><?php esc_html_e('+44 20 7946 0000', 'plogins-locator'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="description locator-preview__note">
                            <?php esc_html_e('A live page also adds your search box and shows every store that matches.', 'plogins-locator'); ?>
                        </p>
                    </div>
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
