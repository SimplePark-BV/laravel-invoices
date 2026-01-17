<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Models\Buyer;
use Tests\TestCase;

final class BuyerTest extends TestCase
{
    #[Test]
    public function make_creates_instance(): void
    {
        // arrange & act
        $buyer = Buyer::make();

        // assert
        $this->assertInstanceOf(Buyer::class, $buyer);
    }

    #[Test]
    public function buyer_is_instance_of_party(): void
    {
        // arrange & act
        $buyer = Buyer::make();

        // assert
        $this->assertInstanceOf(\SimpleParkBv\Invoices\Models\Party::class, $buyer);
    }

    #[Test]
    public function buyer_properties_can_be_set(): void
    {
        // arrange
        $buyer = Buyer::make();

        // act
        $buyer->name = 'Test Buyer';
        $buyer->address = '123 Test St';
        $buyer->email = 'buyer@test.com';

        // assert
        $this->assertEquals('Test Buyer', $buyer->name);
        $this->assertEquals('123 Test St', $buyer->address);
        $this->assertEquals('buyer@test.com', $buyer->email);
    }
}
