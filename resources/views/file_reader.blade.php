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


        .variable-item {
            display: flex;
            align-items: center;
            gap: 8px; /* Khoảng cách giữa tên biến và icon */
        }
        .variable-item i {
            font-size: 14px; /* Kích thước icon */
            color: #0d6efd; /* Màu icon, trùng với màu liên kết trong giao diện */
        }






        .popover-btn {
            padding: 2px 4px;
            font-size: 12px;
        }
        .popover-btn i {
            font-size: 10px;
        }
        .popover {
            min-width: 250px;
            max-width: 400px;
            font-size: 15px;
            padding: 10px 14px;
            word-break: break-word;
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
        .popover-body ul {
            padding-left: 18px;
            margin-bottom: 0;
        }
        .popover-body li {
            margin-bottom: 4px;
            font-size: 15px;
        }
        /* Thêm để giảm khoảng trắng */
        .row + .row {
            margin-top: 1rem;
        }
        .card mb-3 {
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

        
    

        <!-- Form đọc file Excel -->
    <div class="row">
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

        <!-- Sửa section đầu tiên (form Thêm File Word và Đọc File Excel) -->

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

            <!-- Sửa section Danh sách file Word (popover và nút Xem nội dung) -->

        <div class="card-body h6">
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
                                                <div class="extracted-vars-block" style="background:#f8f9fa;border:1px solid #e0e0e0;padding:10px 16px;border-radius:6px;min-width:220px;max-width:400px;">
                                                    <strong>Biến đã trích xuất:</strong>
                                                    @if ($doc->variables->count())
                                                        <ul style="margin-bottom:0;">
                                                            @foreach ($doc->variables as $variable)
                                                                <li class="variable-item">
                                                                    {{ $variable->var_name }}
                                                                    <i class="fa-solid fa-link" title="Mapping"></i>
                                                                    
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <div class="text-muted">Không tìm thấy biến nào trong file này.</div>
                                                    @endif
                                                </div>
                                            @else
                                                <button class="btn btn-primary select-doc" data-doc-id="{{ $doc->id }}" style="font-weight:600;min-width:120px;">Trích xuất</button>
                                            @endif
                                            <a href="#" class="btn btn-sm btn-secondary view-doc" data-doc-id="{{ $doc->id }}" data-doc-name="{{ $doc->name }}" data-bs-toggle="modal" data-bs-target="#docModal">Xem nội dung</a>
                                            <form action="{{ route('doc.removeDoc') }}" method="POST" style="display: inline-block;">
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
        </div>
    </div>

        

    

        <!-- Giữ nguyên section Danh sách Sheet đã được tạo bảng -->
    <div class="row">
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
    </div>

    

    


        <!-- Sửa script (thêm AJAX cho selectDoc và initPopovers) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    
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
            $(document).on('click', '.select-doc', function(e) {
                e.preventDefault();
                var docId = $(this).data('doc-id');
                var $button = $(this);
                $button.prop('disabled', true).text('Đang trích xuất...');
                $.ajax({
                    url: '{{ route("doc.selectDoc", ":docId") }}'.replace(':docId', docId),
                    type: 'GET',
                    success: function(response) {
                        var block = $('<div class="extracted-vars-block" style="background:#f8f9fa;border:1px solid #e0e0e0;padding:10px 16px;border-radius:6px;min-width:220px;max-width:400px;"></div>');
                        block.append('<strong>Biến đã trích xuất:</strong>');
                        if (Array.isArray(response.variables) && response.variables.length > 0) {
                            var ul = $('<ul style="margin-bottom:0;"></ul>');
                            response.variables.forEach(function(v) { ul.append('<li>' + v + '</li>'); });
                            block.append(ul);
                        } else {
                            block.append('<div class="text-muted">Không tìm thấy biến nào trong file này.</div>');
                        }
                        $button.replaceWith(block);
                        var alert = '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                                    '<h5>Thông báo</h5><p>Đã trích xuất biến cho tài liệu "' + response.doc_name + '".</p>' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                        $('.container.mt-4').prepend(alert);
                    },
                    error: function(xhr) {
                        $button.prop('disabled', false).text('Trích xuất');
                        var alert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                                    '<h5>Lỗi</h5><p>' + (xhr.responseJSON?.error || 'Không thể trích xuất biến.') + '</p>' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                        $('.container.mt-4').prepend(alert);
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

        
        
    </script>

    

    <!-- Modal xem nội dung file Word -->
    <div class="modal fade" id="docModal" tabindex="-1" aria-labelledby="docModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="docModalLabel">Nội dung File Word</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="docContent">
            <div class="text-center text-muted">Đang tải nội dung...</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal cho nội dung Excel -->
        <div class="modal fade" id="excelModal" tabindex="-1" aria-labelledby="excelModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="excelModalLabel">Nội dung Sheet Excel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="excelContent"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    

</body>
</html>
```


