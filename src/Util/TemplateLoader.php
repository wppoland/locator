<?php

declare(strict_types=1);

namespace Locator\Util;

defined('ABSPATH') || exit;

use const Locator\PLUGIN_DIR;

/**
 * Loads plugin templates from {plugin}/templates/{template}.php.
 */
final class TemplateLoader
{
    /**
     * Render a template and return the HTML.
     *
     * @param string               $template Template name (e.g. 'locator-list').
     * @param array<string, mixed> $args     Variables to extract into the template scope.
     */
    public function render(string $template, array $args = []): string
    {
        $path = $this->locate($template);

        if (null === $path) {
            return '';
        }

        // Prefix every template variable with `locator_` to keep templates within
        // the plugin's variable namespace (per WordPress.org coding standards).
        $locator_args = [];
        foreach ($args as $locator_args_key => $locator_args_value) {
            if (! is_string($locator_args_key) || '' === $locator_args_key) {
                continue;
            }
            $locator_key = str_starts_with($locator_args_key, 'locator_') ? $locator_args_key : 'locator_' . $locator_args_key;
            $locator_args[$locator_key] = $locator_args_value;
        }

        unset($args, $locator_args_key, $locator_args_value, $locator_key);

        ob_start();

        extract($locator_args, EXTR_SKIP); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

        include $path;

        return (string) ob_get_clean();
    }

    /**
     * Locate a template file. Returns null if not found.
     */
    private function locate(string $template): ?string
    {
        $template = ltrim($template, '/');

        if (! str_ends_with($template, '.php')) {
            $template .= '.php';
        }

        $pluginPath = PLUGIN_DIR . '/templates/' . $template;

        return file_exists($pluginPath) ? $pluginPath : null;
    }
}
