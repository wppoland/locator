<?php
/**
 * Storefront template: searchable store-locations directory.
 *
 * Variables (prefixed by the template loader):
 *
 * @var list<\Locator\Model\Store>                                                         $locator_stores
 * @var list<array{label: string, stores: list<\Locator\Model\Store>}>|null               $locator_store_groups
 * @var bool                                                                             $locator_show_search
 * @var array<string, bool>                                                              $locator_fields
 * @var string                                                                           $locator_empty_text
 *
 * @package Locator
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

/** @var list<\Locator\Model\Store> $locator_stores */
$locator_stores = isset($locator_stores) && is_array($locator_stores) ? $locator_stores : [];
/** @var list<array{label: string, stores: list<\Locator\Model\Store>}>|null $locator_store_groups */
$locator_store_groups = isset($locator_store_groups) && is_array($locator_store_groups) ? $locator_store_groups : null;
$locator_show_search = ! empty($locator_show_search);
$locator_fields = isset($locator_fields) && is_array($locator_fields) ? $locator_fields : [];
$locator_empty_text = isset($locator_empty_text) ? (string) $locator_empty_text : '';

$locator_input_id = wp_unique_id('locator-search-');
$locator_count_id = wp_unique_id('locator-count-');
$locator_item_partial = __DIR__ . '/partials/locator-store-item.php';
?>
<div class="locator" data-locator>

    <?php if ([] === $locator_stores) : ?>

        <p class="locator__empty"><?php echo esc_html($locator_empty_text); ?></p>

    <?php else : ?>

        <?php if ($locator_show_search) : ?>
            <div class="locator__search">
                <label for="<?php echo esc_attr($locator_input_id); ?>" class="locator__search-label">
                    <?php esc_html_e('Find a store', 'locator'); ?>
                </label>
                <input
                    type="search"
                    id="<?php echo esc_attr($locator_input_id); ?>"
                    class="locator__search-input"
                    data-locator-search
                    autocomplete="off"
                    placeholder="<?php esc_attr_e('Search by city, postcode or name…', 'locator'); ?>"
                    aria-describedby="<?php echo esc_attr($locator_count_id); ?>" />
                <p
                    id="<?php echo esc_attr($locator_count_id); ?>"
                    class="locator__count"
                    data-locator-count
                    role="status"
                    aria-live="polite">
                    <?php
                    printf(
                        /* translators: %d: number of store locations. */
                        esc_html(_n('%d location', '%d locations', count($locator_stores), 'locator')),
                        (int) count($locator_stores),
                    );
                    ?>
                </p>
                <p class="locator__noresults" data-locator-noresults hidden>
                    <?php esc_html_e('No locations match your search.', 'locator'); ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if (is_array($locator_store_groups) && $locator_store_groups !== []) : ?>
            <?php foreach ($locator_store_groups as $locator_group) :
                $locator_group_label = (string) ($locator_group['label'] ?? '');
                $locator_group_stores = is_array($locator_group['stores'] ?? null) ? $locator_group['stores'] : [];
                ?>
                <section class="locator__group" data-locator-group>
                    <?php if ('' !== $locator_group_label) : ?>
                        <h2 class="locator__group-title"><?php echo esc_html($locator_group_label); ?></h2>
                    <?php endif; ?>
                    <ul class="locator__list" data-locator-list>
                        <?php foreach ($locator_group_stores as $locator_store) :
                            if (! $locator_store instanceof \Locator\Model\Store) {
                                continue;
                            }
                            require $locator_item_partial;
                        endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>
        <?php else : ?>
            <ul class="locator__list" data-locator-list>
                <?php foreach ($locator_stores as $locator_store) :
                    require $locator_item_partial;
                endforeach; ?>
            </ul>
        <?php endif; ?>

    <?php endif; ?>
</div>
