Pesan baru dari Contact Us - {!! config('event.name') !!}

Nama    : {!! $senderName !!}
Email   : {!! $senderEmail !!}
Dikirim : {!! $submittedAt->translatedFormat('l, j F Y, H.i T') !!}

Pesan:
{!! $messageBody !!}

Balas email ini untuk menjawab langsung ke {!! $senderEmail !!}.
{{-- Plain-text version: values are not HTML-escaped because this part is never rendered as HTML. --}}
