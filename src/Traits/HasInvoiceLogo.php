<?php

namespace SimpleParkBv\Invoices\Traits;

/**
 * Trait HasInvoiceLogo
 *
 * @var string|null $logo
 */
trait HasInvoiceLogo
{
    public ?string $logo = null;

    public function initializeHasInvoiceLogo(): void
    {
        $this->logo = config('invoices.logo');
    }

    /**
     * Set the logo path for this invoice.
     *
     * @return $this
     */
    public function setLogo(?string $logoPath): self
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
        if (! $this->logo || ! file_exists($this->logo)) {
            return null;
        }

        $imageData = file_get_contents($this->logo);

        if ($imageData === false) {
            return null;
        }

        $imageInfo = getimagesize($this->logo);

        if ($imageInfo === false) {
            return null;
        }

        $mimeType = $imageInfo['mime'];
        $base64 = base64_encode($imageData);

        return sprintf('data:%s;base64,%s', $mimeType, $base64);
    }
}
