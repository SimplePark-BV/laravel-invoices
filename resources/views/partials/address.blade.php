{{-- the address lines of a party: street, postal code and city, and country --}}
{!! collect([$party->getAddress(), trim($party->getPostalCode().' '.$party->getCity()), $party->getCountry()])->filter(fn (?string $line): bool => $line !== null && $line !== '')->map(fn (string $line): string => e($line))->implode('<br>') !!}
