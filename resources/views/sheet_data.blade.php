<style>
    .content {
        font-family: Arial, sans-serif !important;
        font-size: 12px !important;
        padding: 20px;
        overflow-x: auto;
    }
    .content table {
        width: 100%;
        max-width: 800px;
        border-collapse: collapse;
        margin-bottom: 16px;
    }
    .content table td {
        border: 1px solid #dee2e6;
        padding: 6px;
        vertical-align: top;
        font-size: 12px !important;
        text-align: center;
    }
</style>

<div class="content">
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <p>{{ session('success') }}</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (!empty($data))
        <table class="table table-bordered">
            @foreach ($data as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td {{ $cell['colspan'] > 1 ? 'colspan="' . $cell['colspan'] . '"' : '' }}>
                            {{ $cell['value'] === '' ? '-' : htmlspecialchars($cell['value'], ENT_QUOTES, 'UTF-8') }}
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    @else
        <p>Không có dữ liệu trong sheet.</p>
    @endif
</div>