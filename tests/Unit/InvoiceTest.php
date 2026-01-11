<?php

namespace Tests\Unit;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Buyer;
use SimpleParkBv\Invoices\Invoice;
use SimpleParkBv\Invoices\InvoiceItem;
use SimpleParkBv\Invoices\Seller;
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
        $this->assertInstanceOf(Seller::class, $invoice->seller);
        $this->assertTrue($invoice->items->isEmpty());
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
        $invoice = Invoice::fromArray($data);

        // assert
        $this->assertInstanceOf(Buyer::class, $invoice->buyer);
        $this->assertEquals('Test Buyer', $invoice->buyer->name);
        $this->assertEquals('2024-01-15', $invoice->date->format('Y-m-d'));
        $this->assertCount(1, $invoice->items);
        $this->assertEquals('INV', $invoice->series);
        $this->assertEquals(1, $invoice->sequence);
        $this->assertEquals('en', $invoice->language);
        $this->assertEquals(25.41, $invoice->forcedTotal);
    }

    #[Test]
    public function to_array_returns_structure(): void
    {
        // arrange
        $buyer = Buyer::make();
        $buyer->name = 'Test Buyer';
        $buyer->email = 'buyer@test.com';

        $item = InvoiceItem::make();
        $item->title = 'Test Item';
        $item->quantity = 2;
        $item->unit_price = 10.50;
        $item->tax_percentage = 21;

        $invoice = Invoice::make();
        $invoice->buyer($buyer);
        $invoice->date('2024-01-15');
        $invoice->addItem($item);
        $invoice->series('INV');
        $invoice->sequence(1);
        $invoice->setLanguage('en');

        // act
        $array = $invoice->toArray();

        // assert
        $this->assertIsArray($array);
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
        $buyer = Buyer::make();
        $buyer->name = 'Test Buyer';

        // act
        $result = $invoice->buyer($buyer);

        // assert
        $this->assertSame($invoice, $result);
        $this->assertSame($buyer, $invoice->buyer);
    }

    #[Test]
    #[DataProvider('date_handling_data_provider')]
    public function date_handling(mixed $dateInput, string $expectedFormat): void
    {
        // arrange
        $invoice = Invoice::make();
        $date = is_string($dateInput) ? Carbon::parse($dateInput) : $dateInput;

        // act
        $result = $invoice->date($dateInput);

        // assert
        $this->assertSame($invoice, $result);
        $this->assertEquals($expectedFormat, $invoice->date->format('Y-m-d'));
    }

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
        $item = InvoiceItem::make();
        $item->title = 'Test Item';
        $item->quantity = 1;
        $item->unit_price = 10.00;

        // act
        $result = $invoice->addItem($item);

        // assert
        $this->assertSame($invoice, $result);
        $this->assertCount(1, $invoice->items);
        $this->assertSame($item, $invoice->items->first());
    }

    #[Test]
    public function add_items(): void
    {
        // arrange
        $invoice = Invoice::make();
        $item1 = InvoiceItem::make();
        $item1->title = 'Item 1';
        $item1->quantity = 1;
        $item1->unit_price = 10.00;

        $item2 = InvoiceItem::make();
        $item2->title = 'Item 2';
        $item2->quantity = 2;
        $item2->unit_price = 20.00;

        // act
        $result = $invoice->addItems([$item1, $item2]);

        // assert
        $this->assertSame($invoice, $result);
        $this->assertCount(2, $invoice->items);
    }

    #[Test]
    #[DataProvider('items_total_data_provider')]
    public function items_total(array $items, float $expected): void
    {
        // arrange
        $invoice = Invoice::make();
        foreach ($items as $itemData) {
            $item = InvoiceItem::make();
            $item->title = $itemData['title'];
            $item->quantity = $itemData['quantity'];
            $item->unit_price = $itemData['unit_price'];
            $invoice->addItem($item);
        }

        // act
        $total = $invoice->itemsTotal();

        // assert
        $this->assertEquals($expected, $total);
    }

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
        $item = InvoiceItem::make();
        $item->title = 'Item';
        $item->quantity = 2;
        $item->unit_price = 121.00; // 100 + 21% tax
        $item->tax_percentage = 21;
        $invoice->addItem($item);

        // act
        $subTotal = $invoice->subTotal();

        // assert
        // itemsTotal = 242, taxAmount = 242 * 0.21 / 1.21 = 42, subtotal = 242 - 42 = 200
        $this->assertEquals(242.00, $invoice->itemsTotal());
        $this->assertEquals(200.00, round($subTotal, 2));
    }

    #[Test]
    #[DataProvider('total_data_provider')]
    public function total(?float $forcedTotal, float $expected): void
    {
        // arrange
        $invoice = Invoice::make();
        $item = InvoiceItem::make();
        $item->title = 'Item';
        $item->quantity = 2;
        $item->unit_price = 10.00;
        $invoice->addItem($item);

        if ($forcedTotal !== null) {
            $invoice->forcedTotal($forcedTotal);
        }

        // act
        $total = $invoice->total();

        // assert
        $this->assertEquals($expected, $total);
    }

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
        $item1 = InvoiceItem::make();
        $item1->title = 'Item 1';
        $item1->quantity = 1;
        $item1->unit_price = 121.00; // 100 + 21% tax
        $item1->tax_percentage = 21;

        $item2 = InvoiceItem::make();
        $item2->title = 'Item 2';
        $item2->quantity = 1;
        $item2->unit_price = 110.00; // 100 + 10% tax
        $item2->tax_percentage = 10;

        $invoice->addItems([$item1, $item2]);

        // act
        $taxAmount = $invoice->taxAmount();

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
        $item1 = InvoiceItem::make();
        $item1->title = 'Item 1';
        $item1->quantity = 1;
        $item1->unit_price = 10.00;
        $item1->tax_percentage = 21;

        $item2 = InvoiceItem::make();
        $item2->title = 'Item 2';
        $item2->quantity = 1;
        $item2->unit_price = 10.00;
        $item2->tax_percentage = 9;

        $item3 = InvoiceItem::make();
        $item3->title = 'Item 3';
        $item3->quantity = 1;
        $item3->unit_price = 10.00;
        $item3->tax_percentage = 21;

        $item4 = InvoiceItem::make();
        $item4->title = 'Item 4';
        $item4->quantity = 1;
        $item4->unit_price = 10.00;
        $item4->tax_percentage = null;

        $invoice->addItems([$item1, $item2, $item3, $item4]);

        // act
        $taxGroups = $invoice->taxGroups();

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
        $item1 = InvoiceItem::make();
        $item1->title = 'Item 1';
        $item1->quantity = 1;
        $item1->unit_price = 121.00;
        $item1->tax_percentage = 21;

        $item2 = InvoiceItem::make();
        $item2->title = 'Item 2';
        $item2->quantity = 1;
        $item2->unit_price = 110.00;
        $item2->tax_percentage = 10;

        $invoice->addItems([$item1, $item2]);

        // act
        $tax = $invoice->taxAmountForTaxGroup($taxPercentage);

        // assert
        $this->assertEquals($expected, round($tax, 2));
    }

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
        $item = InvoiceItem::make();
        $item->title = 'Item';
        $item->quantity = 1;
        $item->unit_price = 121.00;
        $item->tax_percentage = 21;
        $invoice->addItem($item);

        // act
        $subTotal = $invoice->subTotalForTaxGroup(21);

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
        $this->assertEquals(100.50, $invoice->forcedTotal);
        $this->assertEquals(100.50, $invoice->total());
    }

    #[Test]
    public function validate_passes(): void
    {
        // arrange
        $invoice = $this->create_valid_invoice();

        // act & assert
        $this->expectNotToPerformAssertions();
        $invoice->validate();
    }

    #[Test]
    #[DataProvider('validation_errors_data_provider')]
    public function validate_throws_exception(string $scenario, callable $setup, string $expectedMessage): void
    {
        // arrange
        $invoice = Invoice::make();
        $setup($invoice);

        // assert
        $this->expectException(\SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException::class);
        $this->expectExceptionMessage($expectedMessage);

        // act
        $invoice->validate();
    }

    public static function validation_errors_data_provider(): array
    {
        return [
            'missing buyer' => [
                'missing buyer',
                function ($invoice) {
                    $item = InvoiceItem::make();
                    $item->title = 'Test Item';
                    $item->quantity = 1;
                    $item->unit_price = 10.00;
                    $invoice->addItem($item);
                },
                'Buyer is required for invoice',
            ],
            'empty items' => [
                'empty items',
                function ($invoice) {
                    $buyer = Buyer::make();
                    $buyer->name = 'Test Buyer';
                    $invoice->buyer($buyer);
                },
                'Invoice must have at least one item',
            ],
            'empty title' => [
                'empty title',
                function ($invoice) {
                    $buyer = Buyer::make();
                    $buyer->name = 'Test Buyer';
                    $invoice->buyer($buyer);
                    $item = InvoiceItem::make();
                    $item->title = '';
                    $item->quantity = 1;
                    $item->unit_price = 10.00;
                    $invoice->addItem($item);
                },
                'Item at index 0 must have a title',
            ],
            'zero quantity' => [
                'zero quantity',
                function ($invoice) {
                    $buyer = Buyer::make();
                    $buyer->name = 'Test Buyer';
                    $invoice->buyer($buyer);
                    $item = InvoiceItem::make();
                    $item->title = 'Test Item';
                    $item->quantity = 0;
                    $item->unit_price = 10.00;
                    $invoice->addItem($item);
                },
                'Item at index 0 must have a quantity greater than 0',
            ],
            'negative unit price' => [
                'negative unit price',
                function ($invoice) {
                    $buyer = Buyer::make();
                    $buyer->name = 'Test Buyer';
                    $invoice->buyer($buyer);
                    $item = InvoiceItem::make();
                    $item->title = 'Test Item';
                    $item->quantity = 1;
                    $item->unit_price = -10.00;
                    $invoice->addItem($item);
                },
                'Item at index 0 must have a unit_price greater than or equal to 0',
            ],
            'invalid tax percentage' => [
                'invalid tax percentage',
                function ($invoice) {
                    $buyer = Buyer::make();
                    $buyer->name = 'Test Buyer';
                    $invoice->buyer($buyer);
                    $item = InvoiceItem::make();
                    $item->title = 'Test Item';
                    $item->quantity = 1;
                    $item->unit_price = 10.00;
                    $item->tax_percentage = 101;
                    $invoice->addItem($item);
                },
                'Item at index 0 must have a tax_percentage between 0 and 100, or null',
            ],
        ];
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
        $invoice->pdf = new \stdClass; // mock pdf object

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
        $this->assertInstanceOf(Seller::class, $invoice->seller);
        $this->assertEquals('Test Seller', $invoice->seller->name);
        $this->assertEquals('Test Address', $invoice->seller->address);
    }

    #[Test]
    public function formatted_total(): void
    {
        // arrange
        $invoice = Invoice::make();
        $item = InvoiceItem::make();
        $item->title = 'Item';
        $item->quantity = 1;
        $item->unit_price = 10.50;
        $invoice->addItem($item);

        // act
        $formatted = $invoice->formattedTotal();

        // assert
        $this->assertStringContainsString('10,50', $formatted);
        $this->assertStringContainsString('€', $formatted);
    }

    #[Test]
    public function formatted_subtotal(): void
    {
        // arrange
        $invoice = Invoice::make();
        $item = InvoiceItem::make();
        $item->title = 'Item';
        $item->quantity = 1;
        $item->unit_price = 121.00;
        $item->tax_percentage = 21;
        $invoice->addItem($item);

        // act
        $formattedSubTotal = $invoice->formattedSubTotal();

        // assert
        // should be around 100 (121 - 21 tax)
        $this->assertGreaterThanOrEqual(99.99, $formattedSubTotal);
        $this->assertLessThanOrEqual(100.01, $formattedSubTotal);
    }
}
