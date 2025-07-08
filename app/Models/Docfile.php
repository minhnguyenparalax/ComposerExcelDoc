<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Docfile extends Model
{
    use HasFactory;

    protected $table = 'docfile';

    protected $fillable = [
        'path',
        'name',
        'content',
        'primary_key',
    ];
}