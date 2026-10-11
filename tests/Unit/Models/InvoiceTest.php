<?php

namespace Tests\Unit\Models;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Enums\PaymentMethod;
use SimpleParkBv\Invoices\Exceptions\ExpectedTotalMismatchException;
use SimpleParkBv\Invoices\Exceptions\InvalidDocumentException;
use SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException;
use SimpleParkBv\Invoices\Exceptions\UnsupportedLanguageException;
use SimpleParkBv\Invoices\Models\Buyer;
use SimpleParkBv\Invoices\Models\Invoice;
use SimpleParkBv\Invoices\Models\InvoiceItem;
use SimpleParkBv\Invoices\Models\Seller;
use Tests\TestCase;
use Tests\Traits\CreatesTestInvoices;

final class InvoiceTest extends TestCase
{
    use CreatesTestInvoices;

    #[Test]
    public function a_new_invoice_has_the_defaults_from_the_config(): void
    {
        // act
        $invoice = Invoice::make();

        // assert
        $this->assertSame('nl', $invoice->getLanguage());
        $this->assertSame('d-m-Y', $invoice->getDateFormat());
        $this->assertSame(30, $invoice->getPaymentTermsDays());
        $this->assertSame(PaymentMethod::BankTransfer, $invoice->getPaymentMethod());
        $this->assertFalse($invoice->isPaid());
        $this->assertSame('Test Seller B.V.', $invoice->getSeller()->getName());
        $this->assertNull($invoice->getBuyer());
        $this->assertNull($invoice->getLogo());
        $this->assertSame('invoices::invoice.index', $invoice->getTemplate());
        $this->assertTrue($invoice->getItems()->isEmpty());
    }

    #[Test]
    public function the_config_can_change_the_defaults(): void
    {
        // arrange
        Config::set('invoices.default_language', 'en');
        Config::set('invoices.default_payment_terms_days', '14');
        Config::set('invoices.date_format', 'Y-m-d');
        Config::set('invoices.logo', 'logo.png');

        // act
        $invoice = Invoice::make();

        // assert
        $this->assertSame('en', $invoice->getLanguage());
        $this->assertSame(14, $invoice->getPaymentTermsDays());
        $this->assertSame('Y-m-d', $invoice->getDateFormat());
        $this->assertSame('logo.png', $invoice->getLogo());
    }

    #[Test]
    public function an_unsupported_default_language_throws(): void
    {
        // arrange
        Config::set('invoices.default_language', 'xx');

        // assert
        $this->expectException(UnsupportedLanguageException::class);

        // act
        Invoice::make();
    }

    #[Test]
    public function make_and_fluent_setters_build_the_same_invoice(): void
    {
        // act
        $fromArray = Invoice::make([
            'buyer' => ['name' => 'Jan'],
            'language' => 'en',
            'date' => '2026-10-11',
            'serial' => '2026.0001',
            'items' => [['title' => 'Parking', 'unit_price' => 12.10, 'tax_percentage' => 21]],
            'payment_method' => 'direct_debit',
            'expected_total' => 12.10,
        ]);

        $fluent = Invoice::make()
            ->buyer(Buyer::make(['name' => 'Jan']))
            ->language('en')
            ->date('2026-10-11')
            ->serial('2026.0001')
            ->addItem(InvoiceItem::make()->title('Parking')->unitPrice(12.10)->taxPercentage(21))
            ->paymentMethod(PaymentMethod::DirectDebit)
            ->expectedTotal(12.10);

        // assert
        $this->assertEquals($fluent, $fromArray);
    }

    #[Test]
    public function make_rejects_unknown_keys(): void
    {
        // assert
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage("Unknown key 'download' for Invoice");

        // act
        Invoice::make(['download' => 'invoice.pdf']);
    }

    #[Test]
    public function items_accept_items_and_arrays_and_replace_the_current_items(): void
    {
        // arrange
        $invoice = $this->createInvoice();

        // act
        $invoice->items([$this->createInvoiceItem(['title' => 'First']), ['title' => 'Second', 'unit_price' => 1]]);
        $invoice->addItem(['title' => 'Third', 'unit_price' => 2]);

        // assert
        $this->assertSame(['First', 'Second', 'Third'], $invoice->getItems()->map->getTitle()->all());
    }

    #[Test]
    public function get_items_returns_a_copy(): void
    {
        // arrange
        $invoice = $this->createInvoice();

        // act
        $invoice->getItems()->push($this->createInvoiceItem());

        // assert
        $this->assertCount(1, $invoice->getItems());
    }

    #[Test]
    #[DataProvider('numbers')]
    public function get_number_combines_serial_series_and_sequence(?string $serial, ?string $series, int|string|null $sequence, ?string $expected): void
    {
        // arrange
        $invoice = Invoice::make()->serial($serial)->series($series)->sequence($sequence);

        // act & assert
        $this->assertSame($expected, $invoice->getNumber());
    }

    /**
     * @return array<string, array{string|null, string|null, int|string|null, string|null}>
     */
    public static function numbers(): array
    {
        return [
            'nothing' => [null, null, null, null],
            'serial' => ['2026.0001', null, null, '2026.0001'],
            'serial wins over series and sequence' => ['2026.0001', '2026', 5, '2026.0001'],
            'blank serial is ignored' => ['  ', '2026', 5, '2026.00000005'],
            'series and sequence' => [null, '2026', 1, '2026.00000001'],
            'series and numeric string sequence' => [null, '2026', '42', '2026.00000042'],
            'series and text sequence' => [null, 'INV', 'A-1', 'INV.A-1'],
            'only series' => [null, '2026', null, '2026'],
            'only sequence' => [null, null, 7, '00000007'],
        ];
    }

    #[Test]
    public function the_due_date_is_the_date_plus_the_payment_terms(): void
    {
        // arrange
        $invoice = $this->createInvoice(['date' => '2026-01-31'])->paymentTermsDays(14);

        // act & assert
        $this->assertSame('2026-02-14', $invoice->getDueDate()?->toDateString());
        $this->assertSame('14-02-2026', $invoice->getFormattedDueDate());
    }

    #[Test]
    public function an_explicit_due_date_wins_over_the_payment_terms(): void
    {
        // arrange
        $invoice = $this->createInvoice(['date' => '2026-01-31', 'due_date' => '2026-03-01']);

        // act & assert
        $this->assertSame('2026-03-01', $invoice->getDueDate()?->toDateString());
    }

    #[Test]
    public function a_concept_has_no_due_date(): void
    {
        // act & assert
        $this->assertNull($this->createInvoice()->getDueDate());
        $this->assertNull($this->createInvoice()->getFormattedDueDate());
    }

    #[Test]
    public function negative_payment_terms_throw(): void
    {
        // assert
        $this->expectException(InvalidInvoiceException::class);

        // act
        Invoice::make()->paymentTermsDays(-1);
    }

    #[Test]
    public function the_date_is_stored_as_an_immutable_copy(): void
    {
        // arrange
        $date = \Illuminate\Support\Carbon::parse('2026-10-11');
        $invoice = $this->createInvoice()->date($date);

        // act
        $date->addMonth();
        $invoice->getDate()?->addMonth();

        // assert
        $this->assertSame('2026-10-11', $invoice->getDate()?->toDateString());
        $this->assertInstanceOf(CarbonImmutable::class, $invoice->getDate());
    }

    #[Test]
    public function an_empty_date_throws_instead_of_issuing_the_invoice_today(): void
    {
        // assert
        $this->expectException(InvalidInvoiceException::class);

        // act
        $this->createInvoice(['date' => '']);
    }

    #[Test]
    public function the_formatted_date_is_in_the_display_timezone(): void
    {
        // arrange
        $invoice = $this->createInvoice(['date' => CarbonImmutable::parse('2026-01-31 23:30', 'UTC')]);

        // act & assert
        $this->assertSame('01-02-2026', $invoice->getFormattedDate());
    }

    #[Test]
    public function totals_match_the_printed_breakdown(): void
    {
        // arrange
        $invoice = $this->createInvoice(['items' => [
            ['title' => 'A', 'unit_price' => 70.00, 'tax_percentage' => 21],
            ['title' => 'B', 'unit_price' => 10.00, 'tax_percentage' => 9],
        ]]);

        // act
        $totals = $invoice->getTotals();

        // assert
        $this->assertSame(80.0, $invoice->getTotal());
        $this->assertSame(12.98, $invoice->getTaxAmount());
        $this->assertSame(67.02, $invoice->getSubtotal());
        $this->assertSame([1215, 83], [$totals->getTaxGroups()[0]->getTaxCents(), $totals->getTaxGroups()[1]->getTaxCents()]);
    }

    #[Test]
    public function line_totals_add_up_to_the_total(): void
    {
        // arrange: 3 x 0.33 at a quantity of 1.5 used to print 0,50 per line but 1,48 as total
        $invoice = $this->createInvoice(['items' => array_fill(0, 3, ['title' => 'A', 'unit_price' => 0.33, 'quantity' => 1.5])]);

        // act & assert
        $this->assertSame(150, $invoice->getTotals()->getTotalCents());
    }

    #[Test]
    public function the_status_follows_the_date_total_and_payment(): void
    {
        // arrange
        $concept = $this->createInvoice();
        $issued = $this->createInvoice(['date' => '2026-10-11']);
        $paid = $this->createInvoice(['date' => '2026-10-11', 'paid' => true]);
        $credit = $this->createInvoice(['date' => '2026-10-11', 'items' => [['title' => 'Refund', 'unit_price' => -5]]]);
        $zero = $this->createInvoice(['date' => '2026-10-11', 'items' => [['title' => 'A', 'unit_price' => 5], ['title' => 'B', 'unit_price' => -5]]]);

        // act & assert
        $this->assertSame([false, false, false], [$concept->isIssued(), $concept->isCredit(), $concept->isPaymentDue()]);
        $this->assertSame([true, false, true], [$issued->isIssued(), $issued->isCredit(), $issued->isPaymentDue()]);
        $this->assertSame([true, false, false], [$paid->isIssued(), $paid->isCredit(), $paid->isPaymentDue()]);
        $this->assertSame([true, true, false], [$credit->isIssued(), $credit->isCredit(), $credit->isPaymentDue()]);
        $this->assertSame([true, false, false], [$zero->isIssued(), $zero->isCredit(), $zero->isPaymentDue()]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    #[Test]
    #[DataProvider('footers')]
    public function the_footer_depends_on_the_state_of_the_invoice(array $data, string $expected): void
    {
        // arrange
        $invoice = $this->createInvoice(['serial' => '2026.0001', ...$data]);

        // act
        $footer = strip_tags($invoice->getFooterMessage());

        // assert
        $this->assertSame($expected, $footer);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function footers(): array
    {
        $date = ['date' => '2026-10-11'];

        return [
            'concept' => [[], 'Dit is een CONCEPT. Dit betekent dat deze factuur nog niet officieel is en er nog geen betaling vereist is. Je ontvangt de officiële factuur op een later moment.'],
            'custom concept' => [['concept_footer_message' => 'Nog niet definitief <b>'], 'Nog niet definitief &lt;b&gt;'],
            'payment request' => [$date, "We verzoeken je vriendelijk het bovenstaande bedrag van €\u{00A0}10,00 voor 10-11-2026 te voldoen op onze bankrekening. Voor vragen kan je contact opnemen per e-mail."],
            'direct debit' => [[...$date, 'payment_method' => 'direct_debit'], "Het bovenstaande bedrag van €\u{00A0}10,00 wordt rond 10-11-2026 automatisch van je rekening afgeschreven. Voor vragen kan je contact opnemen per e-mail."],
            'paid' => [[...$date, 'paid' => true, 'payment_method' => 'direct_debit'], "Het bovenstaande bedrag van €\u{00A0}10,00 is al voldaan, je hoeft dus niets meer te betalen. Voor vragen kan je contact opnemen per e-mail."],
            'credit' => [[...$date, 'paid' => true, 'items' => [['title' => 'Refund', 'unit_price' => -10]]], "We zullen het bovenstaande bedrag van €\u{00A0}10,00 voor 10-11-2026 overmaken op je bankrekening. Voor vragen kan je contact opnemen per e-mail."],
            'nothing due' => [[...$date, 'items' => [['title' => 'A', 'unit_price' => 0.30], ['title' => 'B', 'unit_price' => -0.10], ['title' => 'C', 'unit_price' => -0.20]]], 'Je hoeft voor deze factuur niets te betalen. Voor vragen kan je contact opnemen per e-mail.'],
            'custom' => [[...$date, 'footer_message' => 'Pay :amount before :date for :number & <thanks>'], "Pay €\u{00A0}10,00 before 10-11-2026 for 2026.0001 &amp; &lt;thanks&gt;"],
            'custom with a negative total keeps the sign' => [[...$date, 'footer_message' => 'Total :amount', 'items' => [['title' => 'Refund', 'unit_price' => -10]]], "Total €\u{00A0}-10,00"],
        ];
    }

    #[Test]
    public function the_footer_uses_the_document_language_outside_rendering(): void
    {
        // arrange
        app()->setLocale('nl');
        $invoice = $this->createInvoice(['language' => 'en', 'date' => '2026-10-11', 'items' => [['title' => 'A', 'unit_price' => 1234.50]]]);

        // act
        $footer = strip_tags($invoice->getFooterMessage());

        // assert
        $this->assertStringStartsWith("We kindly request you to pay the above amount of €\u{00A0}1,234.50 by 10-11-2026", $footer);
    }

    #[Test]
    public function the_footer_marks_the_amount_date_and_number(): void
    {
        // arrange
        $invoice = $this->createInvoice(['date' => '2026-10-11', 'serial' => '2026.0001', 'footer_message' => ':amount :date :number']);

        // act & assert
        $this->assertSame(
            "<span class=\"invoice__footer-amount\">€\u{00A0}10,00</span> <span class=\"invoice__footer-date\">10-11-2026</span> <span class=\"invoice__footer-number\">2026.0001</span>",
            $invoice->getFooterMessage(),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    #[Test]
    #[DataProvider('titles')]
    public function the_title_shows_the_type_and_number(array $data, string $expected): void
    {
        // act & assert
        $this->assertSame($expected, $this->createInvoice($data)->getTitle());
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function titles(): array
    {
        return [
            'concept' => [[], 'Factuur CONCEPT'],
            'numbered' => [['serial' => '2026.0001'], 'Factuur 2026.0001'],
            'english concept' => [['language' => 'en'], 'Invoice DRAFT'],
            'credit invoice' => [['serial' => '2026.0002', 'items' => [['title' => 'Refund', 'unit_price' => -5]]], 'Creditfactuur 2026.0002'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    #[Test]
    #[DataProvider('filenames')]
    public function the_filename_is_built_from_the_number(array $data, string $expected): void
    {
        // act & assert
        $this->assertSame($expected, $this->createInvoice($data)->getFilename());
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function filenames(): array
    {
        return [
            'serial' => [['serial' => '2026.0001'], 'factuur-2026.0001.pdf'],
            'series and sequence' => [['series' => '2024', 'sequence' => 1, 'date' => '2024-01-15'], 'factuur-2024.00000001.pdf'],
            'concept' => [[], 'factuur-CONCEPT.pdf'],
            'english' => [['language' => 'en', 'serial' => '2026.0001'], 'invoice-2026.0001.pdf'],
            'unsafe characters' => [['serial' => 'INV/2026\\0001:"x"'], 'factuur-INV-2026-0001-x-.pdf'],
        ];
    }

    #[Test]
    #[DataProvider('languages')]
    public function language_normalises_the_code(string $language, string $expected): void
    {
        // act & assert
        $this->assertSame($expected, Invoice::make()->language($language)->getLanguage());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function languages(): array
    {
        return [
            'dutch' => ['nl', 'nl'],
            'english' => ['en', 'en'],
            'upper case' => ['NL', 'nl'],
            'region falls back to the language' => ['nl_BE', 'nl'],
            'region with a dash' => ['en-GB', 'en'],
            'spaces' => [' en ', 'en'],
        ];
    }

    #[Test]
    public function language_rejects_languages_without_translations(): void
    {
        // assert
        $this->expectException(UnsupportedLanguageException::class);
        $this->expectExceptionMessage("Language 'de' is not supported");

        // act
        Invoice::make()->language('de');
    }

    #[Test]
    public function language_accepts_languages_added_by_the_app(): void
    {
        // arrange
        Lang::addLines(['invoice.invoice' => 'Rechnung', 'invoice.concept' => 'ENTWURF'], 'de', 'invoices');

        // act
        $invoice = $this->createInvoice()->language('de');

        // assert
        $this->assertSame('de', $invoice->getLanguage());
        $this->assertSame('Rechnung ENTWURF', $invoice->getTitle());
    }

    #[Test]
    public function payment_method_accepts_the_enum_and_its_value(): void
    {
        // act & assert
        $this->assertSame(PaymentMethod::DirectDebit, Invoice::make()->paymentMethod('direct_debit')->getPaymentMethod());
        $this->assertSame(PaymentMethod::BankTransfer, Invoice::make()->paymentMethod(PaymentMethod::BankTransfer)->getPaymentMethod());
    }

    #[Test]
    public function payment_method_rejects_unknown_methods(): void
    {
        // assert
        $this->expectException(InvalidInvoiceException::class);

        // act
        Invoice::make()->paymentMethod('cash');
    }

    #[Test]
    public function an_expected_total_that_matches_passes_validation(): void
    {
        // arrange
        $invoice = $this->createInvoice(['expected_total' => '10.00']);

        // act
        $invoice->validate();

        // assert
        $this->assertSame(10.0, $invoice->getExpectedTotal());
    }

    #[Test]
    #[DataProvider('mismatchingTotals')]
    public function an_expected_total_that_differs_by_a_cent_throws(float $unitPrice, float $expectedTotal): void
    {
        // arrange
        $invoice = $this->createInvoice(['serial' => '2026.0001', 'items' => [['title' => 'A', 'unit_price' => $unitPrice]]])->expectedTotal($expectedTotal);

        // assert
        $this->expectException(ExpectedTotalMismatchException::class);
        $this->expectExceptionMessage('The total of invoice 2026.0001 is');

        // act
        $invoice->validate();
    }

    /**
     * @return array<string, array{float, float}>
     */
    public static function mismatchingTotals(): array
    {
        return [
            'one cent more' => [10.00, 10.01],
            'one cent less' => [12.85, 12.84],
            'float noise used to hide this one' => [10.10, 10.11],
            'negative' => [-10.00, -10.01],
        ];
    }

    #[Test]
    public function the_expected_total_mismatch_exception_has_the_amounts(): void
    {
        // arrange
        $invoice = $this->createInvoice(['expected_total' => 12.34]);

        // act
        try {
            $invoice->validate();
            $this->fail('The mismatch was not detected');
        } catch (ExpectedTotalMismatchException $e) {
            // assert
            $this->assertSame(1234, $e->expectedCents);
            $this->assertSame(1000, $e->actualCents);
            $this->assertSame('The total of invoice (concept) is 10.00, but 12.34 was expected (difference: -2.34)', $e->getMessage());
        }
    }

    #[Test]
    public function an_expected_total_of_null_removes_the_check(): void
    {
        // arrange
        $invoice = $this->createInvoice(['expected_total' => 12.34])->expectedTotal(null);

        // act
        $invoice->validate();

        // assert
        $this->assertNull($invoice->getExpectedTotal());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    #[Test]
    #[DataProvider('invalidInvoices')]
    public function validate_rejects_invalid_invoices(array $data, string $message): void
    {
        // arrange
        $invoice = Invoice::make($data);

        // assert
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage($message);

        // act
        $invoice->validate();
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidInvoices(): array
    {
        $item = ['title' => 'A', 'unit_price' => 1];

        return [
            'no buyer' => [['items' => [$item]], 'An invoice must have a buyer'],
            'buyer without a name' => [['buyer' => ['email' => 'jan@example.test'], 'items' => [$item]], 'The buyer of an invoice must have a name'],
            'seller without a name' => [['buyer' => ['name' => 'Jan'], 'seller' => ['email' => 'info@seller.test'], 'items' => [$item]], 'The seller must have a name'],
            'no items' => [['buyer' => ['name' => 'Jan']], 'An invoice must have at least one item'],
            'invalid item' => [['buyer' => ['name' => 'Jan'], 'items' => [$item, ['unit_price' => 1]]], 'Item at index 1 must have a title'],
        ];
    }

    #[Test]
    public function validation_errors_share_one_base_exception(): void
    {
        // assert
        $this->expectException(InvalidDocumentException::class);

        // act
        Invoice::make()->validate();
    }

    #[Test]
    public function an_invalid_buyer_array_throws_the_invoice_exception(): void
    {
        // assert
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage("Unknown key 'nam' for Buyer");

        // act
        Invoice::make(['buyer' => ['nam' => 'Jan']]);
    }

    #[Test]
    public function seller_accepts_a_seller_and_an_array(): void
    {
        // act & assert
        $this->assertSame('Other B.V.', Invoice::make()->seller(['name' => 'Other B.V.'])->getSeller()->getName());
        $this->assertSame('Third B.V.', Invoice::make()->seller(Seller::make(['name' => 'Third B.V.']))->getSeller()->getName());
    }

    #[Test]
    public function to_array_returns_every_field(): void
    {
        // arrange
        $invoice = $this->createInvoice([
            'date' => '2026-10-11',
            'serial' => '2026.0001',
            'due_date' => '2026-10-25',
            'paid' => true,
            'footer_message' => 'Thanks',
        ]);

        // act
        $array = $invoice->toArray();

        // assert
        $this->assertSame(['name' => 'Test Buyer', 'address' => null, 'postal_code' => null, 'city' => null, 'country' => null, 'email' => null, 'phone' => null, 'website' => null], $array['buyer']);
        $this->assertSame('Test Seller B.V.', data_get($array, 'seller.name'));
        $this->assertSame('nl', $array['language']);
        $this->assertSame('2026-10-11T00:00:00+02:00', $array['date']);
        $this->assertSame('2026-10-25T00:00:00+02:00', $array['due_date']);
        $this->assertSame('2026.0001', $array['serial']);
        $this->assertSame(30, $array['payment_terms_days']);
        $this->assertSame('bank_transfer', $array['payment_method']);
        $this->assertTrue($array['paid']);
        $this->assertSame('Thanks', $array['footer_message']);
        $this->assertNull($array['concept_footer_message']);
        $this->assertNull($array['expected_total']);
        $this->assertIsArray($array['items']);
        $this->assertCount(1, $array['items']);
    }
}
