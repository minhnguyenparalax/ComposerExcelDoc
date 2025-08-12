<?php

namespace App\Observers;

use App\Models\Mapping;
use App\Models\TempSyncData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MappingObserver
{
    public function updating(Mapping $mapping)
    {
        if ($mapping->isDirty('primary_key') && $mapping->getOriginal('primary_key') === '1' && is_null($mapping->primary_key)) {
            TempSyncData::where('variable_id', $mapping->doc_variable_id)->delete();
            if ($mapping->doc_name && Schema::hasTable($mapping->doc_name)) {
                DB::table($mapping->doc_name)->truncate();
            }
            Log::info('Xóa dữ liệu liên quan do primary_key đổi thành NULL', [
                'variable_id' => $mapping->doc_variable_id,
                'doc_name' => $mapping->doc_name
            ]);
        }
    }
}