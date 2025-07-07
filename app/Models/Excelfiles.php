<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Excelfiles extends Model
{
    protected $fillable = ['name', 'path'];

    protected static function booted()
    {
        static::deleting(function ($excelfile) {
            foreach ($excelfile->sheets as $sheet) {
                if ($sheet->table_name && Schema::hasTable($sheet->table_name)) {
                    Schema::dropIfExists($sheet->table_name);
                }
                $sheet->delete();
            }
        });
    }


    public function sheets()
    {
        return $this->hasMany(ExcelSheets::class, 'excelfile_id');
    }
}