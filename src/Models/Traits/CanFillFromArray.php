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
     * 1. Converts snake_case keys to camelCase method names (e.g., unit_price -> unitPrice())
     * 2. Calls the setter method if it exists
     * 3. Skips the key if no setter method is found
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
        }

        return $this;
    }
}
