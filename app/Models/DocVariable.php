<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocVariable extends Model
{
    protected $fillable = [
        'docfile_id',
        'var_name',
        'table_var_name',
        'is_table_variable_created',
    ];

    public function docfile()
    {
        return $this->belongsTo(Docfile::class);
    }
}