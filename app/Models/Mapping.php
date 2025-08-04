<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mapping extends Model
{
    // Các cột có thể điền giá trị
    protected $fillable = [
        'doc_variable_id',
        'var_name',
        'table_name',
        'original_headers_id',
        'original_headers_index',
        'field',
        'fields_mapping',
        'primary_key', // Thêm: Để lưu primary_key
        'doc_name'    // Thêm: Để lưu tên bảng động

    ];

    // Quan hệ với bảng doc_variables
    public function docVariable()
    {
        return $this->belongsTo(DocVariable::class, 'doc_variable_id');
    }

    // Quan hệ với bảng excel_sheets
    public function excelSheet()
    {
        return $this->belongsTo(ExcelSheets::class, 'original_headers_id');
    }
}
