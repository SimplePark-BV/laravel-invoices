<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Models\Buyer;
use Tests\TestCase;

final class PartyTest extends TestCase
{
    #[Test]
    public function get_name_returns_name(): void
    {
        // arrange
        $buyer = Buyer::make(['name' => 'Test Name']);

        // act
        $name = $buyer->getName();

        // assert
        $this->assertEquals('Test Name', $name);
    }

    #[Test]
    #[DataProvider('getter_methods_data_provider')]
    public function getter_methods_return_value_or_null(string $setter, ?string $value, string $getter, ?string $expected): void
    {
        // arrange
        $buyer = Buyer::make(['name' => 'Test Buyer']);

        // explicitly set property to value (including null)
        $buyer->$setter($value);

        // act
        $result = $buyer->$getter();

        // assert
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array<string, array{0: string, 1: string|null, 2: string, 3: string|null}>
     */
    public static function getter_methods_data_provider(): array
    {
        return [
            'address with value' => ['address', '123 Test St', 'getAddress', '123 Test St'],
            'address null' => ['address', null, 'getAddress', null],
            'city with value' => ['city', 'Test City', 'getCity', 'Test City'],
            'city null' => ['city', null, 'getCity', null],
            'postalCode with value' => ['postalCode', '12345', 'getPostalCode', '12345'],
            'postalCode null' => ['postalCode', null, 'getPostalCode', null],
            'country with value' => ['country', 'Test Country', 'getCountry', 'Test Country'],
            'country null' => ['country', null, 'getCountry', null],
            'email with value' => ['email', 'test@example.com', 'getEmail', 'test@example.com'],
            'email null' => ['email', null, 'getEmail', null],
            'phone with value' => ['phone', '+1234567890', 'getPhone', '+1234567890'],
            'phone null' => ['phone', null, 'getPhone', null],
            'website with value' => ['website', 'https://example.com', 'getWebsite', 'https://example.com'],
            'website null' => ['website', null, 'getWebsite', null],
        ];
    }

    #[Test]
    public function to_array_includes_all_properties(): void
    {
        // arrange
        $buyer = Buyer::make([
            'name' => 'Test Buyer',
            'address' => '123 Test St',
            'city' => 'Test City',
            'postal_code' => '12345',
            'country' => 'Test Country',
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'website' => 'https://example.com',
        ]);

        // act
        $array = $buyer->toArray();

        // assert
        $this->assertEquals('Test Buyer', $array['name']);
        $this->assertEquals('123 Test St', $array['address']);
        $this->assertEquals('Test City', $array['city']);
        $this->assertEquals('12345', $array['postal_code']); // toArray uses snake_case
        $this->assertEquals('Test Country', $array['country']);
        $this->assertEquals('test@example.com', $array['email']);
        $this->assertEquals('+1234567890', $array['phone']);
        $this->assertEquals('https://example.com', $array['website']);
    }

    #[Test]
    public function to_array_includes_null_for_unset_properties(): void
    {
        // arrange
        $buyer = Buyer::make(['name' => 'Test Buyer']);

        // act
        $array = $buyer->toArray();

        // assert
        $this->assertIsArray($array); // @phpstan-ignore-line method.alreadyNarrowedType
        $this->assertEquals('Test Buyer', $array['name']);
        $this->assertNull($array['address']);
        $this->assertNull($array['city']);
        $this->assertNull($array['postal_code']); // toArray uses snake_case
        $this->assertNull($array['country']);
        $this->assertNull($array['email']);
        $this->assertNull($array['phone']);
        $this->assertNull($array['website']);
    }
}
