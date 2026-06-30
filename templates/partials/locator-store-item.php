<?php
/**
 * Single store card in the locator directory list.
 *
 * @var \Locator\Model\Store $locator_store
 * @var array<string, bool>  $locator_fields
 *
 * @package Locator
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

if (! isset($locator_store) || ! $locator_store instanceof \Locator\Model\Store) {
    return;
}

$locator_fields = isset($locator_fields) && is_array($locator_fields) ? $locator_fields : [];
?>
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
                    <span class="locator__hours-label"><?php esc_html_e('Opening hours', 'plogins-locator'); ?></span>
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
