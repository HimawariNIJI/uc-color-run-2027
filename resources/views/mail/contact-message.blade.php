<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pesan baru dari Contact Us</title>
</head>
<body style="margin: 0; padding: 24px 12px; background-color: #f4f1e9; font-family: Arial, Helvetica, sans-serif; color: #22114f;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 0 auto; background-color: #fffdf8; border: 1px solid #e6e0d2; border-radius: 16px;">
        <tr>
            <td style="padding: 20px 24px; background-color: #3d12c1; border-radius: 16px 16px 0 0; color: #ffe286; font-size: 18px; font-weight: bold;">
                Pesan baru dari Contact Us
                <div style="margin-top: 4px; color: #f4f1e9; font-size: 13px; font-weight: normal;">{{ config('event.name') }}</div>
            </td>
        </tr>
        <tr>
            <td style="padding: 24px;">
                <table role="presentation" cellpadding="0" cellspacing="0" style="font-size: 14px; line-height: 1.6;">
                    <tr>
                        <td style="padding: 2px 16px 2px 0; color: #5f5878; vertical-align: top;">Nama</td>
                        <td style="padding: 2px 0; font-weight: bold;">{{ $senderName }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 2px 16px 2px 0; color: #5f5878; vertical-align: top;">Email</td>
                        <td style="padding: 2px 0;"><a href="mailto:{{ $senderEmail }}" style="color: #3d12c1;">{{ $senderEmail }}</a></td>
                    </tr>
                    <tr>
                        <td style="padding: 2px 16px 2px 0; color: #5f5878; vertical-align: top;">Dikirim</td>
                        <td style="padding: 2px 0;">{{ $submittedAt->translatedFormat('l, j F Y, H.i T') }}</td>
                    </tr>
                </table>

                <p style="margin: 20px 0 8px; color: #5f5878; font-size: 14px;">Pesan</p>
                <div style="padding: 16px; background-color: #f4f1e9; border-radius: 12px; font-size: 14px; line-height: 1.6;">{!! nl2br(e($messageBody)) !!}</div>

                <p style="margin: 20px 0 0; color: #5f5878; font-size: 13px;">Tekan <strong>Balas</strong> untuk menjawab langsung ke {{ $senderEmail }}.</p>
            </td>
        </tr>
    </table>
</body>
</html>
