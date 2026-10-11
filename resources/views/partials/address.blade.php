{{-- the address lines of a party: street, postal code and city, and country --}}
@include('invoices::partials.lines', ['lines' => [$party->getAddress(), trim($party->getPostalCode().' '.$party->getCity()), $party->getCountry()]])
