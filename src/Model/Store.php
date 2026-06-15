<?php

declare(strict_types=1);

namespace Locator\Model;

defined('ABSPATH') || exit;

/**
 * An immutable value object describing a single store location, hydrated from a
 * locator_store post and its meta.
 */
final class Store
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $description,
        public readonly string $address,
        public readonly string $city,
        public readonly string $postcode,
        public readonly string $country,
        public readonly string $phone,
        public readonly string $email,
        public readonly string $hours,
        public readonly ?float $lat,
        public readonly ?float $lng,
        public readonly string $thumbnailUrl,
    ) {
    }

    /**
     * A single, lower-cased haystack used for client-side text filtering.
     */
    public function searchHaystack(): string
    {
        $parts = [
            $this->name,
            $this->address,
            $this->city,
            $this->postcode,
            $this->country,
        ];

        return strtolower(trim(implode(' ', array_filter($parts))));
    }
}
