<?php

declare(strict_types=1);

/**
 * Plugin Name:       Plogins Locator - Store Locator for WooCommerce
 * Plugin URI:        https://plogins.com/plogins-locator/
 * Description:        Show your physical store locations with a searchable list customers can filter by area.
 * Version:           0.1.4
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Tested up to:      7.0
 * Author:            WPPoland.com
 * Author URI:        https://wppoland.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       plogins-locator
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 *
 * WC requires at least: 8.0
 * WC tested up to:      9.6
 *
 * @package Locator
 */

namespace Locator;

defined('ABSPATH') || exit;

const VERSION         = '0.1.4';
const PLUGIN_FILE     = __FILE__;
const PLUGIN_DIR      = __DIR__;
const MIN_PHP_VERSION = '8.1.0';

define('LOCATOR_DIR', plugin_dir_path(__FILE__));
define('LOCATOR_URL', plugin_dir_url(__FILE__));

/**
 * Declare WooCommerce HPOS (Custom Order Tables) compatibility.
 */
add_action('before_woocommerce_init', static function (): void {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', PLUGIN_FILE, true);
    }
});

/**
 * Require PHP 8.1+ before doing anything else.
 */
if (version_compare(PHP_VERSION, MIN_PHP_VERSION, '<')) {
    add_action('admin_notices', static function (): void {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html(sprintf(
                /* translators: 1: Required PHP version, 2: Current PHP version */
                __('Locator requires PHP %1$s or higher. You are running PHP %2$s.', 'plogins-locator'),
                MIN_PHP_VERSION,
                PHP_VERSION,
            )),
        );
    });
    return;
}

require_once __DIR__ . '/autoload.php';

/**
 * Boot once WooCommerce is confirmed present.
 */
add_action('plugins_loaded', static function (): void {
    if (! class_exists('WooCommerce')) {
        add_action('admin_notices', static function (): void {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                esc_html__('Locator requires WooCommerce to be installed and activated.', 'plogins-locator'),
            );
        });
        return;
    }

    // Boot on init:0 so the CPT, shortcode and translations register at the
    // correct, translation-safe moment.
    add_action('init', static function (): void {
        Plugin::instance()->boot();
    }, 0);
}, 10);

register_activation_hook(PLUGIN_FILE, static function (): void {
    require_once PLUGIN_DIR . '/autoload.php';
    Plugin::instance()->container()->get(Migrator::class)->maybeMigrate();
});
