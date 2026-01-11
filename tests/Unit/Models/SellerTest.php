<?php

namespace Tests\Unit\Models;

use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use SimpleParkBv\Invoices\Seller;
use Tests\TestCase;

final class SellerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set default config for seller
        Config::set('invoices.seller', [
            'name' => 'Test Seller',
            'address' => 'Test Address',
            'city' => 'Test City',
            'postal_code' => '12345',
            'country' => 'Test Country',
            'email' => 'seller@test.com',
            'kvk' => '12345678',
            'btw' => 'NL123456789B01',
            'iban' => 'NL91ABNA0417164300',
        ]);
    }

    #[Test]
    public function make_creates_instance(): void
    {
        // arrange & act
        $seller = Seller::make();

        // assert
        $this->assertInstanceOf(Seller::class, $seller);
    }

    #[Test]
    public function seller_is_instance_of_party(): void
    {
        // arrange & act
        $seller = Seller::make();

        // assert
        $this->assertInstanceOf(\SimpleParkBv\Invoices\Party::class, $seller);
    }

    #[Test]
    public function seller_initializes_from_config(): void
    {
        // arrange
        Config::set('invoices.seller', [
            'name' => 'Config Seller',
            'address' => 'Config Address',
            'city' => 'Config City',
            'postal_code' => '54321',
            'country' => 'Config Country',
            'email' => 'config@test.com',
            'kvk' => '87654321',
            'btw' => 'NL987654321B02',
            'iban' => 'NL91ABNA0417164301',
        ]);

        // act
        $seller = Seller::make();

        // assert
        $this->assertEquals('Config Seller', $seller->name);
        $this->assertEquals('Config Address', $seller->address);
        $this->assertEquals('Config City', $seller->city);
        $this->assertEquals('54321', $seller->postal_code);
        $this->assertEquals('Config Country', $seller->country);
        $this->assertEquals('config@test.com', $seller->email);
        $this->assertEquals('87654321', $seller->kvk);
        $this->assertEquals('NL987654321B02', $seller->btw);
        $this->assertEquals('NL91ABNA0417164301', $seller->iban);
    }

    #[Test]
    public function seller_throws_exception_when_name_missing(): void
    {
        // arrange
        Config::set('invoices.seller', []);

        // assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Seller name is required');

        // act
        Seller::make();
    }

    #[Test]
    public function seller_handles_partial_config(): void
    {
        // arrange
        Config::set('invoices.seller', [
            'name' => 'Partial Seller',
        ]);

        // act
        $seller = Seller::make();

        // assert
        $this->assertEquals('Partial Seller', $seller->name);
        $this->assertNull($seller->address);
        $this->assertNull($seller->city);
        $this->assertNull($seller->postal_code);
        $this->assertNull($seller->country);
        $this->assertNull($seller->email);
        $this->assertNull($seller->phone);
        $this->assertNull($seller->website);
        $this->assertNull($seller->kvk);
        $this->assertNull($seller->btw);
        $this->assertNull($seller->iban);
    }

    #[Test]
    public function seller_has_additional_properties(): void
    {
        // arrange
        $seller = Seller::make();

        // act
        $seller->kvk = '12345678';
        $seller->btw = 'NL123456789B01';
        $seller->iban = 'NL91ABNA0417164300';

        // assert
        $this->assertEquals('12345678', $seller->kvk);
        $this->assertEquals('NL123456789B01', $seller->btw);
        $this->assertEquals('NL91ABNA0417164300', $seller->iban);
    }
}
