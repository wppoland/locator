<?php

declare(strict_types=1);

namespace Locator\Service;

defined('ABSPATH') || exit;

use Locator\Admin\Settings;
use Locator\Contract\HasHooks;
use Locator\Repository\StoreRepository;
use Locator\Util\TemplateLoader;

use const Locator\VERSION;

/**
 * Front-end service: registers the [locator] shortcode and renders an
 * accessible, searchable directory of published store locations.
 *
 * Filtering is performed client-side (no AJAX, no external API): every store
 * carries a lower-cased search haystack on a data attribute, and a small script
 * shows/hides cards as the visitor types. This keeps the directory fast, private
 * and fully functional without JavaScript (all stores are rendered server-side).
 */
final class Locator implements HasHooks
{
    private bool $assetsNeeded = false;

    public function __construct(
        private readonly StoreRepository $repository,
        private readonly TemplateLoader $templates,
        private readonly Settings $settings,
    ) {
    }

    public function registerHooks(): void
    {
        add_shortcode('locator', [$this, 'renderShortcode']);
        add_action('wp_enqueue_scripts', [$this, 'registerAssets']);
        add_action('wp_footer', [$this, 'enqueueIfNeeded']);
    }

    public function registerAssets(): void
    {
        wp_register_style(
            'locator',
            \LOCATOR_URL . 'assets/css/locator.css',
            [],
            VERSION,
        );

        wp_register_script(
            'locator',
            \LOCATOR_URL . 'assets/js/locator.js',
            [],
            VERSION,
            true,
        );
    }

    /**
     * Enqueue assets only when the shortcode actually rendered on the page.
     */
    public function enqueueIfNeeded(): void
    {
        if (! $this->assetsNeeded) {
            return;
        }

        wp_enqueue_style('locator');
        wp_enqueue_script('locator');
    }

    /**
     * Render the [locator] shortcode.
     *
     * @param array<string, mixed>|string $atts
     */
    public function renderShortcode(array|string $atts = []): string
    {
        $settings = $this->settings->all();

        $stores = $this->repository->all();

        // Mark assets for enqueue (search interactivity + styling).
        $this->assetsNeeded = true;

        /** @var array<string, bool> $fields */
        $fields = is_array($settings['fields'] ?? null) ? $settings['fields'] : [];

        return $this->templates->render('locator-list', [
            'stores'      => $stores,
            'show_search' => ! empty($settings['show_search']),
            'fields'      => $fields,
            'empty_text'  => __('No store locations have been added yet.', 'locator'),
        ]);
    }
}
