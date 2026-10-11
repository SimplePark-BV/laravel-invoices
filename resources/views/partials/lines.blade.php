{{-- lines of text, escaped and joined by line breaks, leaving out the empty ones --}}
{!! collect($lines)->filter(fn (?string $line): bool => $line !== null && $line !== '')->map(fn (string $line): string => e($line))->implode('<br>') !!}
