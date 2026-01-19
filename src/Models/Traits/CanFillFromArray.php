<?php

namespace SimpleParkBv\Invoices\Models\Traits;

use Illuminate\Support\Str;
use ReflectionException;
use ReflectionMethod;

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
        // blocklist of dangerous method names that should never be called via fill
        $dangerousMethods = [
            'fill', 'make', 'toArray', 'toJson', '__construct', '__destruct',
            '__call', '__callStatic', '__get', '__set', '__isset', '__unset',
            '__sleep', '__wakeup', '__serialize', '__unserialize', '__toString',
            '__invoke', '__set_state', '__clone', '__debugInfo',
        ];

        foreach ($data as $key => $value) {
            // skip arrays as they need separate handling (e.g., buyer, items)
            if (is_array($value)) {
                continue;
            }

            // convert snake_case to camelCase for method name
            $method = Str::camel($key);

            // skip if method name is in the dangerous methods blocklist
            if (in_array($method, $dangerousMethods, true)) {
                continue;
            }

            // check if a setter method exists (e.g., unitPrice())
            if (! method_exists($this, $method)) {
                continue;
            }

            // use reflection to verify the method is safe to call
            try {
                $reflection = new ReflectionMethod($this, $method);

                // ensure the method is public
                if (! $reflection->isPublic()) {
                    continue;
                }

                // ensure the method has exactly one required parameter
                // or exactly one total parameter (with optional parameters)
                $paramCount = $reflection->getNumberOfParameters();
                $requiredParamCount = $reflection->getNumberOfRequiredParameters();

                if ($paramCount === 0 || $requiredParamCount > 1) {
                    continue;
                }

                // safe to invoke the setter
                $this->$method($value);
            } catch (ReflectionException $e) {
                // skip if reflection fails
                continue;
            }
        }

        return $this;
    }
}
