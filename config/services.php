<?php
/**
 * Service wiring. Returns a closure that registers every service in the
 * container. Bindings are lazy — nothing is instantiated until first resolved —
 * so the container is safe to build during the activation hook.
 *
 * @package Locator
 */

declare(strict_types=1);

use Locator\Admin\Settings;
use Locator\Container;
use Locator\Migrator;
use Locator\PostType\StoreLocation;
use Locator\Repository\StoreRepository;
use Locator\Service\Locator;
use Locator\Service\StoreWriter;
use Locator\Util\TemplateLoader;

defined('ABSPATH') || exit;

return static function (Container $c): void {
    // Infrastructure.
    $c->singleton(Migrator::class, static fn (): Migrator => new Migrator());
    $c->singleton(StoreRepository::class, static fn (): StoreRepository => new StoreRepository());
    $c->singleton(StoreWriter::class, static fn (): StoreWriter => new StoreWriter());
    $c->singleton(TemplateLoader::class, static fn (): TemplateLoader => new TemplateLoader());

    // The custom post type is needed in both admin and front-end contexts.
    $c->singleton(StoreLocation::class, static fn (): StoreLocation => new StoreLocation());

    // Settings is resolved both in admin (its own page) and on the front end
    // (the shortcode reads the visible-fields config), so register it unconditionally.
    $c->singleton(Settings::class, static fn (): Settings => new Settings());

    // Front-end directory service.
    $c->singleton(Locator::class, static fn (): Locator => new Locator(
        $c->get(StoreRepository::class),
        $c->get(TemplateLoader::class),
        $c->get(Settings::class),
    ));
};
