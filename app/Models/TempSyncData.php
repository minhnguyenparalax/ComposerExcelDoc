<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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

    protected static function boot()
    {
        parent::boot();

        // Trước khi tạo bản ghi temp_sync_data, kiểm tra primary_key
        static::creating(function ($model) {
            $mapping = Mapping::where('doc_variable_id', $model->variable_id)
                ->where('primary_key', '1')
                ->first();

            if (!$mapping) {
                Log::warning('Không cho phép tạo temp_sync_data do không có primary_key = 1', [
                    'variable_id' => $model->variable_id
                ]);
                return false; // Chặn tạo bản ghi
            }
        });

        // Khi xóa bản ghi temp_sync_data, truncate bảng doc_table_name
        static::deleting(function ($model) {
            if ($model->doc_table_name && Schema::hasTable($model->doc_table_name)) {
                DB::table($model->doc_table_name)->truncate();
                Log::info('Truncate bảng doc_table_name do xóa temp_sync_data', [
                    'variable_id' => $model->variable_id,
                    'doc_table_name' => $model->doc_table_name
                ]);
            }
        });
    }

    // Hàm tĩnh để xóa dữ liệu liên quan khi primary_key thành NULL hoặc Mapping bị xóa
    public static function clearRelatedData($variableId)
    {
        $mapping = Mapping::where('doc_variable_id', $variableId)->first();
        if (!$mapping || is_null($mapping->primary_key)) {
            $tempSyncData = static::where('variable_id', $variableId)->get();
            foreach ($tempSyncData as $data) {
                if ($data->doc_table_name && Schema::hasTable($data->doc_table_name)) {
                    DB::table($data->doc_table_name)->truncate();
                    Log::info('Truncate bảng doc_table_name do primary_key NULL hoặc Mapping không tồn tại', [
                        'variable_id' => $variableId,
                        'doc_table_name' => $data->doc_table_name
                    ]);
                }
            }
            static::where('variable_id', $variableId)->delete();
            Log::info('Xóa temp_sync_data do primary_key NULL hoặc Mapping không tồn tại', [
                'variable_id' => $variableId
            ]);
        }
    }
}