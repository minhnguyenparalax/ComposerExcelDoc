<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExcelSheets extends Model
{
    protected $fillable = ['excelfile_id', 'name', 'table_name'];

    public function excelfile()
    {
        return $this->belongsTo(Excelfiles::class, 'excelfile_id');
    }
}
