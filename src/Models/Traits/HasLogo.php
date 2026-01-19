<?php

namespace SimpleParkBv\Invoices\Models\Traits;

/**
 * Trait HasLogo
 *
 * @var string|null $logo
 */
trait HasLogo
{
    protected ?string $logo = null;

    public function initializeHasLogo(): void
    {
        $this->logo = config('invoices.logo');
    }

    /**
     * Get the logo path.
     */
    public function getLogo(): ?string
    {
        return $this->logo;
    }

    /**
     * Set the logo path.
     *
     * @return $this
     */
    public function logo(?string $logoPath): self
    {
        $this->logo = $logoPath;

        return $this;
    }

    /**
     * Get the logo as a data URI for PDF rendering.
     * Supports PNG, JPG, JPEG, GIF, and SVG formats.
     *
     * Note: SVG support in dompdf is limited and may render incorrectly.
     * PNG is recommended for best compatibility.
     */
    public function getLogoDataUri(): ?string
    {
        if (! $this->getLogo()) {
            return null;
        }

        // validate file path to prevent directory traversal
        $realPath = realpath($this->getLogo());
        if ($realPath === false || ! file_exists($realPath)) {
            return null;
        }

        // validate file type
        $allowedMimeTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/svg+xml'];
        $imageInfo = getimagesize($realPath);

        if ($imageInfo === false || ! in_array($imageInfo['mime'], $allowedMimeTypes, true)) {
            return null;
        }

        $imageData = file_get_contents($realPath);

        if ($imageData === false) {
            return null;
        }

        $mimeType = $imageInfo['mime'];
        $base64 = base64_encode($imageData);

        return sprintf('data:%s;base64,%s', $mimeType, $base64);
    }
}
