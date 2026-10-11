<?php

namespace Tests\Unit\Models;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Exceptions\InvalidUsageReceiptException;
use SimpleParkBv\Invoices\Models\UsageReceiptItem;
use Tests\TestCase;
use Tests\Traits\CreatesTestReceipts;

final class UsageReceiptItemTest extends TestCase
{
    use CreatesTestReceipts;

    #[Test]
    public function make_fills_the_item_from_an_array(): void
    {
        // act
        $item = $this->createReceiptItem();

        // assert
        $this->assertSame('John Doe', $item->getUser());
        $this->assertSame('AB-123-C', $item->getIdentifier());
        $this->assertSame('2026-10-02T10:00:00+02:00', $item->getStartDate()?->toIso8601String());
        $this->assertSame('2026-10-02T12:00:00+02:00', $item->getEndDate()?->toIso8601String());
        $this->assertSame('Zone 1001', $item->getCategory());
        $this->assertSame(5.50, $item->getPrice());
        $this->assertSame(550, $item->getPriceCents());
        $this->assertSame(550, $item->getTotalCents());
        $this->assertNull($item->getTaxPercentage());
    }

    #[Test]
    public function dates_are_stored_as_immutable_copies(): void
    {
        // arrange
        $start = \Illuminate\Support\Carbon::parse('2026-10-02 10:00');

        // act
        $item = $this->createReceiptItem(['start_date' => $start]);
        $start->addHours(5);

        // assert
        $this->assertSame('10:00', $item->getStartDate()?->format('H:i'));
        $this->assertInstanceOf(CarbonImmutable::class, $item->getStartDate());
    }

    #[Test]
    public function start_date_rejects_an_empty_string(): void
    {
        // assert
        $this->expectException(InvalidUsageReceiptException::class);
        $this->expectExceptionMessage('The start date must be a date, an empty string given');

        // act
        $this->createReceiptItem(['start_date' => '']);
    }

    #[Test]
    public function price_rejects_more_than_two_decimals(): void
    {
        // assert
        $this->expectException(InvalidUsageReceiptException::class);

        // act
        $this->createReceiptItem(['price' => 0.333]);
    }

    #[Test]
    public function a_session_without_duration_is_valid(): void
    {
        // arrange
        $item = $this->createReceiptItem(['start_date' => '2026-10-02 10:00', 'end_date' => '2026-10-02 10:00']);

        // act
        $item->validate();

        // assert
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function a_free_session_is_valid(): void
    {
        // arrange
        $item = $this->createReceiptItem(['price' => 0]);

        // act
        $item->validate();

        // assert
        $this->addToAssertionCount(1);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    #[Test]
    #[DataProvider('invalidItems')]
    public function validate_rejects_invalid_items(array $data, string $message): void
    {
        // arrange
        $item = UsageReceiptItem::make($this->receiptItemData($data));

        // assert
        $this->expectException(InvalidUsageReceiptException::class);
        $this->expectExceptionMessage($message);

        // act
        $item->validate(1);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidItems(): array
    {
        return [
            'no user' => [['user' => null], 'Item at index 1 must have a user'],
            'blank user' => [['user' => '  '], 'Item at index 1 must have a user'],
            'no identifier' => [['identifier' => null], 'Item at index 1 must have an identifier'],
            'no category' => [['category' => ''], 'Item at index 1 must have a category'],
            'no start date' => [['start_date' => null], 'Item at index 1 must have a start date'],
            'no end date' => [['end_date' => null], 'Item at index 1 must have an end date'],
            'ends before it starts' => [['end_date' => '2026-10-02 09:59'], 'Item at index 1 must not end before it starts'],
            'no price' => [['price' => null], 'Item at index 1 must have a price'],
            'negative price' => [['price' => -1], 'Item at index 1 must not have a negative price'],
        ];
    }

    #[Test]
    public function to_array_returns_the_fields(): void
    {
        // act & assert
        $this->assertSame([
            'user' => 'John Doe',
            'identifier' => 'AB-123-C',
            'start_date' => '2026-10-02T10:00:00+02:00',
            'end_date' => '2026-10-02T12:00:00+02:00',
            'category' => 'Zone 1001',
            'price' => 5.5,
        ], $this->createReceiptItem()->toArray());
    }
}
