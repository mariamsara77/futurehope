<!doctype html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>নতুন যোগাযোগ বার্তা</title>
</head>
<body style="margin:0;background:#f4f4f5;font-family:Arial,sans-serif;color:#18181b;">
    <div style="max-width:680px;margin:32px auto;padding:24px;">
        <div style="background:#ffffff;border:1px solid #e4e4e7;border-radius:16px;padding:28px;">
            <h1 style="margin:0 0 20px;font-size:24px;">নতুন যোগাযোগ বার্তা</h1>
            <p><strong>নাম:</strong> {{ $contactMessage->name }}</p>
            <p><strong>ইমেইল:</strong> {{ $contactMessage->email }}</p>
            @if($contactMessage->phone)
                <p><strong>ফোন:</strong> {{ $contactMessage->phone }}</p>
            @endif
            <p><strong>বিষয়:</strong> {{ $contactMessage->subject }}</p>
            <div style="margin-top:20px;padding:18px;background:#f4f4f5;border-radius:12px;white-space:pre-wrap;line-height:1.7;">{{ $contactMessage->message }}</div>
        </div>
    </div>
</body>
</html>