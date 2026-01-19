<?php

namespace Tests\Unit\Models;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Models\Buyer;
use SimpleParkBv\Invoices\Models\ReceiptItem;
use SimpleParkBv\Invoices\Models\Seller;
use SimpleParkBv\Invoices\Models\UsageReceipt;
use Tests\TestCase;
use Tests\Traits\CreatesTestReceipts;

final class UsageReceiptTest extends TestCase
{
    use CreatesTestReceipts;

    #[Test]
    public function make_creates_instance(): void
    {
        // arrange & act
        $receipt = UsageReceipt::make();

        // assert
        $this->assertInstanceOf(UsageReceipt::class, $receipt);
        $this->assertInstanceOf(Seller::class, $receipt->seller);
        $this->assertTrue($receipt->items->isEmpty());
        $this->assertNull($receipt->pdf);
        $this->assertEquals('usage-receipt.index', $receipt->template);
    }

    #[Test]
    public function from_array_creates_receipt(): void
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
                    'user' => 'John Doe',
                    'identifier' => 'ABC-123',
                    'start_date' => '2024-01-15 10:00:00',
                    'end_date' => '2024-01-15 12:00:00',
                    'category' => 'Standard Parking',
                    'price' => 5.50,
                ],
            ],
            'document_id' => 'DOC-12345',
            'user_id' => 'USER-67890',
            'language' => 'en',
            'note' => 'Test note',
            'forced_total' => 25.50,
        ];

        // act
        $receipt = UsageReceipt::make($data);

        // assert
        $this->assertInstanceOf(Buyer::class, $receipt->buyer);
        $this->assertEquals('Test Buyer', $receipt->buyer->getName());
        $this->assertNotNull($receipt->date);
        $this->assertEquals('2024-01-15', $receipt->date->format('Y-m-d'));
        $this->assertCount(1, $receipt->items);
        $this->assertEquals('DOC-12345', $receipt->documentId);
        $this->assertEquals('USER-67890', $receipt->userId);
        $this->assertEquals('en', $receipt->language);
        $this->assertEquals('Test note', $receipt->note);
        $this->assertEquals(25.50, $receipt->forcedTotal);
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
                    'user' => 'John Doe',
                    'identifier' => 'ABC-123',
                    'start_date' => '2024-01-15 10:00:00',
                    'end_date' => '2024-01-15 12:00:00',
                    'category' => 'Standard Parking',
                    'price' => 5.50,
                ],
            ],
        ];

        // act
        $receipt = UsageReceipt::make($data);

        // assert
        $this->assertInstanceOf(Buyer::class, $receipt->buyer);
        $this->assertEquals('Test Buyer', $receipt->buyer->getName());
        $this->assertCount(1, $receipt->items);

        // optional fields should use defaults or be null
        $this->assertNull($receipt->documentId);
        $this->assertNull($receipt->userId);
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
        $receipt = UsageReceipt::make($data);

        // assert
        $this->assertInstanceOf(Buyer::class, $receipt->buyer);
        $this->assertCount(0, $receipt->items);
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
                    'user' => 'John Doe',
                    'identifier' => 'ABC-123',
                    'start_date' => '2024-01-15 10:00:00',
                    'end_date' => '2024-01-15 12:00:00',
                    'category' => 'Standard Parking',
                    'price' => 5.50,
                ],
            ],
        ];

        // act
        $receipt = UsageReceipt::make($data);

        // assert
        $this->assertInstanceOf(Buyer::class, $receipt->buyer);
        $this->assertNull($receipt->documentId);
        $this->assertNull($receipt->userId);
        $this->assertNull($receipt->forcedTotal);
        $this->assertNull($receipt->note);
    }

    #[Test]
    public function to_array_handles_null_values(): void
    {
        // arrange
        $receipt = UsageReceipt::make();
        $buyer = Buyer::make(['name' => 'Test Buyer']);
        $receipt->buyer($buyer);
        $item = ReceiptItem::make([
            'user' => 'John Doe',
            'identifier' => 'ABC-123',
            'start_date' => '2024-01-15 10:00:00',
            'end_date' => '2024-01-15 12:00:00',
            'category' => 'Standard Parking',
            'price' => 5.50,
        ]);
        $receipt->items([$item]);

        // act
        $array = $receipt->toArray();

        // assert
        $this->assertIsArray($array); // @phpstan-ignore-line method.alreadyNarrowedType
        $this->assertNull($array['document_id']);
        $this->assertNull($array['user_id']);
        $this->assertNull($array['forced_total']);
        $this->assertNull($array['note']);
    }

    #[Test]
    public function to_array_returns_structure(): void
    {
        // arrange
        $buyer = Buyer::make([
            'name' => 'Test Buyer',
            'email' => 'buyer@test.com',
        ]);

        $item = ReceiptItem::make([
            'user' => 'John Doe',
            'identifier' => 'ABC-123',
            'start_date' => '2024-01-15 10:00:00',
            'end_date' => '2024-01-15 12:00:00',
            'category' => 'Standard Parking',
            'price' => 5.50,
        ]);

        $receipt = UsageReceipt::make();
        $receipt->buyer($buyer);
        $receipt->date('2024-01-15');
        $receipt->items([$item]);
        $receipt->documentId('DOC-12345');
        $receipt->userId('USER-67890');
        $receipt->language('en');
        $receipt->note('Test note');

        // act
        $array = $receipt->toArray();

        // assert
        $this->assertIsArray($array); // @phpstan-ignore-line method.alreadyNarrowedType
        $this->assertArrayHasKey('buyer', $array);
        $this->assertArrayHasKey('date', $array);
        $this->assertArrayHasKey('items', $array);
        $this->assertArrayHasKey('document_id', $array);
        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('language', $array);
        $this->assertArrayHasKey('note', $array);
        $this->assertEquals('Test Buyer', $array['buyer']['name']);
        $this->assertCount(1, $array['items']);
        $this->assertEquals('DOC-12345', $array['document_id']);
        $this->assertEquals('USER-67890', $array['user_id']);
        $this->assertEquals('Test note', $array['note']);
    }

    #[Test]
    public function buyer_assignment(): void
    {
        // arrange
        $receipt = UsageReceipt::make();
        $buyer = Buyer::make(['name' => 'Test Buyer']);

        // act
        $result = $receipt->buyer($buyer);

        // assert
        $this->assertSame($receipt, $result);
        $this->assertSame($buyer, $receipt->buyer);
    }

    #[Test]
    #[DataProvider('date_handling_data_provider')]
    public function date_handling(mixed $dateInput, string $expectedFormat): void
    {
        // arrange
        $receipt = UsageReceipt::make();
        $date = is_string($dateInput) ? Carbon::parse($dateInput) : $dateInput;

        // act
        $result = $receipt->date($dateInput);

        // assert
        $this->assertSame($receipt, $result);
        $this->assertNotNull($receipt->date);
        $this->assertEquals($expectedFormat, $receipt->date->format('Y-m-d'));
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
        $receipt = UsageReceipt::make();
        $item = ReceiptItem::make([
            'user' => 'John Doe',
            'identifier' => 'ABC-123',
            'start_date' => '2024-01-15 10:00:00',
            'end_date' => '2024-01-15 12:00:00',
            'category' => 'Standard Parking',
            'price' => 5.50,
        ]);

        // act
        $result = $receipt->items([$item]);

        // assert
        $this->assertSame($receipt, $result);
        $this->assertCount(1, $receipt->items);
        $this->assertSame($item, $receipt->items->first());
    }

    #[Test]
    public function add_items(): void
    {
        // arrange
        $receipt = UsageReceipt::make();
        $item1 = ReceiptItem::make([
            'user' => 'John Doe',
            'identifier' => 'ABC-123',
            'start_date' => '2024-01-15 10:00:00',
            'end_date' => '2024-01-15 12:00:00',
            'category' => 'Standard Parking',
            'price' => 5.50,
        ]);

        $item2 = ReceiptItem::make([
            'user' => 'Jane Doe',
            'identifier' => 'XYZ-789',
            'start_date' => '2024-01-15 14:00:00',
            'end_date' => '2024-01-15 16:00:00',
            'category' => 'Premium Parking',
            'price' => 10.00,
        ]);

        // act
        $result = $receipt->items([$item1, $item2]);

        // assert
        $this->assertSame($receipt, $result);
        $this->assertCount(2, $receipt->items);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    #[Test]
    #[DataProvider('items_total_data_provider')]
    public function items_total(array $items, float $expected): void
    {
        // arrange
        $receipt = UsageReceipt::make();
        $receiptItems = [];
        foreach ($items as $itemData) {
            $receiptItems[] = ReceiptItem::make($itemData);
        }
        $receipt->items($receiptItems);

        // act
        $total = $receipt->getItemsTotal();

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
                [[
                    'user' => 'John Doe',
                    'identifier' => 'ABC-123',
                    'start_date' => '2024-01-15 10:00:00',
                    'end_date' => '2024-01-15 12:00:00',
                    'category' => 'Standard Parking',
                    'price' => 5.50,
                ]],
                5.50,
            ],
            'multiple items' => [
                [
                    [
                        'user' => 'John Doe',
                        'identifier' => 'ABC-123',
                        'start_date' => '2024-01-15 10:00:00',
                        'end_date' => '2024-01-15 12:00:00',
                        'category' => 'Standard Parking',
                        'price' => 5.50,
                    ],
                    [
                        'user' => 'Jane Doe',
                        'identifier' => 'XYZ-789',
                        'start_date' => '2024-01-15 14:00:00',
                        'end_date' => '2024-01-15 16:00:00',
                        'category' => 'Premium Parking',
                        'price' => 10.00,
                    ],
                ],
                15.50,
            ],
        ];
    }

    #[Test]
    #[DataProvider('total_data_provider')]
    public function total(?float $forcedTotal, float $expected): void
    {
        // arrange
        $receipt = UsageReceipt::make();
        $item = ReceiptItem::make([
            'user' => 'John Doe',
            'identifier' => 'ABC-123',
            'start_date' => '2024-01-15 10:00:00',
            'end_date' => '2024-01-15 12:00:00',
            'category' => 'Standard Parking',
            'price' => 5.50,
        ]);
        $receipt->items([$item]);

        if ($forcedTotal !== null) {
            $receipt->forcedTotal($forcedTotal);
        }

        // act
        $total = $receipt->getTotal();

        // assert
        $this->assertEquals($expected, $total);
    }

    /**
     * @return array<string, array{0: float|null, 1: float}>
     */
    public static function total_data_provider(): array
    {
        return [
            'no forced total' => [null, 5.50],
            'with forced total' => [25.50, 25.50],
        ];
    }

    #[Test]
    public function forced_total(): void
    {
        // arrange
        $receipt = UsageReceipt::make();

        // act
        $result = $receipt->forcedTotal(100.50);

        // assert
        $this->assertSame($receipt, $result);
        $this->assertEquals(100.50, $receipt->forcedTotal);
        $this->assertEquals(100.50, $receipt->getTotal());
    }

    #[Test]
    public function is_rendered_returns_false(): void
    {
        // arrange
        $receipt = UsageReceipt::make();

        // act
        $isRendered = $receipt->isRendered();

        // assert
        $this->assertFalse($isRendered);
    }

    #[Test]
    public function clear_pdf(): void
    {
        // arrange
        $receipt = UsageReceipt::make();
        /** @var \Barryvdh\DomPDF\PDF&\Mockery\MockInterface $mockPdf */
        $mockPdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $receipt->pdf = $mockPdf; // mock pdf object

        // act
        $result = $receipt->clearPdf();

        // assert
        $this->assertSame($receipt, $result);
        $this->assertNull($receipt->pdf);
    }

    #[Test]
    public function seller_initialized_from_config(): void
    {
        // arrange
        Config::set('invoices.seller.name', 'Test Seller');
        Config::set('invoices.seller.address', 'Test Address');

        // act
        $receipt = UsageReceipt::make();

        // assert
        $this->assertInstanceOf(Seller::class, $receipt->seller);
        $this->assertEquals('Test Seller', $receipt->seller->getName());
        $this->assertEquals('Test Address', $receipt->seller->getAddress());
    }

    #[Test]
    public function formatted_total(): void
    {
        // arrange
        Config::set('invoices.currency_symbol', '€');
        Config::set('invoices.decimal_separator', ',');
        Config::set('invoices.thousands_separator', '.');

        $receipt = UsageReceipt::make();
        $item = ReceiptItem::make([
            'user' => 'John Doe',
            'identifier' => 'ABC-123',
            'start_date' => '2024-01-15 10:00:00',
            'end_date' => '2024-01-15 12:00:00',
            'category' => 'Standard Parking',
            'price' => 10.50,
        ]);
        $receipt->items([$item]);

        // act
        $formatted = $receipt->getFormattedTotal();

        // assert
        $this->assertEquals('€ 10,50', $formatted);
    }

    #[Test]
    public function document_id_setter(): void
    {
        // arrange
        $receipt = UsageReceipt::make();

        // act
        $result = $receipt->documentId('DOC-12345');

        // assert
        $this->assertSame($receipt, $result);
        $this->assertEquals('DOC-12345', $receipt->documentId);
    }

    #[Test]
    public function user_id_setter(): void
    {
        // arrange
        $receipt = UsageReceipt::make();

        // act
        $result = $receipt->userId('USER-67890');

        // assert
        $this->assertSame($receipt, $result);
        $this->assertEquals('USER-67890', $receipt->userId);
    }

    #[Test]
    public function template_setter(): void
    {
        // arrange
        $receipt = UsageReceipt::make();

        // act
        $result = $receipt->template('custom-template');

        // assert
        $this->assertSame($receipt, $result);
        $this->assertEquals('custom-template', $receipt->template);
    }

    #[Test]
    public function note_setter(): void
    {
        // arrange
        $receipt = UsageReceipt::make();

        // act
        $result = $receipt->note('Test note content');

        // assert
        $this->assertSame($receipt, $result);
        $this->assertEquals('Test note content', $receipt->note);
    }

    #[Test]
    public function set_language_validates_language(): void
    {
        // arrange
        $receipt = UsageReceipt::make();

        // assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Language 'invalid' is not supported");

        // act
        $receipt->language('invalid');
    }

    #[Test]
    public function set_language_with_valid_language(): void
    {
        // arrange
        $receipt = UsageReceipt::make();

        // act
        $result = $receipt->language('en');

        // assert
        $this->assertSame($receipt, $result);
        $this->assertEquals('en', $receipt->language);
    }

    #[Test]
    public function get_available_languages(): void
    {
        // arrange
        $receipt = UsageReceipt::make();

        // act
        $languages = $receipt->getAvailableLanguages();

        // assert
        $this->assertIsArray($languages); // @phpstan-ignore-line method.alreadyNarrowedType

        // should at least contain 'en' and 'nl' based on the project structure
        $this->assertContains('en', $languages);
        $this->assertContains('nl', $languages);
    }

    #[Test]
    #[DataProvider('formatted_date_data_provider')]
    public function formatted_date(string $dateFormat, string $expected): void
    {
        // arrange
        $receipt = UsageReceipt::make();
        $receipt->date('2024-01-15');
        $receipt->dateFormat = $dateFormat;

        // act
        $formatted = $receipt->getFormattedDate();

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
    #[DataProvider('set_logo_data_provider')]
    public function set_logo(?string $logoPath, ?string $initialLogo, ?string $expected): void
    {
        // arrange
        $receipt = UsageReceipt::make();
        if ($initialLogo !== null) {
            $receipt->logo = $initialLogo;
        }

        // act
        $result = $receipt->logo($logoPath);

        // assert
        $this->assertSame($receipt, $result);
        $this->assertEquals($expected, $receipt->logo);
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
        $receipt = UsageReceipt::make();
        $receipt->logo = $logoPath;

        // act
        $dataUri = $receipt->getLogoDataUri();

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
