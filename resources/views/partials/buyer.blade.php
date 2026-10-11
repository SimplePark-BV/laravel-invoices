{{-- the buyer: name, address and contact details --}}
@php($buyer = $document->getBuyer())

<div class="document__buyer">
    @if($buyer->getName() !== null)
        <div class="document__buyer-name document__text">{{ $buyer->getName() }}</div>
    @endif
    @if($buyer->hasAddress())
        <div class="document__buyer-detail document__text">@include('invoices::partials.address', ['party' => $buyer])</div>
    @endif
    @if($buyer->getEmail() !== null || $buyer->getPhone() !== null)
        <div class="document__buyer-detail document__text">
            {!! collect([$buyer->getEmail(), $buyer->getPhone()])->filter(fn (?string $line): bool => $line !== null && $line !== '')->map(fn (string $line): string => e($line))->implode('<br>') !!}
        </div>
    @endif
</div>
