<?php
/**
 * Storefront template: searchable store-locations directory.
 *
 * Variables (prefixed by the template loader):
 *
 * @var list<\Locator\Model\Store> $locator_stores
 * @var bool                       $locator_show_search
 * @var array<string, bool>        $locator_fields
 * @var string                     $locator_empty_text
 *
 * @package Locator
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

/** @var list<\Locator\Model\Store> $locator_stores */
$locator_stores = isset($locator_stores) && is_array($locator_stores) ? $locator_stores : [];
$locator_show_search = ! empty($locator_show_search);
$locator_fields = isset($locator_fields) && is_array($locator_fields) ? $locator_fields : [];
$locator_empty_text = isset($locator_empty_text) ? (string) $locator_empty_text : '';

$locator_input_id = wp_unique_id('locator-search-');
$locator_count_id = wp_unique_id('locator-count-');
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

        <ul class="locator__list" data-locator-list>
            <?php foreach ($locator_stores as $locator_store) : ?>
                <li class="locator__item" data-locator-item
                    data-locator-haystack="<?php echo esc_attr($locator_store->searchHaystack()); ?>">
                    <article class="locator__card">
                        <span class="locator__pin" data-locator-pin aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="20" height="20" focusable="false">
                                <path d="M12 2a7 7 0 0 0-7 7c0 4.8 6.2 12.2 6.5 12.5a.7.7 0 0 0 1 0C12.8 21.2 19 13.8 19 9a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z" />
                            </svg>
                        </span>
                        <div class="locator__body">
                            <h3 class="locator__name"><?php echo esc_html($locator_store->name); ?></h3>

                            <?php
                            if (! empty($locator_fields['address'])) :
                                $locator_address_lines = array_filter([
                                    $locator_store->address,
                                    trim($locator_store->postcode . ' ' . $locator_store->city),
                                    $locator_store->country,
                                ], static fn (string $line): bool => '' !== trim($line));
                                ?>
                                <?php if ([] !== $locator_address_lines) : ?>
                                    <address class="locator__address">
                                        <?php echo wp_kses_post(nl2br(esc_html(implode("\n", $locator_address_lines)))); ?>
                                    </address>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if (! empty($locator_fields['hours']) && '' !== trim($locator_store->hours)) : ?>
                                <div class="locator__hours">
                                    <span class="locator__hours-label"><?php esc_html_e('Opening hours', 'locator'); ?></span>
                                    <?php echo wp_kses_post(nl2br(esc_html($locator_store->hours))); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (! empty($locator_fields['phone']) && '' !== trim($locator_store->phone)) : ?>
                                <ul class="locator__contact">
                                    <li class="locator__phone">
                                        <a href="<?php echo esc_url('tel:' . preg_replace('/[^0-9+]/', '', $locator_store->phone)); ?>">
                                            <?php echo esc_html($locator_store->phone); ?>
                                        </a>
                                    </li>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </article>
                </li>
            <?php endforeach; ?>
        </ul>

    <?php endif; ?>
</div>
