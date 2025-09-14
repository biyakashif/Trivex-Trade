<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $subject }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .header { background-color: #1a73e8; color: #fff; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .footer { background-color: #1a73e8; color: #fff; padding: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        Trivex Trade
    </div>
    <div class="content">
        @foreach ($lines as $line)
            <p>{{ $line }}</p>
        @endforeach
    </div>
    <div class="footer">
        © {{ date('Y') }} Trivex Trade. All rights reserved.
    </div>
</body>
</html>