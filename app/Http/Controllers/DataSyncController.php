<?php

namespace App\Http\Controllers;

use App\Models\Mapping;
use App\Models\TempSyncData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DataSyncController extends Controller
{
    public function syncData(Request $request)
    {
        $request->validate([
            'variable_id' => 'required|exists:doc_variables,id'
        ]);

        try {
            DB::beginTransaction();

            $mapping = Mapping::where('doc_variable_id', $request->variable_id)
                ->where('primary_key', '1')
                ->first();

            if (!$mapping) {
                Log::info('Không tìm thấy ánh xạ với primary_key = 1', [
                    'variable_id' => $request->variable_id
                ]);
                return response()->json(['success' => 'Không có ánh xạ để đồng bộ'], 200);
            }

            $docTableName = $mapping->doc_name;
            $excelTableName = $mapping->table_name;
            $varName = $mapping->var_name;
            $field = $mapping->field;

            if (!$docTableName || !$excelTableName) {
                Log::error('doc_name hoặc table_name bị NULL', [
                    'variable_id' => $request->variable_id
                ]);
                return response()->json(['error' => 'doc_name hoặc table_name bị NULL'], 400);
            }

            if (!Schema::hasTable($excelTableName)) {
                Log::error('Bảng Excel không tồn tại', ['table_name' => $excelTableName]);
                return response()->json(['error' => 'Bảng Excel không tồn tại'], 400);
            }

            $exactField = Str::snake(Str::slug($field, '_'));
            $excelColumns = Schema::getColumnListing($excelTableName);
            if (!in_array($exactField, $excelColumns)) {
                Log::error('Cột field không tồn tại', [
                    'table_name' => $excelTableName,
                    'field' => $exactField,
                    'columns' => $excelColumns
                ]);
                return response()->json(['error' => 'Cột ' . $exactField . ' không tồn tại'], 400);
            }

            if (!Schema::hasTable($docTableName)) {
                Log::error('Bảng Word không tồn tại', ['doc_name' => $docTableName]);
                return response()->json(['error' => 'Bảng Word không tồn tại'], 400);
            }

            $varNameColumn = Str::snake(Str::slug($varName, '_'));
            $wordColumns = Schema::getColumnListing($docTableName);
            if (!in_array($varNameColumn, $wordColumns)) {
                Log::error('Cột var_name không tồn tại', [
                    'doc_name' => $docTableName,
                    'var_name' => $varNameColumn,
                    'columns' => $wordColumns
                ]);
                return response()->json(['error' => 'Cột ' . $varNameColumn . ' không tồn tại'], 400);
            }

            $values = DB::table($excelTableName)->pluck($exactField)->toArray();
            if (empty($values)) {
                Log::warning('Không có dữ liệu trong cột Excel', [
                    'table_name' => $excelTableName,
                    'field' => $exactField
                ]);
                return response()->json(['error' => 'Không có dữ liệu trong cột ' . $exactField], 400);
            }

            Log::info('Giá trị trích xuất', [
                'table_name' => $excelTableName,
                'field' => $exactField,
                'values' => $values
            ]);

            TempSyncData::create([
                'variable_id' => $request->variable_id,
                'excel_table_name' => $excelTableName,
                'field' => $exactField,
                'values' => json_encode($values, JSON_UNESCAPED_UNICODE),
                'doc_table_name' => $docTableName,
                'var_name_column' => $varNameColumn,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            foreach ($values as $value) {
                try {
                    DB::table($docTableName)->insert([
                        $varNameColumn => $value
                    ]);
                } catch (\Exception $e) {
                    Log::error('Lỗi chèn bản ghi', [
                        'doc_name' => $docTableName,
                        'var_name' => $varNameColumn,
                        'value' => $value,
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            }

            Log::info('Chèn dữ liệu thành công', [
                'doc_name' => $docTableName,
                'var_name' => $varNameColumn,
                'values_inserted' => $values,
                'total_rows' => DB::table($docTableName)->count()
            ]);

            DB::commit();
            return response()->json([
                'success' => 'Đã chèn dữ liệu thành công',
                'variable_id' => $request->variable_id,
                'var_name' => $varName,
                'doc_name' => $docTableName,
                'table_name' => $excelTableName,
                'values_inserted' => $values
            ]);
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('Lỗi đồng bộ: ' . $e->getMessage(), [
                'variable_id' => $request->variable_id
            ]);
            return response()->json(['error' => 'Lỗi đồng bộ: ' . $e->getMessage()], 500);
        }
    }
}