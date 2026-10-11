<?php

namespace Tests\Unit\Models;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Exceptions\ExpectedTotalMismatchException;
use SimpleParkBv\Invoices\Exceptions\InvalidUsageReceiptException;
use SimpleParkBv\Invoices\Models\UsageReceipt;
use SimpleParkBv\Invoices\Models\UsageReceiptItem;
use SimpleParkBv\Invoices\Models\UsageReceiptLine;
use Tests\TestCase;
use Tests\Traits\CreatesTestReceipts;

final class UsageReceiptTest extends TestCase
{
    use CreatesTestReceipts;

    #[Test]
    public function a_new_receipt_has_the_defaults_from_the_config(): void
    {
        // act
        $receipt = UsageReceipt::make();

        // assert
        $this->assertSame('nl', $receipt->getLanguage());
        $this->assertSame('d-m-Y H:i', $receipt->getDateTimeFormat());
        $this->assertSame('invoices::usage-receipt.index', $receipt->getTemplate());
        $this->assertSame('Test Seller B.V.', $receipt->getSeller()->getName());
        $this->assertTrue($receipt->getItems()->isEmpty());
        $this->assertTrue($receipt->getLines()->isEmpty());
    }

    #[Test]
    public function make_and_fluent_setters_build_the_same_receipt(): void
    {
        // act
        $fromArray = UsageReceipt::make([
            'buyer' => ['name' => 'Jan'],
            'date' => '2026-10-11',
            'document_id' => 'PS-1',
            'user_id' => 'U-1',
            'title' => 'Parking receipt',
            'note' => 'Thanks',
            'items' => [$this->receiptItemData()],
            'lines' => [['label' => 'Fee', 'amount' => 0.35, 'tax_percentage' => 21]],
        ]);

        $fluent = UsageReceipt::make()
            ->buyer(['name' => 'Jan'])
            ->date('2026-10-11')
            ->documentId('PS-1')
            ->userId('U-1')
            ->title('Parking receipt')
            ->note('Thanks')
            ->addItem(UsageReceiptItem::make($this->receiptItemData()))
            ->addLine(UsageReceiptLine::make()->label('Fee')->amount(0.35)->taxPercentage(21));

        // assert
        $this->assertEquals($fluent, $fromArray);
    }

    #[Test]
    public function make_rejects_unknown_keys(): void
    {
        // assert
        $this->expectException(InvalidUsageReceiptException::class);
        $this->expectExceptionMessage("Unknown key 'notes' for UsageReceipt");

        // act
        UsageReceipt::make(['notes' => 'Thanks']);
    }

    #[Test]
    public function items_and_lines_accept_objects_and_arrays(): void
    {
        // act
        $receipt = UsageReceipt::make()
            ->items([$this->createReceiptItem(), $this->receiptItemData(['identifier' => 'XY-1'])])
            ->lines([$this->createReceiptLine(), ['label' => 'Discount', 'amount' => -1]]);

        // assert
        $this->assertSame(['AB-123-C', 'XY-1'], $receipt->getItems()->map->getIdentifier()->all());
        $this->assertSame(['Service fee', 'Discount'], $receipt->getLines()->map->getLabel()->all());
    }

    #[Test]
    public function the_title_falls_back_to_the_translation_in_the_document_language(): void
    {
        // act & assert
        $this->assertSame('Gebruiksbevestiging', UsageReceipt::make()->getTitle());
        $this->assertSame('Usage Confirmation', UsageReceipt::make()->language('en')->getTitle());
        $this->assertSame('Parking receipt', UsageReceipt::make()->title('Parking receipt')->getTitle());
        $this->assertSame('Gebruiksbevestiging', UsageReceipt::make()->title('  ')->getTitle());
    }

    #[Test]
    public function without_lines_the_total_is_the_sum_of_the_session_prices(): void
    {
        // arrange: 0.1 + 0.2 used to be 0.30000000000000004
        $receipt = $this->createReceipt(['items' => [$this->receiptItemData(['price' => 0.10]), $this->receiptItemData(['price' => 0.20])]]);

        // act & assert
        $this->assertSame(30, $receipt->getTotals()->getTotalCents());
        $this->assertSame(0.3, $receipt->getTotal());
        $this->assertSame([], $receipt->getTotals()->getTaxGroups());
    }

    #[Test]
    public function with_lines_the_total_is_the_sum_of_the_lines_and_includes_their_tax(): void
    {
        // arrange
        $receipt = $this->createFullReceipt();

        // act
        $totals = $receipt->getTotals();

        // assert: 12.50 + 0.35 - 0.10, of which 21% tax over 0.25
        $this->assertSame(1275, $totals->getTotalCents());
        $this->assertCount(1, $totals->getTaxGroups());
        $this->assertSame(4, $totals->getTaxCents());
        $this->assertSame(0.04, $receipt->getTaxAmount());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    #[Test]
    #[DataProvider('filenames')]
    public function the_filename_uses_the_document_id_or_else_the_date(array $data, string $expected): void
    {
        // act & assert
        $this->assertSame($expected, $this->createReceipt($data)->getFilename());
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function filenames(): array
    {
        return [
            'document id' => [['document_id' => 'PS-123', 'date' => '2026-10-11 14:30'], 'gebruiksbevestiging-PS-123.pdf'],
            'date' => [['date' => '2026-10-11 14:30:15'], 'gebruiksbevestiging-2026-10-11-14-30-15.pdf'],
            'date in the display timezone' => [['date' => CarbonImmutable::parse('2026-10-11 12:30', 'UTC')], 'gebruiksbevestiging-2026-10-11-14-30-00.pdf'],
            'nothing' => [[], 'gebruiksbevestiging.pdf'],
            'english' => [['language' => 'en', 'document_id' => 'PS-123'], 'usage-confirmation-PS-123.pdf'],
            'unsafe characters' => [['document_id' => 'PS/1'], 'gebruiksbevestiging-PS-1.pdf'],
        ];
    }

    #[Test]
    public function the_filename_without_a_date_does_not_change_over_time(): void
    {
        // arrange
        $receipt = $this->createReceipt();
        $filename = $receipt->getFilename();

        // act
        $this->travel(5)->seconds();

        // assert
        $this->assertSame($filename, $receipt->getFilename());
    }

    #[Test]
    public function a_buyer_without_a_name_is_valid_on_a_receipt(): void
    {
        // arrange
        $receipt = $this->createReceipt(['buyer' => ['name' => null, 'email' => 'anonymous@example.test']]);

        // act
        $receipt->validate();

        // assert
        $this->assertNull($receipt->getBuyer()?->getName());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    #[Test]
    #[DataProvider('invalidReceipts')]
    public function validate_rejects_invalid_receipts(array $data, string $message): void
    {
        // arrange
        $receipt = UsageReceipt::make($data);

        // assert
        $this->expectException(InvalidUsageReceiptException::class);
        $this->expectExceptionMessage($message);

        // act
        $receipt->validate();
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidReceipts(): array
    {
        $item = [
            'user' => 'Jan',
            'identifier' => 'AB-1',
            'start_date' => '2026-10-02 10:00',
            'end_date' => '2026-10-02 11:00',
            'category' => 'Zone 1',
            'price' => 1,
        ];

        return [
            'no buyer' => [['items' => [$item]], 'A usage receipt must have a buyer'],
            'seller without a name' => [['buyer' => ['name' => 'Jan'], 'seller' => ['email' => 'info@seller.test'], 'items' => [$item]], 'The seller must have a name'],
            'no items' => [['buyer' => ['name' => 'Jan']], 'A usage receipt must have at least one item'],
            'invalid item' => [['buyer' => ['name' => 'Jan'], 'items' => [$item, [...$item, 'end_date' => '2026-10-02 09:00']]], 'Item at index 1 must not end before it starts'],
            'invalid line' => [['buyer' => ['name' => 'Jan'], 'items' => [$item], 'lines' => [['amount' => 1]]], 'Line at index 0 must have a label'],
        ];
    }

    #[Test]
    public function an_expected_total_is_checked_against_the_lines(): void
    {
        // arrange
        $receipt = $this->createFullReceipt(['expected_total' => 12.74]);

        // assert
        $this->expectException(ExpectedTotalMismatchException::class);
        $this->expectExceptionMessage('The total of usage receipt PS-2026-000123 is 12.75, but 12.74 was expected');

        // act
        $receipt->validate();
    }

    #[Test]
    public function to_array_returns_every_field(): void
    {
        // arrange
        $receipt = $this->createFullReceipt();

        // act
        $array = $receipt->toArray();

        // assert
        $this->assertSame('PS-2026-000123', $array['document_id']);
        $this->assertSame('USR-000042', $array['user_id']);
        $this->assertNull($array['title']);
        $this->assertSame("Thank you for parking.\nSee you next time!", $array['note']);
        $this->assertSame('d-m-Y H:i', $array['date_time_format']);
        $this->assertSame('2026-10-11T14:30:00+02:00', $array['date']);
        $this->assertIsArray($array['items']);
        $this->assertCount(2, $array['items']);
        $this->assertSame(['label' => 'Service fee', 'amount' => 0.35, 'tax_percentage' => 21.0], data_get($array, 'lines.1'));
    }

    #[Test]
    public function the_receipt_has_no_invoice_only_methods(): void
    {
        // arrange
        $class = new \ReflectionClass(UsageReceipt::class);

        // act & assert
        $this->assertFalse($class->hasMethod('getDueDate'));
        $this->assertFalse($class->hasMethod('isIssued'));
        $this->assertFalse($class->hasMethod('paymentTermsDays'));
    }

    #[Test]
    public function the_date_time_format_can_be_changed(): void
    {
        // arrange
        Config::set('invoices.datetime_format', 'Y-m-d H:i');

        // act & assert
        $this->assertSame('Y-m-d H:i', UsageReceipt::make()->getDateTimeFormat());
        $this->assertSame('H:i', UsageReceipt::make()->dateTimeFormat('H:i')->getDateTimeFormat());
    }
}
