<?php

namespace Tests\Unit\Models;

use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use SimpleParkBv\Invoices\Models\Seller;
use Tests\TestCase;

final class SellerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // set default config for seller
        Config::set('invoices.seller', [
            'name' => 'Test Seller',
            'address' => 'Test Address',
            'city' => 'Test City',
            'postal_code' => '12345',
            'country' => 'Test Country',
            'email' => 'seller@test.com',
            'registration_number' => '12345678',
            'tax_id' => 'NL123456789B01',
            'bank_account' => 'NL91ABNA0417164300',
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
        $this->assertInstanceOf(\SimpleParkBv\Invoices\Models\Party::class, $seller);
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
            'registration_number' => '87654321',
            'tax_id' => 'NL987654321B02',
            'bank_account' => 'NL91ABNA0417164301',
        ]);

        // act
        $seller = Seller::make();

        // assert
        $this->assertEquals('Config Seller', $seller->getName());
        $this->assertEquals('Config Address', $seller->getAddress());
        $this->assertEquals('Config City', $seller->getCity());
        $this->assertEquals('54321', $seller->getPostalCode());
        $this->assertEquals('Config Country', $seller->getCountry());
        $this->assertEquals('config@test.com', $seller->getEmail());
        $this->assertEquals('87654321', $seller->registrationNumber);
        $this->assertEquals('NL987654321B02', $seller->taxId);
        $this->assertEquals('NL91ABNA0417164301', $seller->bankAccount);
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
        $this->assertEquals('Partial Seller', $seller->getName());
        $this->assertNull($seller->getAddress());
        $this->assertNull($seller->getCity());
        $this->assertNull($seller->getPostalCode());
        $this->assertNull($seller->getCountry());
        $this->assertNull($seller->getEmail());
        $this->assertNull($seller->getPhone());
        $this->assertNull($seller->getWebsite());
        $this->assertNull($seller->registrationNumber);
        $this->assertNull($seller->taxId);
        $this->assertNull($seller->bankAccount);
    }

    #[Test]
    public function seller_has_additional_properties(): void
    {
        // arrange
        $seller = Seller::make();

        // act
        $seller->registrationNumber = '12345678';
        $seller->taxId = 'NL123456789B01';
        $seller->bankAccount = 'NL91ABNA0417164300';

        // assert
        $this->assertEquals('12345678', $seller->registrationNumber);
        $this->assertEquals('NL123456789B01', $seller->taxId);
        $this->assertEquals('NL91ABNA0417164300', $seller->bankAccount);
    }
}
