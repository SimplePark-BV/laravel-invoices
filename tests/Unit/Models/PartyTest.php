<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Models\Buyer;
use SimpleParkBv\Invoices\Models\Party;
use SimpleParkBv\Invoices\Models\Seller;
use Tests\TestCase;

final class PartyTest extends TestCase
{
    /**
     * @param  class-string<\SimpleParkBv\Invoices\Models\Party>  $class
     */
    #[Test]
    #[DataProvider('parties')]
    public function fluent_setters_and_getters_match(string $class): void
    {
        // act
        $party = (new $class)
            ->name('Name')
            ->address('Address')
            ->postalCode('1234 AB')
            ->city('City')
            ->country('Country')
            ->email('mail@example.test')
            ->phone('0612345678')
            ->website('example.test');

        // assert
        $this->assertInstanceOf(Party::class, $party);
        $this->assertSame(
            ['Name', 'Address', '1234 AB', 'City', 'Country', 'mail@example.test', '0612345678', 'example.test'],
            [$party->getName(), $party->getAddress(), $party->getPostalCode(), $party->getCity(), $party->getCountry(), $party->getEmail(), $party->getPhone(), $party->getWebsite()],
        );
    }

    /**
     * @return array<string, array{class-string<\SimpleParkBv\Invoices\Models\Party>}>
     */
    public static function parties(): array
    {
        return [
            'buyer' => [Buyer::class],
            'seller' => [Seller::class],
        ];
    }

    #[Test]
    public function setters_clear_fields_with_null(): void
    {
        // arrange
        $buyer = Buyer::make(['name' => 'Jan', 'email' => 'jan@example.test']);

        // act
        $buyer->email(null);

        // assert
        $this->assertNull($buyer->getEmail());
    }
}
