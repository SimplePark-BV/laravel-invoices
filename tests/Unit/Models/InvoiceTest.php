<?php

namespace Tests\Unit\Models;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
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
    public function make_creates_instance(): void
    {
        // arrange & act
        $invoice = Invoice::make();

        // assert
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertInstanceOf(Seller::class, actual: $invoice->getSeller());
        $this->assertTrue($invoice->getItems()->isEmpty());
        $this->assertNull($invoice->pdf);
    }

    #[Test]
    public function from_array_creates_invoice(): void
    {
        // arrange
        $data = [
            'buyer' => [
                'name' => 'Test Buyer',
                'address' => '123 Test St',
                'city' => 'Test City',
                'postal_code' => '12345',
                'country' => 'Test Country',
                'email' => 'buyer@test.com',
            ],
            'date' => '2024-01-15',
            'items' => [
                [
                    'title' => 'Test Item',
                    'quantity' => 2,
                    'unit_price' => 10.50,
                    'tax_percentage' => 21,
                ],
            ],
            'series' => 'INV',
            'sequence' => 1,
            'language' => 'en',
            'forced_total' => 25.41,
        ];

        // act
        $invoice = Invoice::make($data);

        // assert
        $this->assertInstanceOf(Buyer::class, $invoice->getBuyer());
        $this->assertEquals('Test Buyer', $invoice->getBuyer()->getName());
        $this->assertNotNull($invoice->getDate());
        $this->assertEquals('2024-01-15', $invoice->getDate()->format('Y-m-d'));
        $this->assertCount(1, $invoice->getItems());
        $this->assertEquals('INV', $invoice->getSeries());
        $this->assertEquals(1, $invoice->getSequence());
        $this->assertEquals('en', $invoice->getLanguage());
        $this->assertEquals(25.41, $invoice->getForcedTotal());
    }

    #[Test]
    public function from_array_handles_partial_data(): void
    {
        // arrange
        $data = [
            'buyer' => [
                'name' => 'Test Buyer',
            ],
            'items' => [
                [
                    'title' => 'Test Item',
                    'quantity' => 1,
                    'unit_price' => 10.00,
                ],
            ],
        ];

        // act
        $invoice = Invoice::make($data);

        // assert
        $this->assertInstanceOf(Buyer::class, $invoice->getBuyer());
        $this->assertEquals('Test Buyer', $invoice->getBuyer()->getName());
        $this->assertCount(1, $invoice->getItems());

        // optional fields should use defaults or be null
        $this->assertNull($invoice->getSeries());
        $this->assertNull($invoice->getSequence());
    }

    #[Test]
    public function from_array_handles_empty_items_array(): void
    {
        // arrange
        $data = [
            'buyer' => [
                'name' => 'Test Buyer',
            ],
            'items' => [],
        ];

        // act
        $invoice = Invoice::make($data);

        // assert
        $this->assertInstanceOf(Buyer::class, $invoice->getBuyer());
        $this->assertCount(0, $invoice->getItems());
    }

    #[Test]
    public function from_array_handles_missing_optional_fields(): void
    {
        // arrange
        $data = [
            'buyer' => [
                'name' => 'Test Buyer',
            ],
            'items' => [
                [
                    'title' => 'Test Item',
                    'quantity' => 1,
                    'unit_price' => 10.00,
                ],
            ],
        ];

        // act
        $invoice = Invoice::make($data);

        // assert
        $this->assertInstanceOf(Buyer::class, $invoice->getBuyer());
        $this->assertNull($invoice->getSeries());
        $this->assertNull($invoice->getSequence());
        $this->assertNull($invoice->getForcedTotal());
    }

    #[Test]
    public function to_array_handles_null_values(): void
    {
        // arrange
        $invoice = Invoice::make();
        $buyer = Buyer::make(['name' => 'Test Buyer']);
        $invoice->buyer($buyer);
        $item = $this->createInvoiceItem();
        $invoice->addItem($item);

        // act
        $array = $invoice->toArray();

        // assert
        $this->assertIsArray($array); // @phpstan-ignore-line method.alreadyNarrowedType
        $this->assertNull($array['series']);
        $this->assertNull($array['sequence']);
        $this->assertNull($array['forced_total']);
    }

    #[Test]
    public function to_array_returns_structure(): void
    {
        // arrange
        $buyer = Buyer::make([
            'name' => 'Test Buyer',
            'email' => 'buyer@test.com',
        ]);

        $item = $this->createInvoiceItem('Test Item', 2, 10.50, 21);

        $invoice = Invoice::make();
        $invoice->buyer($buyer);
        $invoice->date('2024-01-15');
        $invoice->addItem($item);
        $invoice->series('INV');
        $invoice->sequence(1);
        $invoice->language('en');

        // act
        $array = $invoice->toArray();

        // assert
        $this->assertIsArray($array); // @phpstan-ignore-line method.alreadyNarrowedType
        $this->assertArrayHasKey('buyer', $array);
        $this->assertArrayHasKey('date', $array);
        $this->assertArrayHasKey('items', $array);
        $this->assertArrayHasKey('series', $array);
        $this->assertArrayHasKey('sequence', $array);
        $this->assertArrayHasKey('language', $array);
        $this->assertEquals('Test Buyer', $array['buyer']['name']);
        $this->assertCount(1, $array['items']);
    }

    #[Test]
    public function buyer_assignment(): void
    {
        // arrange
        $invoice = Invoice::make();
        $buyer = Buyer::make(['name' => 'Test Buyer']);

        // act
        $result = $invoice->buyer($buyer);

        // assert
        $this->assertSame($invoice, $result);
        $this->assertSame($buyer, $invoice->getBuyer());
    }

    #[Test]
    #[DataProvider('date_handling_data_provider')]
    public function date_handling(mixed $dateInput, string $expectedFormat): void
    {
        // arrange
        $invoice = Invoice::make();

        // act
        $result = $invoice->date($dateInput);

        // assert
        $this->assertSame($invoice, $result);
        $this->assertNotNull($invoice->getDate());
        $this->assertEquals($expectedFormat, $invoice->getDate()->format('Y-m-d'));
    }

    /**
     * @return array<string, array{0: mixed, 1: string}>
     */
    public static function date_handling_data_provider(): array
    {
        return [
            'carbon instance' => [Carbon::parse('2024-01-15'), '2024-01-15'],
            'string' => ['2024-01-15', '2024-01-15'],
        ];
    }

    #[Test]
    public function add_item(): void
    {
        // arrange
        $invoice = Invoice::make();
        $item = $this->createInvoiceItem();

        // act
        $result = $invoice->items([$item]);

        // assert
        $this->assertSame($invoice, $result);
        $this->assertCount(1, $invoice->getItems());
        $this->assertSame($item, $invoice->getItems()->first());
    }

    #[Test]
    public function add_items(): void
    {
        // arrange
        $invoice = Invoice::make();
        $item1 = $this->createInvoiceItem('Item 1');
        $item2 = $this->createInvoiceItem('Item 2', 2, 20.00);

        // act
        $result = $invoice->items([$item1, $item2]);

        // assert
        $this->assertSame($invoice, $result);
        $this->assertCount(2, $invoice->getItems());
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    #[Test]
    #[DataProvider('items_total_data_provider')]
    public function items_total(array $items, float $expected): void
    {
        // arrange
        $invoice = Invoice::make();
        $invoiceItems = [];
        foreach ($items as $itemData) {
            $invoiceItems[] = InvoiceItem::make($itemData);
        }
        $invoice->items($invoiceItems);

        // act
        $total = $invoice->getItemsTotal();

        // assert
        $this->assertEquals($expected, $total);
    }

    /**
     * @return array<string, array{0: array<int, array<string, mixed>>, 1: float}>
     */
    public static function items_total_data_provider(): array
    {
        return [
            'single item' => [
                [['title' => 'Item', 'quantity' => 2, 'unit_price' => 10.00]],
                20.00,
            ],
            'multiple items' => [
                [
                    ['title' => 'Item 1', 'quantity' => 2, 'unit_price' => 10.00],
                    ['title' => 'Item 2', 'quantity' => 3, 'unit_price' => 20.00],
                ],
                80.00, // (2 * 10) + (3 * 20) = 80
            ],
        ];
    }

    #[Test]
    public function subtotal(): void
    {
        // arrange
        $invoice = Invoice::make();
        $item = $this->createInvoiceItem('Item', 2, 121.00, 21); // 100 + 21% tax
        $invoice->addItem($item);

        // act
        $subTotal = $invoice->getSubTotal();

        // assert
        // itemsTotal = 242, taxAmount = 242 * 0.21 / 1.21 = 42, subtotal = 242 - 42 = 200
        $this->assertEquals(242.00, $invoice->getItemsTotal());
        $this->assertEquals(200.00, round($subTotal, 2));
    }

    #[Test]
    #[DataProvider('total_data_provider')]
    public function total(?float $forcedTotal, float $expected): void
    {
        // arrange
        $invoice = Invoice::make();
        $item = $this->createInvoiceItem('Item', 2);
        $invoice->addItem($item);

        if ($forcedTotal !== null) {
            $invoice->forcedTotal($forcedTotal);
        }

        // act
        $total = $invoice->getTotal();

        // assert
        $this->assertEquals($expected, $total);
    }

    /**
     * @return array<string, array{0: float|null, 1: float}>
     */
    public static function total_data_provider(): array
    {
        return [
            'no forced total' => [null, 20.00],
            'with forced total' => [25.50, 25.50],
        ];
    }

    #[Test]
    public function tax_amount(): void
    {
        // arrange
        $invoice = Invoice::make();
        $item1 = $this->createInvoiceItem('Item 1', 1, 121.00, 21); // 100 + 21% tax
        $item2 = $this->createInvoiceItem('Item 2', 1, 110.00, 10); // 100 + 10% tax

        $invoice->items([$item1, $item2]);

        // act
        $taxAmount = $invoice->getTaxAmount();

        // assert
        // tax1 = 121 * 0.21 / 1.21 = 21
        // tax2 = 110 * 0.10 / 1.10 = 10
        // total tax = 31
        $this->assertEquals(31.00, round($taxAmount, 2));
    }

    #[Test]
    public function tax_groups(): void
    {
        // arrange
        $invoice = Invoice::make();
        $item1 = $this->createInvoiceItem('Item 1', 1, 10.00, 21);
        $item2 = $this->createInvoiceItem('Item 2', 1, 10.00, 9);
        $item3 = $this->createInvoiceItem('Item 3', 1, 10.00, 21);
        $item4 = $this->createInvoiceItem('Item 4', 1, 10.00, null);

        $invoice->items([$item1, $item2, $item3, $item4]);

        // act
        $taxGroups = $invoice->getTaxGroups();

        // assert
        $this->assertCount(2, $taxGroups);
        $this->assertEquals(21.0, $taxGroups->first());
        $this->assertEquals(9.0, $taxGroups->last());
    }

    #[Test]
    #[DataProvider('tax_amount_for_group_data_provider')]
    public function tax_amount_for_group(float $taxPercentage, float $expected): void
    {
        // arrange
        $invoice = Invoice::make();
        $item1 = $this->createInvoiceItem('Item 1', 1, 121.00, 21);
        $item2 = $this->createInvoiceItem('Item 2', 1, 110.00, 10);

        $invoice->items([$item1, $item2]);

        // act
        $tax = $invoice->getTaxAmountForTaxGroup($taxPercentage);

        // assert
        $this->assertEquals($expected, round($tax, 2));
    }

    /**
     * @return array<string, array{0: float, 1: float}>
     */
    public static function tax_amount_for_group_data_provider(): array
    {
        return [
            'tax 21' => [21, 21.00],
            'tax 10' => [10, 10.00],
        ];
    }

    #[Test]
    public function subtotal_for_tax_group(): void
    {
        // arrange
        $invoice = Invoice::make();
        $item = $this->createInvoiceItem('Item', 1, 121.00, 21);
        $invoice->addItem($item);

        // act
        $subTotal = $invoice->getSubTotalForTaxGroup(21);

        // assert
        // itemsTotal = 121, taxAmount = 21, subtotal = 100
        $this->assertEquals(100.00, round($subTotal, 2));
    }

    #[Test]
    public function forced_total(): void
    {
        // arrange
        $invoice = Invoice::make();

        // act
        $result = $invoice->forcedTotal(100.50);

        // assert
        $this->assertSame($invoice, $result);
        $this->assertEquals(100.50, $invoice->getForcedTotal());
        $this->assertEquals(100.50, $invoice->getTotal());
    }

    #[Test]
    public function is_rendered_returns_false(): void
    {
        // arrange
        $invoice = Invoice::make();

        // act
        $isRendered = $invoice->isRendered();

        // assert
        $this->assertFalse($isRendered);
    }

    #[Test]
    public function clear_pdf(): void
    {
        // arrange
        $invoice = Invoice::make();
        /** @var \Barryvdh\DomPDF\PDF&\Mockery\MockInterface $mockPdf */
        $mockPdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $invoice->pdf = $mockPdf; // mock pdf object

        // act
        $result = $invoice->clearPdf();

        // assert
        $this->assertSame($invoice, $result);
        $this->assertNull($invoice->pdf);
    }

    #[Test]
    public function seller_initialized_from_config(): void
    {
        // arrange
        Config::set('invoices.seller.name', 'Test Seller');
        Config::set('invoices.seller.address', 'Test Address');

        // act
        $invoice = Invoice::make();

        // assert
        $this->assertInstanceOf(Seller::class, actual: $invoice->getSeller());
        $this->assertEquals('Test Seller', $invoice->getSeller()->getName());
        $this->assertEquals('Test Address', $invoice->getSeller()->getAddress());
    }

    #[Test]
    public function formatted_total(): void
    {
        // arrange
        Config::set('invoices.currency_symbol', '€');
        Config::set('invoices.decimal_separator', ',');
        Config::set('invoices.thousands_separator', '.');

        $invoice = Invoice::make();
        $item = $this->createInvoiceItem('Item', 1, 10.50);
        $invoice->addItem($item);

        // act
        $formatted = $invoice->getFormattedTotal();

        // assert
        $this->assertEquals('€ 10,50', $formatted);
    }

    #[Test]
    public function formatted_subtotal(): void
    {
        // arrange
        $invoice = Invoice::make();
        $item = $this->createInvoiceItem('Item', 1, 121.00, 21);
        $invoice->addItem($item);

        // act
        $formattedSubTotal = $invoice->getFormattedSubTotal();

        // assert
        // formattedSubTotal returns a float, should be exactly 100.00 (121 - 21 tax)
        $this->assertIsFloat($formattedSubTotal); // @phpstan-ignore-line method.alreadyNarrowedType
        $this->assertEquals(100.00, $formattedSubTotal);
    }

    #[Test]
    #[DataProvider('get_number_data_provider')]
    public function get_number(?string $series, int|string|null $sequence, ?string $expected): void
    {
        // arrange
        $invoice = Invoice::make();
        if ($series !== null) {
            $invoice->series($series);
        }
        if ($sequence !== null) {
            $invoice->sequence($sequence);
        }

        // act
        $number = $invoice->getNumber();

        // assert
        $this->assertEquals($expected, $number);
    }

    /**
     * @return array<string, array{0: string|null, 1: int|string|null, 2: string|null}>
     */
    public static function get_number_data_provider(): array
    {
        return [
            'both series and sequence' => ['INV', 123, 'INV.00000123'],
            'only series' => ['INV', null, 'INV'],
            'only sequence' => [null, 123, '00000123'],
            'neither set' => [null, null, null],
            'sequence with single digit' => ['INV', 1, 'INV.00000001'],
            'sequence with large number' => ['INV', 99999999, 'INV.99999999'],
            'sequence as numeric string' => ['INV', '123', 'INV.00000123'],
            'sequence as non-numeric string' => ['INV', 'ABC', 'INV.ABC'],
            'only sequence as string' => [null, 'ABC', 'ABC'],
        ];
    }

    #[Test]
    public function series_setter(): void
    {
        // arrange
        $invoice = Invoice::make();

        // act
        $result = $invoice->series('INV');

        // assert
        $this->assertSame($invoice, $result);
        $this->assertEquals('INV', $invoice->getSeries());
    }

    #[Test]
    public function sequence_setter(): void
    {
        // arrange
        $invoice = Invoice::make();

        // act
        $result = $invoice->sequence(123);

        // assert
        $this->assertSame($invoice, $result);
        $this->assertEquals(123, $invoice->getSequence());
    }

    #[Test]
    public function sequence_setter_with_string(): void
    {
        // arrange
        $invoice = Invoice::make();

        // act
        $result = $invoice->sequence('ABC');

        // assert
        $this->assertSame($invoice, $result);
        $this->assertEquals('ABC', $invoice->getSequence());
    }

    #[Test]
    public function serial_setter(): void
    {
        // arrange
        $invoice = Invoice::make();

        // act
        $result = $invoice->serial('INV.00000123');

        // assert
        $this->assertSame($invoice, $result);
        $this->assertEquals('INV.00000123', $invoice->getSerial());
    }

    #[Test]
    public function get_number_with_serial(): void
    {
        // arrange
        $invoice = Invoice::make();
        $invoice->serial('INV.00000123');

        // act
        $number = $invoice->getNumber();

        // assert
        $this->assertEquals('INV.00000123', $number);
    }

    #[Test]
    public function serial_overrides_series_sequence(): void
    {
        // arrange
        $invoice = Invoice::make();
        $invoice->series('INV');
        $invoice->sequence(456);
        $invoice->serial('CUSTOM.999');

        // act
        $number = $invoice->getNumber();

        // assert
        $this->assertEquals('CUSTOM.999', $number);
        // verify series and sequence are still set but ignored
        $this->assertEquals('INV', $invoice->getSeries());
        $this->assertEquals(456, $invoice->getSequence());
    }

    #[Test]
    public function serial_in_from_array(): void
    {
        // arrange
        $data = [
            'serial' => 'INV.00000123',
        ];

        // act
        $invoice = Invoice::make($data);

        // assert
        $this->assertEquals('INV.00000123', $invoice->getSerial());
        $this->assertEquals('INV.00000123', $invoice->getNumber());
    }

    #[Test]
    public function serial_in_to_array(): void
    {
        // arrange
        $invoice = Invoice::make();
        $invoice->serial('INV.00000123');

        // act
        $array = $invoice->toArray();

        // assert
        $this->assertArrayHasKey('serial', $array);
        $this->assertEquals('INV.00000123', $array['serial']);
    }

    #[Test]
    public function serial_null_in_to_array(): void
    {
        // arrange
        $invoice = Invoice::make();

        // act
        $array = $invoice->toArray();

        // assert
        $this->assertArrayHasKey('serial', $array);
        $this->assertNull($array['serial']);
    }

    #[Test]
    public function get_number_without_serial_falls_back_to_series_sequence(): void
    {
        // arrange
        $invoice = Invoice::make();
        $invoice->series('INV');
        $invoice->sequence(123);

        // act
        $number = $invoice->getNumber();

        // assert
        $this->assertEquals('INV.00000123', $number);
    }

    #[Test]
    #[DataProvider('formatted_date_data_provider')]
    public function formatted_date(string $dateFormat, string $expected): void
    {
        // arrange
        $invoice = Invoice::make();
        $invoice->date('2024-01-15');
        $invoice->dateFormat($dateFormat);

        // act
        $formatted = $invoice->getFormattedDate();

        // assert
        $this->assertEquals($expected, $formatted);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function formatted_date_data_provider(): array
    {
        return [
            'default format' => ['d-m-Y', '15-01-2024'],
            'custom format' => ['Y/m/d', '2024/01/15'],
        ];
    }

    #[Test]
    #[DataProvider('formatted_due_date_data_provider')]
    public function formatted_due_date(int $payUntilDays, string $expected): void
    {
        // arrange
        $invoice = Invoice::make();
        $invoice->date('2024-01-15');
        $invoice->dateFormat('d-m-Y');
        $invoice->payUntilDays($payUntilDays);

        // act
        $formatted = $invoice->getFormattedDueDate();

        // assert
        $this->assertEquals($expected, $formatted);
    }

    /**
     * @return array<string, array{0: int, 1: string}>
     */
    public static function formatted_due_date_data_provider(): array
    {
        return [
            'default 30 days' => [30, '14-02-2024'],
            'custom 14 days' => [14, '29-01-2024'],
        ];
    }

    #[Test]
    public function template_setter(): void
    {
        // arrange
        $invoice = Invoice::make();

        // act
        $result = $invoice->template('custom-template');

        // assert
        $this->assertSame($invoice, $result);
        $this->assertEquals('custom-template', $invoice->getTemplate());
    }

    #[Test]
    public function set_language_validates_language(): void
    {
        // arrange
        $invoice = Invoice::make();

        // assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Language 'invalid' is not supported");

        // act
        $invoice->language('invalid');
    }

    #[Test]
    public function set_language_with_valid_language(): void
    {
        // arrange
        $invoice = Invoice::make();

        // act
        $result = $invoice->language('en');

        // assert
        $this->assertSame($invoice, $result);
        $this->assertEquals('en', $invoice->getLanguage());
    }

    #[Test]
    public function get_available_languages(): void
    {
        // arrange
        $invoice = Invoice::make();

        // act
        $languages = $invoice->getAvailableLanguages();

        // assert
        $this->assertIsArray($languages); // @phpstan-ignore-line method.alreadyNarrowedType

        // should at least contain 'en' and 'nl' based on the project structure
        $this->assertContains('en', $languages);
        $this->assertContains('nl', $languages);
    }

    #[Test]
    public function footer_message(): void
    {
        // arrange
        $invoice = Invoice::make();
        $buyer = Buyer::make(['name' => 'Test Buyer']);
        $invoice->buyer($buyer);
        $item = $this->createInvoiceItem('Item');
        $invoice->addItem($item);
        $invoice->date('2024-01-15');
        $invoice->payUntilDays(30);

        // act
        $message = $invoice->getFooterMessage();

        // assert
        $this->assertIsString($message); // @phpstan-ignore-line method.alreadyNarrowedType
        $this->assertStringContainsString('10,00', $message);
        $this->assertStringContainsString('14-02-2024', $message);
    }

    #[Test]
    #[DataProvider('set_logo_data_provider')]
    public function set_logo(?string $logoPath, ?string $initialLogo, ?string $expected): void
    {
        // arrange
        $invoice = Invoice::make();
        if ($initialLogo !== null) {
            $invoice->logo($initialLogo);
        }

        // act
        $result = $invoice->logo($logoPath);

        // assert
        $this->assertSame($invoice, $result);
        $this->assertEquals($expected, $invoice->getLogo());
    }

    /**
     * @return array<string, array{0: string|null, 1: string|null, 2: string|null}>
     */
    public static function set_logo_data_provider(): array
    {
        return [
            'set logo path' => ['/path/to/logo.png', null, '/path/to/logo.png'],
            'set logo to null' => [null, '/path/to/logo.png', null],
        ];
    }

    #[Test]
    #[DataProvider('get_logo_data_uri_null_cases_data_provider')]
    public function get_logo_data_uri_returns_null(?string $logoPath): void
    {
        // arrange
        $invoice = Invoice::make();
        $invoice->logo($logoPath);

        // act
        $dataUri = $invoice->getLogoDataUri();

        // assert
        $this->assertNull($dataUri);
    }

    /**
     * @return array<string, array{0: string|null}>
     */
    public static function get_logo_data_uri_null_cases_data_provider(): array
    {
        return [
            'no logo' => [null],
            'file not exists' => ['/nonexistent/path/to/logo.png'],
        ];
    }
}
