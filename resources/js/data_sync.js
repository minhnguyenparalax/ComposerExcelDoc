$(document).ready(function() {
    function syncData(variableId) {
        console.log('Bắt đầu syncData:', { variable_id: variableId });

        $.ajax({
            url: '{{ route("data.sync") }}',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: {
                variable_id: variableId
            },
            success: function(response) {
                console.log('Response syncData:', {
                    success: response.success,
                    variable_id: response.variable_id,
                    var_name: response.var_name,
                    doc_name: response.doc_name,
                    table_name: response.table_name,
                    error: response.error
                });
                if (response.success) {
                    alert(response.success);
                } else {
                    console.error('Lỗi từ server:', response.error);
                    alert(response.error || 'Lỗi khi đồng bộ dữ liệu.');
                }
            },
            error: function(xhr) {
                console.error('Lỗi AJAX syncData:', {
                    status: xhr.status,
                    response: xhr.responseJSON,
                    message: xhr.responseJSON?.error
                });
                alert(xhr.responseJSON?.error || 'Lỗi khi đồng bộ dữ liệu.');
            }
        });
    }

    $('.primary-key-checkbox').off('change').on('change', function(e) {
        e.preventDefault();
        var variableId = $(this).data('variable-id');
        var isChecked = $(this).is(':checked') ? '1' : null;

        console.log('Gửi AJAX setPrimaryKey:', {
            variable_id: variableId,
            primary_key: isChecked
        });

        $.ajax({
            url: '{{ route("excel_doc_mapping.setPrimaryKey") }}',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: {
                variable_id: variableId,
                primary_key: isChecked
            },
            success: function(response) {
                console.log('Response setPrimaryKey:', {
                    success: response.success,
                    variable_id: response.variable_id,
                    primary_key: response.primary_key,
                    var_name: response.var_name,
                    doc_name: response.doc_name,
                    error: response.error
                });
                if (response.success) {
                    alert(response.success);
                    if (isChecked === '1') {
                        console.log('Gọi syncData vì primary_key = 1');
                        syncData(variableId);
                    }
                } else {
                    console.error('Lỗi từ server:', response.error);
                    alert(response.error || 'Lỗi khi cập nhật primary key.');
                }
            },
            error: function(xhr) {
                console.error('Lỗi AJAX setPrimaryKey:', {
                    status: xhr.status,
                    response: xhr.responseJSON,
                    message: xhr.responseJSON?.error
                });
                alert(xhr.responseJSON?.error || 'Lỗi khi cập nhật primary key.');
            }
        });
    });
});