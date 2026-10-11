{{-- the address lines of a party: street, postal code and city, and its country unless left out with withCountry --}}
@include('invoices::partials.lines', ['lines' => [
    $party->getAddress(),
    trim($party->getPostalCode().' '.$party->getCity()),
    ($withCountry ?? true) ? $format->country($party->getCountry()) : null,
]])
