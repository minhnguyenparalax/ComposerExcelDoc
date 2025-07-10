```blade
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý file Word và Excel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Sửa trong <style> (thêm CSS để giảm khoảng trắng) -->
    <style>
        .file-column {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px !important;
            min-width: 0;
        }
        .file-column .btn {
            white-space: nowrap;
        }
        .file-column .doc-name {
            word-break: break-all;
            overflow-wrap: break-word;
            min-width: 0;
            color: #000 !important;
        }
        .card-header {
            background-color: #f8f9fa;
            font-weight: 600;
            font-size: 16px;
            color: #333;
            border-bottom: 1px solid #ddd;
        }
        .card-body h6 {
            font-weight: 600;
            color: #0d6efd;
            margin-top: 12px;
            margin-bottom: 5px;
        }
        .table {
            font-size: 14px;
            margin-bottom: 0;
        }
        .table th {
            background-color: #eaeaea;
            text-align: center;
            font-size: 13px;
        }
        .table td {
            font-size: 13px;
            vertical-align: middle;
        }
        .table-bordered {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            overflow: hidden;
        }
        .popover-btn {
            padding: 2px 4px;
            font-size: 12px;
        }
        .popover-btn i {
            font-size: 10px;
        }
        .popover {
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            font-size: 12px;
        }
        .popover-header {
            background-color: #f0f4ff;
            color: #333;
            font-size: 12px;
            padding: 6px 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        .popover-body {
            padding: 8px 10px;
            color: #333;
            line-height: 1.4;
        }
        /* Thêm để giảm khoảng trắng */
        .row + .row {
            margin-top: 1rem;
        }
        .card.mb-3 {
            margin-bottom: 1rem !important;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <h5>Thông báo</h5>
                <p>{{ session('success') }}</p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h5>Lỗi</h5>
                <p>{{ session('error') }}</p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        
    <!-- Sửa section đầu tiên (form Thêm File Word và Đọc File Excel) -->
    <div class="row">
        <!-- Form thêm file Word -->
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">Thêm File Word</div>
                <div class="card-body">
                    <form action="{{ route('doc.addDoc') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="file_path" class="form-label">Nhập Đường Dẫn File Word (.doc hoặc .docx)</label>
                            <input type="text" class="form-control" id="file_path" name="file_path" placeholder="Đường dẫn có thể có hoặc không có dấu ngoặc kép" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Thêm File Word</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Form đọc file Excel -->
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">Đọc File Excel</div>
                <div class="card-body">
                    <form action="{{ route('excel.addExcel') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="excel_file_path" class="form-label">Nhập Đường Dẫn File Excel (.xlsx hoặc .xls)</label>
                            <input type="text" class="form-control" id="excel_file_path" name="file_path" placeholder="Đường dẫn có thể có hoặc không có dấu ngoặc kép">
                        </div>
                        <button type="submit" class="btn btn-primary">Thêm Excel</button>
                    </form>

                    @if ($excelFiles->isNotEmpty())
                        <h5 class="mt-4">Danh Sách Excel</h5>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>File</th>
                                    <th>Sheets</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($excelFiles as $file)
                                    <tr>
                                        <td class="file-column">{{ $file->name }}</td>
                                        <td>
                                            @foreach ($file->sheets as $sheet)
                                                <div class="file-column">
                                                    <a href="#" class="btn btn-sm btn-info view-sheet" data-file-id="{{ $file->id }}" data-sheet-id="{{ $sheet->id }}" data-sheet-name="{{ $sheet->name }}" data-bs-toggle="modal" data-bs-target="#excelModal">
                                                        Xem
                                                    </a>
                                                    <a href="{{ route('excel.selectSheet', ['fileId' => $file->id, 'sheetId' => $sheet->id]) }}" class="btn btn-sm btn-success">
                                                        Chọn
                                                    </a>
                                                    <span>{{ $sheet->name }}</span>
                                                </div>
                                            @endforeach
                                        </td>
                                        <td>
                                            <form action="{{ route('excel.removeExcel', ['id' => $file->id]) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Xóa Excel</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p>Không có file Excel nào.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Sửa section Danh sách file Word (popover và nút Xem nội dung) -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">Danh sách file Word</div>
                <div class="card-body">
                    @if ($docFiles->isNotEmpty())
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>File Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($docFiles as $doc)
                                    <tr>
                                        <td>{{ $doc->name }}</td>
                                        <td>
                                            @if ($doc->is_selected)
                                                <button type="button" class="btn btn-xs btn-link text-primary popover-btn" data-bs-toggle="popover" data-bs-content="@foreach ($doc->variables as $variable) {{ $variable->var_name }}<br> @endforeach" data-bs-html="true" title="Biến của {{ $doc->name }}">
                                                    <i class="fas fa-list-ul fa-xs"></i>
                                                </button>
                                            @else
                                                <a href="#" class="btn btn-sm btn-primary select-doc" data-doc-id="{{ $doc->id }}">Chọn Doc</a>
                                            @endif
                                            <a href="#" class="btn btn-sm btn-secondary view-doc" data-doc-id="{{ $doc->id }}" data-doc-name="{{ $doc->name }}" data-bs-toggle="modal" data-bs-target="#docModal">Xem nội dung</a>
                                            <form action="{{ route('doc.removeDoc') }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa file này?');">
                                                @csrf
                                                <input type="hidden" name="doc_id" value="{{ $doc->id }}">
                                                <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p>Chưa có file Word nào.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Giữ nguyên section Danh sách Sheet đã được tạo bảng -->
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">Danh sách Sheet đã được tạo bảng</div>
                <div class="card-body">
                    @if ($excelFilesWithCreatedSheets->isNotEmpty())
                        @foreach ($excelFilesWithCreatedSheets as $excelFile)
                            <h6>File: {{ $excelFile->name }}</h6>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Sheet Name</th>
                                        <th>Table Name</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($excelFile->sheets as $sheet)
                                        <tr>
                                            <td>{{ $sheet->name }}</td>
                                            <td>{{ $sheet->table_name }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endforeach
                    @else
                        <p>Chưa có sheet nào được tạo bảng.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>


        <!-- Sửa script (thêm AJAX cho selectDoc và initPopovers) -->
    <script>
        $(document).ready(function() {
            // Hàm khởi tạo popover
            function initPopovers() {
                var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
                popoverTriggerList.forEach(function (popoverTriggerEl) {
                    new bootstrap.Popover(popoverTriggerEl, {
                        trigger: 'click',
                        placement: 'top',
                        customClass: 'custom-popover'
                    });
                });
            }

            // Gọi khởi tạo popover khi tải trang
            initPopovers();

            // Xử lý nút Xem cho Doc
            $('.view-doc').click(function(e) {
                e.preventDefault();
                var docId = $(this).data('doc-id');
                var docName = $(this).data('doc-name');
                $('#docModalLabel').text('Nội dung File Word: ' + docName);

                $.ajax({
                    url: '{{ route("doc.readDoc", ":docId") }}'.replace(':docId', docId),
                    type: 'GET',
                    success: function(response) {
                        $('#docContent').html(response);
                    },
                    error: function(xhr) {
                        $('#docContent').html('<p class="text-danger">Lỗi: ' + (xhr.responseJSON?.error || 'Không thể tải nội dung Word.') + '</p>');
                    }
                });
            });

            // Xử lý nút Chọn Doc
            $('.select-doc').click(function(e) {
                e.preventDefault();
                var docId = $(this).data('doc-id');
                var $button = $(this);

                $.ajax({
                    url: '{{ route("doc.selectDoc", ":docId") }}'.replace(':docId', docId),
                    type: 'GET',
                    success: function(response) {
                        // Thay nút Chọn Doc bằng nút Biến
                        $button.replaceWith(
                            '<button type="button" class="btn btn-xs btn-link text-primary popover-btn" data-bs-toggle="popover" data-bs-content="' + 
                            response.variables.map(v => v.var_name).join('<br>') + 
                            '" data-bs-html="true" title="Biến của ' + response.doc_name + '">' +
                            '<i class="fas fa-list-ul fa-xs"></i>' +
                            '</button>'
                        );
                        // Khởi tạo lại popover cho nút mới
                        initPopovers();
                        // Hiển thị thông báo thành công
                        var alert = '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                                    '<h5>Thông báo</h5><p>Đã chọn tài liệu "' + response.doc_name + '" và trích xuất biến thành công.</p>' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                        $('.container.mt-4').prepend(alert);
                    },
                    error: function(xhr) {
                        var alert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                                    '<h5>Lỗi</h5><p>' + (xhr.responseJSON?.error || 'Không thể chọn tài liệu.') + '</p>' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                        $('.container.mt-4').prepend(alert);
                    }
                });
            });

            // Xử lý nút Xem cho Excel
            $('.view-sheet').click(function(e) {
                e.preventDefault();
                var fileId = $(this).data('file-id');
                var sheetId = $(this).data('sheet-id');
                var sheetName = $(this).data('sheet-name');
                $('#excelModalLabel').text('Nội dung Sheet Excel: ' + sheetName);

                $.ajax({
                    url: '{{ route("excel.readSheet", [":fileId", ":sheetId"]) }}'.replace(':fileId', fileId).replace(':sheetId', sheetId),
                    type: 'GET',
                    success: function(response) {
                        $('#excelContent').html(response);
                    },
                    error: function(xhr) {
                        $('#excelContent').html('<p class="text-danger">Lỗi: ' + (xhr.responseJSON?.error || 'Không thể tải nội dung Excel.') + '</p>');
                    }
                });
            });
        });
    </script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Sửa script (cập nhật popover) -->
    <script>
        $(document).ready(function() {
            // Xử lý nút Xem cho Doc
            $('.view-doc').click(function(e) {
                e.preventDefault();
                var docId = $(this).data('doc-id');
                var docName = $(this).data('doc-name');
                $('#docModalLabel').text('Nội dung File Word: ' + docName);

                $.ajax({
                    url: '{{ route("doc.readDoc", ":docId") }}'.replace(':docId', docId),
                    type: 'GET',
                    success: function(response) {
                        $('#docContent').html(response);
                    },
                    error: function(xhr) {
                        $('#docContent').html('<p class="text-danger">Lỗi: ' + (xhr.responseJSON?.error || 'Không thể tải nội dung Word.') + '</p>');
                    }
                });
            });

            // Xử lý nút Xem cho Excel
            $('.view-sheet').click(function(e) {
                e.preventDefault();
                var fileId = $(this).data('file-id');
                var sheetId = $(this).data('sheet-id');
                var sheetName = $(this).data('sheet-name');
                $('#excelModalLabel').text('Nội dung Sheet Excel: ' + sheetName);

                $.ajax({
                    url: '{{ route("excel.readSheet", [":fileId", ":sheetId"]) }}'.replace(':fileId', fileId).replace(':sheetId', sheetId),
                    type: 'GET',
                    success: function(response) {
                        $('#excelContent').html(response);
                    },
                    error: function(xhr) {
                        $('#excelContent').html('<p class="text-danger">Lỗi: ' + (xhr.responseJSON?.error || 'Không thể tải nội dung Excel.') + '</p>');
                    }
                });
            });

            // Khởi tạo popover
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.forEach(function (popoverTriggerEl) {
                new bootstrap.Popover(popoverTriggerEl, {
                    trigger: 'click',
                    placement: 'top',
                    customClass: 'custom-popover'
                });
            });
        });
    </script>
</body>
</html>
```