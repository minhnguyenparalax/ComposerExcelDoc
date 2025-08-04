<?php

namespace App\Http\Controllers;

use App\Models\Mapping;
use App\Models\DocVariable;
use App\Models\Docfile;
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

        DB::beginTransaction();
        try {
            // Tìm bản ghi mapping với primary_key = 1
            $mapping = Mapping::where('doc_variable_id', $request->variable_id)
                ->where('primary_key', '1')
                ->first();

            if (!$mapping) {
                Log::warning('Không tìm thấy ánh xạ với primary_key = 1', [
                    'variable_id' => $request->variable_id
                ]);
                return response()->json(['error' => 'Không tìm thấy ánh xạ với primary_key = 1 cho variable_id: ' . $request->variable_id], 404);
            }

            // Lấy thông tin từ bản ghi mapping
            $fieldsMapping = $mapping->fields_mapping; // Tên DN SMEs & Company name
            $docTableName = $mapping->doc_name; // doc_246_05_evaluate_the_quality_of_consulting_repaired
            $excelTableName = $mapping->table_name; // sheet_180_summary
            $varName = $mapping->var_name; // Tên DN SMEs
            $field = $mapping->field; // Company name

            // Kiểm tra doc_name và table_name
            if (!$docTableName || !$excelTableName) {
                Log::error('doc_name hoặc table_name bị NULL', [
                    'variable_id' => $request->variable_id,
                    'doc_name' => $docTableName,
                    'table_name' => $excelTableName
                ]);
                return response()->json(['error' => 'doc_name hoặc table_name bị NULL'], 400);
            }

            // Kiểm tra bảng động Excel
            if (!Schema::hasTable($excelTableName)) {
                Log::error('Bảng động Excel không tồn tại', ['table_name' => $excelTableName]);
                return response()->json(['error' => 'Bảng động Excel không tồn tại: ' . $excelTableName], 400);
            }

            // Chuẩn hóa tên cột field thành dạng snake_case
            //$exactField = strtolower(preg_replace('/\s+/', '_', trim($field))); // Làm sạch khoảng trắng, chuyển "Company name" thành "company_name"
            $exactField = Str::snake(Str::slug($field, '_'));// Làm sạch khoảng trắng, chuyển "Company name" thành "company_name"
            // Kiểm tra cột field trong bảng động Excel
            $excelColumns = Schema::getColumnListing($excelTableName);
            if (!in_array($exactField, $excelColumns)) {
                Log::error('Cột field không tồn tại trong bảng động Excel', [
                    'table_name' => $excelTableName,
                    'original_field' => $field,
                    'tried_field' => $exactField,
                    'available_columns' => $excelColumns
                ]);
                return response()->json(['error' => 'Cột ' . $exactField . ' không tồn tại trong bảng ' . $excelTableName . '. Các cột có sẵn: ' . implode(', ', $excelColumns)], 400);
            }

            // Kiểm tra bảng động Word
            if (!Schema::hasTable($docTableName)) {
                Log::error('Bảng động Word không tồn tại', ['doc_name' => $docTableName]);
                return response()->json(['error' => 'Bảng động Word không tồn tại: ' . $docTableName], 400);
            }

            // Kiểm tra cột var_name trong bảng động Word
            $varNameColumn = Str::snake(Str::slug($varName, '_'));// Chuyển Tên DN SMEs thành ten_dn_smes,Tên DN SMEs thành ten_dn_smes (loại bỏ dấu tiếng Việt, thay khoảng trắng bằng _).
            $wordColumns = Schema::getColumnListing($docTableName);
            $exactVarNameColumn = null;
            foreach ($wordColumns as $column) {
                if (strtolower($column) === $varNameColumn || strtolower($column) === strtolower($varName)) {
                    $exactVarNameColumn = $column;
                    break;
                }
            }

            if (!$exactVarNameColumn) {
                Log::error('Cột var_name không tồn tại trong bảng động Word', [
                    'doc_name' => $docTableName,
                    'var_name' => $varName,
                    'tried_column' => $varNameColumn,
                    'available_columns' => $wordColumns
                ]);
                return response()->json(['error' => 'Cột ' . $varNameColumn . ' không tồn tại trong bảng ' . $docTableName . '. Các cột có sẵn: ' . implode(', ', $wordColumns)], 400);
            }

            // Lấy danh sách giá trị từ cột field trong bảng động Excel
            $values = DB::table($excelTableName)->pluck($exactField)->toArray();
            if (empty($values)) {
                Log::warning('Không có dữ liệu trong cột Excel', [
                    'table_name' => $excelTableName,
                    'field' => $exactField
                ]);
                return response()->json(['error' => 'Không có dữ liệu trong cột ' . $exactField . ' của bảng ' . $excelTableName], 400);
            }

            Log::info('Giá trị trích xuất từ cột Excel', [
                'table_name' => $excelTableName,
                'original_field' => $field,
                'used_field' => $exactField,
                'values' => $values
            ]);

            // Lưu giá trị tạm thời vào bảng temp_sync_data
            DB::table('temp_sync_data')->insert([
                'variable_id' => $request->variable_id,
                'excel_table_name' => $excelTableName,
                'field' => $exactField,
                'values' => json_encode($values, JSON_UNESCAPED_UNICODE), // Sửa Unicode
                'doc_table_name' => $docTableName,
                'var_name_column' => $exactVarNameColumn,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::info('Đã lưu giá trị tạm thời vào temp_sync_data', [
                'variable_id' => $request->variable_id,
                'excel_table_name' => $excelTableName,
                'field' => $exactField,
                'values' => $values,
                'doc_table_name' => $docTableName,
                'var_name_column' => $exactVarNameColumn
            ]);
            // Kiểm tra bảng Word
            $wordColumns = Schema::getColumnListing($docTableName);
            if (!in_array($exactVarNameColumn, $wordColumns)) {
                Log::error('Cột var_name không tồn tại', [
                    'doc_name' => $docTableName,
                    'var_name' => $exactVarNameColumn,
                    'columns' => $wordColumns
                ]);
                return response()->json(['error' => 'Cột ' . $exactVarNameColumn . ' không tồn tại'], 400);
            }

            // Xóa toàn bộ dữ liệu cũ trong bảng Word
            //DB::table($docTableName)->truncate();

            // Chèn bản ghi mới vào bảng Word với giá trị từ Excel
            // Chèn bản ghi mới, bỏ truncate
            foreach ($values as $value) {
                try {
                    DB::table($docTableName)->insert([
                        $exactVarNameColumn => $value
                    ]);
                } catch (\Exception $e) {
                    Log::error('Lỗi khi chèn bản ghi', [
                        'doc_name' => $docTableName,
                        'var_name' => $exactVarNameColumn,
                        'value' => $value,
                        'error' => $e->getMessage()
                    ]);
                    continue; // Bỏ qua bản ghi lỗi
                }
            }
            Log::info('Chèn giá trị vào bảng động Word thành công', [
                'doc_name' => $docTableName,
                'var_name' => $exactVarNameColumn,
                'values_inserted' => $values,
                'total_rows_after' => DB::table($docTableName)->count()
            ]);

            DB::commit();
            return response()->json([
                'success' => 'Đã chèn dữ liệu cho ' . $varName . ' từ bảng ' . $excelTableName . ' vào cột ' . $exactVarNameColumn . ' của bảng ' . $docTableName,
                'variable_id' => $request->variable_id,
                'var_name' => $varName,
                'doc_name' => $docTableName,
                'table_name' => $excelTableName,
                'values_inserted' => $values
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi đồng bộ dữ liệu: ' . $e->getMessage(), [
                'request' => $request->all(),
                'variable_id' => $request->variable_id
            ]);
            return response()->json(['error' => 'Không thể đồng bộ dữ liệu: ' . $e->getMessage()], 500);
        }
    }
}