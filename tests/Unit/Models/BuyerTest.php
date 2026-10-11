<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Exceptions\InvalidDocumentException;
use SimpleParkBv\Invoices\Models\Buyer;
use Tests\TestCase;

final class BuyerTest extends TestCase
{
    #[Test]
    public function make_fills_the_buyer_from_an_array(): void
    {
        // act
        $buyer = Buyer::make([
            'name' => 'Jan de Vries',
            'address' => 'Keizersgracht 123',
            'postal_code' => '1015 CJ',
            'city' => 'Amsterdam',
            'country' => 'Nederland',
            'email' => 'jan@example.test',
            'phone' => '+31 6 12345678',
            'website' => 'www.example.test',
        ]);

        // assert
        $this->assertSame('Jan de Vries', $buyer->getName());
        $this->assertSame('Keizersgracht 123', $buyer->getAddress());
        $this->assertSame('1015 CJ', $buyer->getPostalCode());
        $this->assertSame('Amsterdam', $buyer->getCity());
        $this->assertSame('Nederland', $buyer->getCountry());
        $this->assertSame('jan@example.test', $buyer->getEmail());
        $this->assertSame('+31 6 12345678', $buyer->getPhone());
        $this->assertSame('www.example.test', $buyer->getWebsite());
    }

    #[Test]
    public function make_accepts_camel_case_keys(): void
    {
        // act
        $buyer = Buyer::make(['name' => 'Jan', 'postalCode' => '1015 CJ']);

        // assert
        $this->assertSame('1015 CJ', $buyer->getPostalCode());
    }

    #[Test]
    public function make_rejects_unknown_keys(): void
    {
        // assert
        $this->expectException(InvalidDocumentException::class);
        $this->expectExceptionMessage("Unknown key 'zip' for Buyer");

        // act
        Buyer::make(['name' => 'Jan', 'zip' => '1015 CJ']);
    }

    #[Test]
    public function a_buyer_without_a_name_can_be_made(): void
    {
        // act
        $buyer = Buyer::make(['name' => null, 'email' => 'jan@example.test']);

        // assert
        $this->assertNull($buyer->getName());
    }

    #[Test]
    public function texts_are_trimmed_and_empty_texts_become_null(): void
    {
        // act
        $buyer = Buyer::make(['name' => ' Jan ', 'email' => '', 'phone' => '  ']);

        // assert
        $this->assertSame('Jan', $buyer->getName());
        $this->assertNull($buyer->getEmail());
        $this->assertNull($buyer->getPhone());
    }

    #[Test]
    public function has_address_checks_every_address_field(): void
    {
        // act & assert
        $this->assertFalse(Buyer::make(['name' => 'Jan', 'email' => 'jan@example.test'])->hasAddress());
        $this->assertTrue(Buyer::make(['address' => 'Keizersgracht 123'])->hasAddress());
        $this->assertTrue(Buyer::make(['postal_code' => '1015 CJ'])->hasAddress());
        $this->assertTrue(Buyer::make(['city' => 'Amsterdam'])->hasAddress());
        $this->assertTrue(Buyer::make(['country' => 'Nederland'])->hasAddress());
    }

    #[Test]
    public function to_array_round_trips_through_make(): void
    {
        // arrange
        $buyer = Buyer::make(['name' => 'Jan', 'postal_code' => '1015 CJ', 'email' => 'jan@example.test']);

        // act
        $copy = Buyer::make($buyer->toArray());

        // assert
        $this->assertEquals($buyer, $copy);
        $this->assertSame([
            'name' => 'Jan',
            'address' => null,
            'postal_code' => '1015 CJ',
            'city' => null,
            'country' => null,
            'email' => 'jan@example.test',
            'phone' => null,
            'website' => null,
        ], $buyer->toArray());
    }
}
