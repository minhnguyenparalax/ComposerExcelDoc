```blade
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý file Word và Excel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            overflow: 
        }
        .variable-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .variable-item i {
            font-size: 14px;
            color: #0d6efd;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .variable-item i:hover {
            color: #00f6e2ff;
            text-shadow: 0 0 8px rgba(255, 202, 40, 0.8);
        }
        .variable-dropdown {
            max-height: 300px;
            overflow-y: auto;
            min-width: 300px;
            max-width: 600px;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            padding: 8px;
            position: absolute;
            z-index: 1000;
            display: none;
        }
        .variable-dropdown.show {
            display: block;
        }
        .variable-dropdown h6 {
            font-size: 13px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        .variable-dropdown ul {
            padding-left: 15px;
            margin-bottom: 0;
        }
        .variable-dropdown li {
            font-size: 12px;
            color: #555;
        }
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
                                                    <div>
                                                        <strong>Biến đã trích xuất:</strong>
                                                        @if ($doc->variables->count())
                                                            <ul style="margin-bottom:0;">
                                                                @foreach ($doc->variables as $variable)
                                                                    <li class="variable-item dropdown">
                                                                        <!-- Sửa: Hiển thị var_name và field ánh xạ từ bảng mappings với định dạng giống JavaScript -->
                                                                        {{ $variable->var_name }}
                                                                        @php
                                                                            // Truy vấn bảng mappings để lấy field ánh xạ cho biến
                                                                            $mapping = \App\Models\Mapping::where('doc_variable_id', $variable->id)->first();
                                                                            // Nếu có ánh xạ, hiển thị field với lớp CSS text-success ms-2 để đồng bộ hiệu ứng
                                                                            if ($mapping && $mapping->field) {
                                                                                echo '<span class="mapped-field text-success ms-2"> & ' . htmlspecialchars($mapping->field, ENT_QUOTES, 'UTF-8') . '</span>';
                                                                                // Thêm: Hiển thị nút Delete chỉ khi có ánh xạ
                                                                                echo '<i class="fa-solid fa-trash delete-mapping ms-2" title="Xóa ánh xạ" data-variable-id="' . $variable->id . '"></i>';
                                                                            }
                                                                        @endphp
                                                                        <i class="fa-solid fa-link mapping-icon" title="Ánh xạ variable và field" data-variable-id="{{ $variable->id }}" data-var-name="{{ $variable->var_name }}"></i>
                                                                        <div class="dropdown-menu variable-dropdown" id="sheet-list-{{ $variable->id }}"></div>
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

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>



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

            // Hàm khởi tạo sự kiện cho các icon mapping
            function initMappingIcons() {
                console.log('Initializing mapping icons...'); // Debug
                $('.mapping-icon').off('click').on('click', function() {
                    var $icon = $(this);
                    var variableId = $icon.data('variable-id');
                    var varName = $icon.parent().text().trim().split(' ')[0]; // Lấy var_name từ text của li
                    var $sheetList = $('#sheet-list-' + variableId);
                    console.log('Clicked mapping icon with variableId:', variableId); // Debug

                    // Toggle hiển thị/ẩn danh sách
                    if ($sheetList.hasClass('show')) {
                        $sheetList.removeClass('show').empty();
                        return;
                    }

                    // Gọi AJAX để lấy danh sách fields từ các sheet Excel đã tạo bảng
                    $.ajax({
                        url: '{{ route("excel_doc_mapping.getFields", ":variableId") }}'.replace(':variableId', variableId),
                        type: 'GET',
                        success: function(response) {
                            console.log('Response from getFields:', response); // Debug
                            var content = '<h6 class="dropdown-header">Fields trích xuất:</h6>';
                            if (response.sheets && response.sheets.length > 0) {
                                response.sheets.forEach(function(sheet) {
                                    content += '<div class="sheet-item">';
                                    content += '<h6>File: ' + sheet.excel_file + ' - Sheet: ' + sheet.sheet_name + '</h6>';
                                    content += '<ul>';
                                    // Sửa: Duyệt original_headers thay vì columns, chỉ hiển thị field không rỗng
                                    sheet.original_headers.forEach(function(header, index) {
                                        if (header && header.trim() !== '') { // Chỉ thêm header không rỗng
                                            content += '<li><a href="#" class="map-field" ' +
                                                'data-variable-id="' + variableId + '" ' +
                                                'data-var-name="' + varName + '" ' +
                                                'data-sheet-id="' + sheet.sheet_id + '" ' +
                                                'data-table-name="' + sheet.table_name + '" ' +
                                                'data-column-index="' + index + '" ' +
                                                'data-column-name="' + header + '">' + header + '</a></li>';
                                        }
                                    });
                                    content += '</ul>';
                                    content += '</div>';
                                });
                                var maxHeight = Math.min(100 + response.sheets.length * 80, 300);
                                $sheetList.css('max-height', maxHeight + 'px');
                            } else {
                                content = '<p class="text-muted">Không có sheet nào được tạo bảng.</p>';
                                $sheetList.css('max-height', '100px');
                            }
                            $sheetList.html(content).addClass('show');

                            // Xử lý sự kiện click vào field để lưu ánh xạ
                            $('.map-field').off('click').on('click', function(e) {
                                e.preventDefault();
                                var variableId = $(this).data('variable-id');
                                var varName = $(this).data('var-name');
                                var sheetId = $(this).data('sheet-id');
                                var tableName = $(this).data('table-name');
                                var columnIndex = $(this).data('column-index');
                                var columnName = $(this).data('column-name');

                                // Gửi yêu cầu AJAX để lưu ánh xạ vào bảng mappings
                                $.ajax({
                                    url: '{{ route("excel_doc_mapping.storeMapping") }}',
                                    type: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}' // Đảm bảo gửi CSRF token
                                    },
                                    data: {
                                        variable_id: variableId,
                                        var_name: varName,
                                        excel_sheet_id: sheetId,
                                        table_name: tableName,
                                        original_headers_index: columnIndex,
                                        field: columnName
                                    },
                                    success: function(response) {
                                        alert(response.success || 'Ánh xạ thành công!');
                                        $sheetList.removeClass('show').empty();
                                        // Sửa: Xóa span và nút delete cũ, thêm span mới và nút delete để đồng bộ với HTML
                                        $icon.parent().find('.mapped-field, .delete-mapping').remove(); // Xóa span ánh xạ và nút delete cũ nếu có
                                        // Thêm field ánh xạ với định dạng & field (ví dụ: & Project code), mã hóa để an toàn
                                        $icon.parent().append('<span class="mapped-field text-success ms-2"> & ' + $('<div/>').text(columnName).html() + '</span>');
                                        // Thêm: Thêm nút delete để cho phép xóa ánh xạ
                                        $icon.parent().append('<i class="fa-solid fa-trash delete-mapping ms-2" title="Xóa ánh xạ" data-variable-id="' + variableId + '"></i>');
                                    },
                                    error: function(xhr) {
                                        console.error('Error in storeMapping:', xhr.responseJSON); // Debug
                                        alert(xhr.responseJSON?.error || 'Lỗi khi lưu ánh xạ.');
                                    }
                                });
                            });

                            // Thêm: Xử lý sự kiện click vào nút delete để xóa ánh xạ
                            $(document).on('click', '.delete-mapping', function(e) {
                                e.preventDefault();
                                var variableId = $(this).data('variable-id');
                                var $parentLi = $(this).parent(); // Lưu tham chiếu đến li cha để cập nhật giao diện

                                // Gửi yêu cầu AJAX để xóa ánh xạ khỏi bảng mappings
                                $.ajax({
                                    url: '{{ route("excel_doc_mapping.deleteMapping") }}', // Route mới để xóa ánh xạ
                                    type: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}' // Đảm bảo gửi CSRF token
                                    },
                                    data: {
                                        variable_id: variableId
                                    },
                                    success: function(response) {
                                        alert(response.success || 'Xóa ánh xạ thành công!');
                                        // Xóa span ánh xạ và nút delete khỏi giao diện
                                        $parentLi.find('.mapped-field, .delete-mapping').remove();
                                    },
                                    error: function(xhr) {
                                        console.error('Error in deleteMapping:', xhr.responseJSON); // Debug
                                        alert(xhr.responseJSON?.error || 'Lỗi khi xóa ánh xạ.');
                                    }
                                });
                            });
                        },
                        error: function(xhr) {
                            console.error('Error in getFields:', xhr.responseJSON); // Debug
                            var errorMsg = xhr.responseJSON?.error || 'Không thể tải danh sách fields.';
                            $sheetList.html('<p class="text-danger">' + errorMsg + '</p>').addClass('show');
                        }
                    });
                });
            }

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
                        console.log('Response from selectDoc:', response); // Debug
                        // Reload lần 1 để hiển thị thông báo và khối biến
                        window.location.reload();
                        // Reload lần 2 sau 1 giây để hiển thị nút mapping
                        setTimeout(function() {
                            console.log('Triggering second reload...'); // Debug
                            window.location.reload();
                        }, 1000);
                    },
                    error: function(xhr) {
                        console.error('Error in selectDoc:', xhr.responseJSON); // Debug
                        $button.prop('disabled', false).text('Trích xuất');
                        var alert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                                    '<h5>Lỗi</h5><p>' + (xhr.responseJSON?.error || 'Không thể trích xuất biến.') + '</p>' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                        $('.container.mt-4').prepend(alert);
                    }
                });
            });

            // Khởi tạo sự kiện cho các icon mapping khi tải trang
            initMappingIcons();

            // Đóng dropdown khi click ra ngoài
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.mapping-icon, .variable-dropdown').length) {
                    $('.variable-dropdown.show').removeClass('show').empty();
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