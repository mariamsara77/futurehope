<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>নতুন যোগাযোগ বার্তা</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f5;
        }
        .container {
            background: #ffffff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .header {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header h1 {
            margin: 0;
            color: #4f46e5;
            font-size: 22px;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        .badge-urgent {
            background: #fee2e2;
            color: #dc2626;
        }
        .badge-normal {
            background: #e0e7ff;
            color: #4f46e5;
        }
        .info-row {
            margin-bottom: 12px;
        }
        .label {
            font-weight: 600;
            color: #52525b;
            display: inline-block;
            width: 110px;
        }
        .message-box {
            background: #f4f4f5;
            border-left: 4px solid #4f46e5;
            padding: 16px 20px;
            border-radius: 0 8px 8px 0;
            margin-top: 20px;
            white-space: pre-wrap;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e4e4e7;
            font-size: 13px;
            color: #71717a;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                নতুন যোগাযোগ বার্তা
                @if($priority === 'urgent')
                    <span class="badge badge-urgent">URGENT</span>
                @else
                    <span class="badge badge-normal">Normal</span>
                @endif
            </h1>
        </div>

        <div class="info-row">
            <span class="label">নাম:</span>
            <strong>{{ $name }}</strong>
        </div>

        <div class="info-row">
            <span class="label">ইমেইল:</span>
            <a href="mailto:{{ $email }}">{{ $email }}</a>
        </div>

        @if(!empty($phone))
        <div class="info-row">
            <span class="label">ফোন:</span>
            <a href="tel:{{ $phone }}">{{ $phone }}</a>
        </div>
        @endif

        @if(!empty($subject))
        <div class="info-row">
            <span class="label">বিষয়:</span>
            {{ $subject }}
        </div>
        @endif

        @if(!empty($category))
        <div class="info-row">
            <span class="label">ক্যাটাগরি:</span>
            {{ $category }}
        </div>
        @endif

        <div class="message-box">
            <strong>মেসেজ:</strong><br><br>
            {{ $messageContent }}
        </div>

        <div class="footer">
            এই মেসেজটি {{ config('app.name') }} ওয়েবসাইটের Contact Form থেকে পাঠানো হয়েছে।
        </div>
    </div>
</body>
</html>