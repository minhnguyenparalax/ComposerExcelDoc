<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đọc File Excel và Word</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <!-- Danh sách Excel -->
            <div class="col-md-6">
                <div class="card mb-4">
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

            <!-- Danh sách Doc -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">Đọc File Doc</div>
                    <div class="card-body">
                        <form action="{{ route('doc.addDoc') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="doc_file_path" class="form-label">Nhập Đường Dẫn File Doc (.doc hoặc .docx)</label>
                                <input type="text" class="form-control" id="doc_file_path" name="file_path" placeholder="Đường dẫn có thể có hoặc không có dấu ngoặc kép">
                            </div>
                            <button type="submit" class="btn btn-primary">Thêm Doc</button>
                        </form>

                        @if ($docFiles->isNotEmpty())
                            <h5 class="mt-4">Danh Sách Doc</h5>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>File</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($docFiles as $doc)
                                        <tr>
                                            <td class="file-column">
                                                <a href="#" class="btn btn-sm btn-info view-doc" data-doc-id="{{ $doc->id }}" data-doc-name="{{ $doc->name }}" data-bs-toggle="modal" data-bs-target="#docModal">
                                                    Xem Doc
                                                </a>
                                                <span class="doc-name">
                                                    {{ $doc->name ?: '[Tên file không xác định]' }}
                                                </span>
                                            </td>
                                            <td>
                                                <form action="{{ route('doc.removeDoc') }}" method="POST">
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
                            <p>Không có file Doc nào.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal cho nội dung Word -->
        <div class="modal fade" id="docModal" tabindex="-1" aria-labelledby="docModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="docModalLabel">Nội dung File Word</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="docContent"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Xử lý nút Xem cho Doc
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
                        console.log('Excel content loaded:', response);
                    },
                    error: function(xhr) {
                        $('#excelContent').html('<p class="text-danger">Lỗi: ' + (xhr.responseJSON?.error || 'Không thể tải nội dung Excel.') + '</p>');
                        console.error('Error loading Excel content:', xhr.responseText);
                    }
                });
            });

        
            // Xử lý nút Chọn cho Sheet
            

        });
    </script>
</body>
</html>