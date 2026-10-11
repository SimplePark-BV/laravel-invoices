<?php

namespace Tests\Feature\UsageReceipt;

use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Models\UsageReceipt;
use Tests\TestCase;
use Tests\Traits\CreatesTestReceipts;

final class OptionalSectionsTest extends TestCase
{
    use CreatesTestReceipts;

    protected function setUp(): void
    {
        parent::setUp();

        App::setLocale('en');
    }

    private function renderIds(UsageReceipt $receipt): string
    {
        /** @var view-string $view */
        $view = 'invoices::usage-receipt.ids';

        return view($view, ['usageReceipt' => $receipt])->render();
    }

    #[Test]
    public function ids_section_is_hidden_when_no_ids_are_given(): void
    {
        // arrange
        $receipt = $this->createValidReceipt();

        // act
        $html = $this->renderIds($receipt);

        // assert
        $this->assertStringNotContainsString('usage-receipt__ids', $html);
        $this->assertStringNotContainsString('Document ID', $html);
        $this->assertStringNotContainsString('User ID', $html);
    }

    #[Test]
    public function ids_section_shows_both_ids_when_given(): void
    {
        // arrange
        $receipt = $this->createValidReceipt();
        $receipt->documentId('DOC-001');
        $receipt->userId('USER-001');

        // act
        $html = $this->renderIds($receipt);

        // assert
        $this->assertStringContainsString('Document ID', $html);
        $this->assertStringContainsString('DOC-001', $html);
        $this->assertStringContainsString('User ID', $html);
        $this->assertStringContainsString('USER-001', $html);
    }

    #[Test]
    public function ids_section_only_shows_given_id(): void
    {
        // arrange
        $receipt = $this->createValidReceipt();
        $receipt->userId('USER-001');

        // act
        $html = $this->renderIds($receipt);

        // assert
        $this->assertStringNotContainsString('Document ID', $html);
        $this->assertStringContainsString('User ID', $html);
        $this->assertStringContainsString('USER-001', $html);
        $this->assertSame(3, substr_count($html, 'class="usage-receipt__id-column"'));
    }

    #[Test]
    public function ids_section_treats_empty_string_as_missing(): void
    {
        // arrange
        $receipt = $this->createValidReceipt();
        $receipt->documentId('');

        // act
        $html = $this->renderIds($receipt);

        // assert
        $this->assertStringNotContainsString('usage-receipt__ids', $html);
    }

    #[Test]
    public function date_is_hidden_when_no_date_is_given(): void
    {
        // arrange
        $receipt = $this->createValidReceipt();

        /** @var view-string $view */
        $view = 'invoices::usage-receipt.title';

        // act
        $html = view($view, ['usageReceipt' => $receipt])->render();

        // assert
        $this->assertStringNotContainsString('usage-receipt__date-label', $html);
    }

    #[Test]
    public function date_is_shown_when_given(): void
    {
        // arrange
        $receipt = $this->createValidReceipt();
        $receipt->date('2026-10-11');

        /** @var view-string $view */
        $view = 'invoices::usage-receipt.title';

        // act
        $html = view($view, ['usageReceipt' => $receipt])->render();

        // assert
        $this->assertStringContainsString('Date', $html);
        $this->assertStringContainsString('11-10-2026', $html);
    }
}
