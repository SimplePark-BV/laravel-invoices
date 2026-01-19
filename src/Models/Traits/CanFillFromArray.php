<?php

namespace SimpleParkBv\Invoices\Models\Traits;

use Illuminate\Support\Str;

/**
 * Trait CanFillFromArray
 *
 * Provides functionality to fill object properties from an array
 * and create instances with optional data.
 */
trait CanFillFromArray
{
    /**
     * Create a new instance, optionally filling it with data.
     *
     * @param  array<string, mixed>  $data
     */
    public static function make(array $data = []): static
    {
        $instance = new static;

        return empty($data) ? $instance : $instance->fill($data);
    }

    /**
     * Fill the object properties from an array.
     *
     * For each key:
     * 1. First checks if a camelCase method exists (e.g., unit_price -> unitPrice()) and calls it
     * 2. Otherwise checks if the exact key exists as a property and sets it directly
     * 3. If neither exists, skips the key
     *
     * @param  array<string, mixed>  $data
     * @return $this
     */
    public function fill(array $data): self
    {
        foreach ($data as $key => $value) {
            // skip arrays as they need separate handling (e.g., buyer, items)
            if (is_array($value)) {
                continue;
            }

            // convert snake_case to camelCase for method name
            $method = Str::camel($key);

            // check if a setter method exists (e.g., unitPrice())
            if (method_exists($this, $method)) {
                $this->$method($value);
            }

            // fallback to direct property assignment if property exists
            elseif (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        return $this;
    }
}
