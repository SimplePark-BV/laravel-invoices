<?php

namespace Tests\Unit\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Services\LogoResolver;
use Tests\TestCase;

final class LogoResolverTest extends TestCase
{
    private const FIXTURES = __DIR__.'/../../Fixtures';

    #[Test]
    public function no_logo_has_no_data_uri(): void
    {
        // assert
        Log::shouldReceive('warning')->never();

        // act & assert
        $this->assertNull(LogoResolver::dataUri(null));
        $this->assertNull(LogoResolver::dataUri(''));
    }

    #[Test]
    public function a_png_logo_becomes_a_data_uri(): void
    {
        // act
        $dataUri = LogoResolver::dataUri(self::FIXTURES.'/logo.png');

        // assert
        $this->assertSame('data:image/png;base64,'.base64_encode((string) file_get_contents(self::FIXTURES.'/logo.png')), $dataUri);
    }

    #[Test]
    public function an_svg_logo_becomes_a_data_uri(): void
    {
        // act
        $dataUri = LogoResolver::dataUri(self::FIXTURES.'/logo.svg');

        // assert
        $this->assertStringStartsWith('data:image/svg+xml;base64,', (string) $dataUri);
    }

    #[Test]
    public function a_relative_logo_is_read_from_the_base_path(): void
    {
        // arrange
        $this->app?->setBasePath((string) realpath(self::FIXTURES.'/..'));

        // act
        $dataUri = LogoResolver::dataUri('Fixtures/logo.png');

        // assert
        $this->assertStringStartsWith('data:image/png;base64,', (string) $dataUri);
    }

    #[Test]
    public function a_data_uri_is_used_as_it_is(): void
    {
        // arrange
        $dataUri = 'data:image/png;base64,'.base64_encode((string) file_get_contents(self::FIXTURES.'/logo.png'));

        // act & assert
        $this->assertSame($dataUri, LogoResolver::dataUri($dataUri));
    }

    #[Test]
    #[DataProvider('unusableLogos')]
    public function an_unusable_logo_is_left_out_with_a_warning(string $logo): void
    {
        // assert
        Log::shouldReceive('warning')->once();

        // act
        $dataUri = LogoResolver::dataUri($logo);

        // assert
        $this->assertNull($dataUri);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unusableLogos(): array
    {
        return [
            'missing file' => ['/non/existent/logo.png'],
            'directory' => [__DIR__],
            'unsupported type' => [__FILE__],
            'remote url' => ['https://example.test/logo.png'],
            'data uri that is no image' => ['data:text/html;base64,PHNjcmlwdD4='],
        ];
    }

    #[Test]
    public function a_file_that_is_no_image_is_left_out_with_a_warning(): void
    {
        // arrange
        $path = sys_get_temp_dir().'/laravel-invoices-tests/not-an-image.png';
        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'not an image');

        // assert
        Log::shouldReceive('warning')->once();

        // act
        $dataUri = LogoResolver::dataUri($path);

        // assert
        $this->assertNull($dataUri);
    }
}
