<?php

namespace Tests\Feature\UsageReceipt;

use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestReceipts;

final class OptionalSectionsTest extends TestCase
{
    use CreatesTestReceipts;

    #[Test]
    public function a_minimal_receipt_leaves_out_every_optional_section(): void
    {
        // arrange
        $receipt = $this->createReceipt(['buyer' => ['email' => 'anonymous@example.test']]);

        // act
        $html = $receipt->toHtml();

        // assert
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('class="usage-receipt__date-cell"', $html);
        $this->assertStringNotContainsString('class="document__buyer-name', $html);
        $this->assertStringNotContainsString('class="usage-receipt__ids"', $html);
        $this->assertStringNotContainsString('class="usage-receipt__notes"', $html);
        $this->assertStringNotContainsString('Waarvan', $html);
        $this->assertStringContainsString('anonymous@example.test', $html);
    }

    #[Test]
    public function only_the_ids_that_are_set_are_shown(): void
    {
        // arrange
        $receipt = $this->createReceipt(['user_id' => 'USR-1']);

        // act
        $html = $receipt->toHtml();

        // assert
        $this->assertStringContainsString('Gebruikers-ID', $html);
        $this->assertStringNotContainsString('Document-ID', $html);
    }

    #[Test]
    public function a_note_of_zero_is_shown(): void
    {
        // arrange
        $receipt = $this->createReceipt(['note' => '0']);

        // act
        $html = $receipt->toHtml();

        // assert
        $this->assertStringContainsString('class="usage-receipt__notes"', $html);
    }

    #[Test]
    public function without_lines_only_the_total_of_the_sessions_is_shown(): void
    {
        // arrange
        $receipt = $this->createReceipt(['items' => [$this->receiptItemData(['price' => 1.10]), $this->receiptItemData(['price' => 2.20])]]);

        // act
        $html = $receipt->toHtml();

        // assert
        $this->assertSame(1, substr_count($html, 'class="usage-receipt__totals-label'));
        $this->assertStringContainsString("€\u{00A0}3,30", $html);
    }

    #[Test]
    public function the_seller_footer_leaves_out_missing_details(): void
    {
        // arrange
        $receipt = $this->createReceipt(['seller' => ['name' => 'Seller B.V.']]);

        // act
        $html = $receipt->toHtml();

        // assert
        $this->assertStringContainsString('Seller B.V.', $html);
        $this->assertStringNotContainsString('KvK-nummer', $html);
        $this->assertStringNotContainsString('Website', $html);
        $this->assertStringNotContainsString('E-mail', $html);
    }

    #[Test]
    public function the_phone_of_the_seller_is_shown_when_set(): void
    {
        // arrange
        $receipt = $this->createReceipt(['seller' => ['name' => 'Seller B.V.', 'phone' => '+31 20 1234567']]);

        // act
        $html = $receipt->toHtml();

        // assert
        $this->assertStringContainsString('Telefoon', $html);
        $this->assertStringContainsString('+31 20 1234567', $html);
    }

    #[Test]
    public function the_price_column_of_the_sessions_can_be_hidden(): void
    {
        // arrange
        $receipt = $this->createReceipt()->itemPrices(false);

        // act
        $html = $receipt->toHtml();

        // assert — the total of the sessions is still shown
        $this->assertStringNotContainsString('<th class="usage-receipt__sessions-header usage-receipt__sessions-header--right"', $html);
        $this->assertStringContainsString('usage-receipt__totals-value--total', $html);
    }

    #[Test]
    #[RequiresPhpExtension('intl')]
    public function the_buyer_country_is_left_out_when_it_is_the_seller_country(): void
    {
        // arrange
        Config::set('invoices.seller.country', 'NL');
        $receipt = $this->createReceipt(['buyer' => ['name' => 'Jan', 'city' => 'Utrecht', 'country' => 'nl']]);

        // act
        $html = $receipt->toHtml();

        // assert — the seller's own address in the footer still names the country
        $this->assertFalse($receipt->showsBuyerCountry());
        $this->assertSame(1, substr_count($html, 'Nederland'));
    }

    #[Test]
    #[RequiresPhpExtension('intl')]
    public function a_foreign_buyer_country_is_named_in_the_document_language(): void
    {
        // arrange
        Config::set('invoices.seller.country', 'NL');
        $receipt = $this->createReceipt([
            'language' => 'en',
            'buyer' => ['name' => 'Jan', 'city' => 'Antwerpen', 'country' => 'BE'],
        ]);

        // act
        $html = $receipt->toHtml();

        // assert
        $this->assertTrue($receipt->showsBuyerCountry());
        $this->assertStringContainsString('Belgium', $html);
        $this->assertStringContainsString('Netherlands', $html);
    }
}
