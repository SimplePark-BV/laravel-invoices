<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Models\Traits;

use Illuminate\Support\Str;
use TypeError;

/**
 * Fills an object from an array, by calling the setter of every key.
 *
 * Keys are the snake_case names of the setters (camelCase is accepted too), and the values are passed to the
 * setter as they are, so toArray() output can be fed back into make(). Unknown keys throw instead of being
 * ignored, so a typo never silently leaves a field empty.
 */
trait CanFillFromArray
{
    /**
     * Create a new instance, filled with the given data.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidDocumentException when a key is unknown or a value is invalid
     */
    public static function make(array $data = []): static
    {
        return (new static)->fill($data);
    }

    /**
     * Fill the object from an array.
     *
     * @param  array<string, mixed>  $data
     * @return $this
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidDocumentException when a key is unknown or a value is invalid
     */
    public function fill(array $data): static
    {
        $setters = static::fillable();
        $exception = static::invalidDataException();

        foreach ($data as $key => $value) {
            $setter = $setters[Str::snake((string) $key)] ?? null;

            if ($setter === null) {
                throw new $exception(sprintf(
                    "Unknown key '%s' for %s, expected one of: %s",
                    $key,
                    class_basename(static::class),
                    implode(', ', array_keys($setters)),
                ));
            }

            try {
                $this->{$setter}($value);
            } catch (TypeError $e) {
                throw new $exception(sprintf("Invalid value for '%s' of %s: %s", $key, class_basename(static::class), get_debug_type($value)), 0, $e);
            }
        }

        return $this;
    }

    /**
     * The keys fill() accepts, mapped to the setter each one calls.
     *
     * @return array<string, string>
     */
    abstract protected static function fillable(): array;

    /**
     * The exception fill() throws for an unknown key or invalid value.
     *
     * @return class-string<\SimpleParkBv\Invoices\Exceptions\InvalidDocumentException>
     */
    abstract protected static function invalidDataException(): string;
}
