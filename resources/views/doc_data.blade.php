<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Nội dung File Word</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .content {
            font-family: Arial, sans-serif;
            font-size: 22px;
            color: #000;
            padding: 20px;
        }
    </style>
</head>
<body>
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="content">
        {!! $content !!}
    </div>
</body>
</html>