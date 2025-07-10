<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh Sách Biến - {{ $docName }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        @if (isset($success))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <h5>Thông báo</h5>
                <p>{{ $success }}</p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header">Danh Sách Biến Từ File: {{ $docName }}</div>
            <div class="card-body">
                @if (!empty($variables))
                    <ul class="list-group">
                        @foreach ($variables as $variable)
                            <li class="list-group-item">{{ $variable }}</li>
                        @endforeach
                    </ul>
                @else
                    <p>Không tìm thấy biến nào trong file.</p>
                @endif
                <a href="{{ route('file.index') }}" class="btn btn-primary mt-3">Quay lại</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>