{{ $heroTitle ?? 'Notification' }}
=================================

@if(!empty($heroText))
{{ $heroText }}

@endif
@if(!empty($otpCode))
OTP Code:
{{ $otpCode }}

{{ $otpCopyHint ?? 'Tip: press and hold the code to copy it.' }}

@endif
@if(!empty($buttonText) && !empty($buttonUrl))
{{ $buttonText }}: {{ $buttonUrl }}

@endif
@if(!empty($cards))
Details:
@foreach($cards as $card)
- {{ $card['title'] ?? 'Info' }}: {{ $card['text'] ?? '' }}
@endforeach

@endif
@if(!empty($navItems))
Links:
@foreach($navItems as $item)
- {{ $item['label'] ?? 'Link' }}: {{ $item['url'] ?? '#' }}
@endforeach

@endif
{{ $footerText ?? 'Agilify' }}
