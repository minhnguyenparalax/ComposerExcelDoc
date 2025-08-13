<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TempSyncData extends Model
{
    protected $table = 'temp_sync_data';
    protected $fillable = [
        'variable_id',
        'excel_table_name',
        'field',
        'values',
        'doc_table_name',
        'var_name_column',
        'created_at',
        'updated_at'
    ];
}