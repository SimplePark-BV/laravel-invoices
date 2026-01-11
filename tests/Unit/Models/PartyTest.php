<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Buyer;
use Tests\TestCase;

final class PartyTest extends TestCase
{
    #[Test]
    public function get_name_returns_name(): void
    {
        // arrange
        $buyer = Buyer::make();
        $buyer->name = 'Test Name';

        // act
        $name = $buyer->getName();

        // assert
        $this->assertEquals('Test Name', $name);
    }

    #[Test]
    #[DataProvider('getter_methods_data_provider')]
    public function getter_methods_return_value_or_null(string $property, ?string $value, string $method, ?string $expected): void
    {
        // arrange
        $buyer = Buyer::make();

        // explicitly set property to value (including null) to avoid uninitialized property errors
        $buyer->$property = $value;

        // act
        $result = $buyer->$method();

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
            'postal_code with value' => ['postal_code', '12345', 'getPostalCode', '12345'],
            'postal_code null' => ['postal_code', null, 'getPostalCode', null],
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
        $buyer = Buyer::make();
        $buyer->name = 'Test Buyer';
        $buyer->address = '123 Test St';
        $buyer->city = 'Test City';
        $buyer->postal_code = '12345';
        $buyer->country = 'Test Country';
        $buyer->email = 'test@example.com';
        $buyer->phone = '+1234567890';
        $buyer->website = 'https://example.com';

        // act
        $array = $buyer->toArray();

        // assert
        $this->assertEquals('Test Buyer', $array['name']);
        $this->assertEquals('123 Test St', $array['address']);
        $this->assertEquals('Test City', $array['city']);
        $this->assertEquals('12345', $array['postal_code']);
        $this->assertEquals('Test Country', $array['country']);
        $this->assertEquals('test@example.com', $array['email']);
        $this->assertEquals('+1234567890', $array['phone']);
        $this->assertEquals('https://example.com', $array['website']);
    }

    #[Test]
    public function to_array_includes_null_for_unset_properties(): void
    {
        // arrange
        $buyer = Buyer::make();
        $buyer->name = 'Test Buyer';

        // act
        $array = $buyer->toArray();

        // assert
        $this->assertIsArray($array); // @phpstan-ignore-line method.alreadyNarrowedType
        $this->assertEquals('Test Buyer', $array['name']);
        $this->assertNull($array['address']);
        $this->assertNull($array['city']);
        $this->assertNull($array['postal_code']);
        $this->assertNull($array['country']);
        $this->assertNull($array['email']);
        $this->assertNull($array['phone']);
        $this->assertNull($array['website']);
    }
}
