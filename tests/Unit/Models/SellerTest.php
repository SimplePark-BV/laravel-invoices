<?php

namespace Tests\Unit\Models;

use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Models\Seller;
use Tests\TestCase;

final class SellerTest extends TestCase
{
    #[Test]
    public function from_config_reads_the_invoices_seller_config(): void
    {
        // act
        $seller = Seller::fromConfig();

        // assert
        $this->assertSame('Test Seller B.V.', $seller->getName());
        $this->assertSame('Teststraat 1', $seller->getAddress());
        $this->assertSame('1234 AB', $seller->getPostalCode());
        $this->assertSame('Teststad', $seller->getCity());
        $this->assertSame('Nederland', $seller->getCountry());
        $this->assertSame('info@seller.test', $seller->getEmail());
        $this->assertNull($seller->getPhone());
        $this->assertSame('www.seller.test', $seller->getWebsite());
        $this->assertSame('12345678', $seller->getRegistrationNumber());
        $this->assertSame('NL123456789B01', $seller->getTaxId());
        $this->assertSame('NL00 TEST 0123 4567 89', $seller->getBankAccount());
    }

    #[Test]
    public function from_config_ignores_unknown_keys_and_casts_numbers(): void
    {
        // arrange
        Config::set('invoices.seller', ['name' => 'Seller', 'registration_number' => 12345678, 'unknown' => 'value']);

        // act
        $seller = Seller::fromConfig();

        // assert
        $this->assertSame('Seller', $seller->getName());
        $this->assertSame('12345678', $seller->getRegistrationNumber());
    }

    #[Test]
    public function from_config_without_config_has_no_fields(): void
    {
        // arrange
        Config::set('invoices.seller', null);

        // act & assert
        $this->assertNull(Seller::fromConfig()->getName());
    }

    #[Test]
    public function make_fills_the_seller_from_an_array(): void
    {
        // act
        $seller = Seller::make(['name' => 'Other B.V.', 'registration_number' => '87654321', 'tax_id' => 'NL1', 'bank_account' => 'NL2']);

        // assert
        $this->assertSame('Other B.V.', $seller->getName());
        $this->assertSame('87654321', $seller->getRegistrationNumber());
        $this->assertSame('NL1', $seller->getTaxId());
        $this->assertSame('NL2', $seller->getBankAccount());
    }

    #[Test]
    public function to_array_includes_the_seller_fields_and_round_trips(): void
    {
        // arrange
        $seller = Seller::fromConfig();

        // act
        $array = $seller->toArray();

        // assert
        $this->assertSame('12345678', $array['registration_number']);
        $this->assertSame('NL123456789B01', $array['tax_id']);
        $this->assertSame('NL00 TEST 0123 4567 89', $array['bank_account']);
        $this->assertEquals($seller, Seller::make($array));
    }
}
