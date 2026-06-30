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
     * @param list<array<string, mixed>> $raw
     * @return list<array{label: string, stores: list<\Locator\Model\Store>}>
     */
    private function normalizeStoreGroups(array $raw): array
    {
        $groups = [];

        foreach ($raw as $group) {
            if (! is_array($group)) {
                continue;
            }

            $stores = $group['stores'] ?? [];
            if (! is_array($stores) || $stores === []) {
                continue;
            }

            $resolved = [];
            foreach ($stores as $store) {
                if ($store instanceof \Locator\Model\Store) {
                    $resolved[] = $store;
                }
            }

            if ($resolved === []) {
                continue;
            }

            $groups[] = [
                'label'  => isset($group['label']) ? (string) $group['label'] : '',
                'stores' => $resolved,
            ];
        }

        return $groups;
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

        /** @var list<array{label: string, stores: list<\Locator\Model\Store>}>|null $storeGroups */
        $storeGroups = apply_filters('locator/store_groups', null, $stores);

        if (! is_array($storeGroups) || $storeGroups === []) {
            $storeGroups = null;
        } else {
            $storeGroups = $this->normalizeStoreGroups($storeGroups);
            if ($storeGroups === []) {
                $storeGroups = null;
            }
        }

        return $this->templates->render('locator-list', [
            'stores'       => $stores,
            'store_groups' => $storeGroups,
            'show_search'  => ! empty($settings['show_search']),
            'fields'       => $fields,
            'empty_text'   => __('No store locations have been added yet.', 'plogins-locator'),
        ]);
    }
}
