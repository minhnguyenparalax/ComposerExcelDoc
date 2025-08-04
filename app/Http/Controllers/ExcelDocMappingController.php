<?php
namespace App\Http\Controllers;

use App\Models\DocVariable;
use App\Models\ExcelSheets;
use App\Models\Excelfiles;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Mapping;

class ExcelDocMappingController extends Controller
{
    // Lấy danh sách các cột từ các sheet Excel đã tạo bảng để hiển thị trong dropdown ánh xạ
    // Lấy danh sách các cột từ các sheet Excel đã tạo bảng để hiển thị trong dropdown ánh xạ
    public function getFields($variableId)
    {
        $sheets = [];
        $excelFiles = Excelfiles::whereHas('sheets', function ($query) {
            $query->where('is_table_created', true);
        })->with(['sheets' => function ($query) {
            $query->where('is_table_created', true);
        }])->get();

        foreach ($excelFiles as $excelFile) {
            foreach ($excelFile->sheets as $sheet) {
                $columns = Schema::getColumnListing($sheet->table_name);
                $columns = array_filter($columns, fn($column) => $column !== 'id' && $column !== 'created_at' && $column !== 'updated_at');
                $originalHeaders = json_decode($sheet->original_headers, true, 512, JSON_UNESCAPED_UNICODE) ?? [];
                $mappedColumns = [];
                foreach ($columns as $index => $column) {
                    $mappedColumns[] = [
                        'name' => $originalHeaders[$index] ?? $column, // Dùng tên gốc nếu có
                        'index' => $index, // Chỉ số của cột trong original_headers
                    ];
                }
                $sheets[] = [
                    'excel_file' => $excelFile->name,
                    'sheet_name' => $sheet->name,
                    'sheet_id' => $sheet->id, // ID của sheet để lưu vào original_headers_id
                    'table_name' => $sheet->table_name, // Tên bảng động
                    'columns' => $mappedColumns, // Danh sách cột với tên và chỉ số
                    // Sửa: Thêm original_headers để JavaScript duyệt trực tiếp
                    'original_headers' => $originalHeaders,
                ];
            }
        }

        return response()->json([
            'variable_id' => $variableId,
            'variable_name' => DocVariable::findOrFail($variableId)->var_name,
            'sheets' => $sheets,
        ]);
    }

    // Sửa: Lưu ánh xạ và cập nhật doc_name từ bảng động Docfile
    public function storeMapping(Request $request)
    {
        // Kiểm tra dữ liệu đầu vào
        $request->validate([
            'variable_id' => 'required|exists:doc_variables,id',
            'excel_sheet_id' => 'required|exists:excel_sheets,id',
            'table_name' => 'required|string',
            'original_headers_index' => 'required|integer',
            'field' => 'required|string',
        ], [
            'variable_id.exists' => 'Biến không tồn tại trong bảng doc_variables.',
            'excel_sheet_id.exists' => 'Sheet không tồn tại trong bảng excel_sheets.',
        ]);

        try {
            // Kiểm tra xem biến đã được ánh xạ trước đó chưa
            $existingMapping = Mapping::where('doc_variable_id', $request->variable_id)->first();
            if ($existingMapping) {
                Log::warning('Biến đã được ánh xạ: ' . $request->variable_id);
                return response()->json(['error' => 'Biến này đã được ánh xạ.'], 400);
            }

            // Lấy var_name đầy đủ từ bảng doc_variables
            $variable = DocVariable::findOrFail($request->variable_id);
            $varName = $variable->var_name; // Ví dụ: Tên DN SMEs

            // Thêm: Tìm bảng động Docfile từ docfile_id
            $docFile = \App\Models\Docfile::find($variable->docfile_id);
            if (!$docFile) {
                Log::error('Không tìm thấy Docfile', [
                    'variable_id' => $request->variable_id,
                    'docfile_id' => $variable->docfile_id
                ]);
                return response()->json(['error' => 'Không tìm thấy Docfile cho docfile_id: ' . ($variable->docfile_id ?? 'NULL')], 404);
            }

            // Thêm: Lấy tên bảng động từ docfile
            $docTableName = $docFile->table_name ?? null; // Ví dụ: doc_246_05_evaluate_the_quality_of_consulting_repaired
            if (!$docTableName) {
                Log::warning('Docfile không có table_name', [
                    'variable_id' => $request->variable_id,
                    'docfile_id' => $variable->docfile_id,
                    'docfile' => $docFile->toArray()
                ]);
                return response()->json(['error' => 'Docfile không có table_name cho docfile_id: ' . $variable->docfile_id], 400);
            }

            // Sửa: Tạo chuỗi fields_mapping và lưu doc_name
            $fieldsMapping = $varName . ' & ' . $request->field;
            $mapping = Mapping::create([
                'doc_variable_id' => $request->variable_id,
                'var_name' => $varName,
                'table_name' => $request->table_name,
                'original_headers_id' => $request->excel_sheet_id,
                'original_headers_index' => $request->original_headers_index,
                'field' => $request->field,
                'fields_mapping' => $fieldsMapping,
                'doc_name' => $docTableName // Thêm: Lưu tên bảng động
            ]);

            // Thêm: Ghi log chi tiết để debug
            Log::info('Ánh xạ thành công', [
                'variable_id' => $request->variable_id,
                'var_name' => $varName,
                'field' => $request->field,
                'table_name' => $request->table_name,
                'doc_name' => $docTableName,
                'mapping_id' => $mapping->id,
                'doc_name_stored' => $mapping->fresh()->doc_name // Kiểm tra giá trị thực sự lưu
            ]);

            // Sửa: Trả về thông báo thành công với doc_name
            return response()->json([
                'success' => 'Ánh xạ thành công cho ' . $varName . ', thuộc bảng ' . $docTableName,
                'variable_id' => $request->variable_id,
                'field' => $request->field,
                'table_name' => $request->table_name,
                'doc_name' => $docTableName
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lưu ánh xạ: ' . $e->getMessage(), [
                'request' => $request->all(),
                'variable_id' => $request->variable_id
            ]);
            return response()->json(['error' => 'Không thể lưu ánh xạ: ' . $e->getMessage()], 500);
        }
    }

    // Thêm: Xóa ánh xạ khỏi bảng mappings dựa trên doc_variable_id
    public function deleteMapping(Request $request)
    {
        // Kiểm tra dữ liệu đầu vào
        $request->validate([
            'variable_id' => 'required|exists:mappings,doc_variable_id',
        ], [
            'variable_id.exists' => 'Không tìm thấy ánh xạ cho biến này.',
        ]);

        try {
            // Xóa bản ghi ánh xạ từ bảng mappings
            $deleted = Mapping::where('doc_variable_id', $request->variable_id)->delete();

            if ($deleted) {
                // Trả về thông báo thành công
                return response()->json(['success' => 'Xóa ánh xạ thành công.']);
            } else {
                return response()->json(['error' => 'Không tìm thấy ánh xạ để xóa.'], 404);
            }
        } catch (\Exception $e) {
            // Ghi log lỗi để debug
            Log::error('Lỗi khi xóa ánh xạ: ' . $e->getMessage(), [
                'request' => $request->all(),
                'variable_id' => $request->variable_id
            ]);
            return response()->json(['error' => 'Không thể xóa ánh xạ: ' . $e->getMessage()], 500);
        }
    }
    
    // Sửa: Cập nhật primary_key với thông báo tùy theo tích/bỏ tích
    public function setPrimaryKey(Request $request)
    {
        $request->validate([
            'variable_id' => 'required|exists:doc_variables,id',
            'primary_key' => 'nullable|in:1' // Chỉ chấp nhận "1" hoặc NULL
        ]);

        DB::beginTransaction();
        try {
            $mapping = Mapping::where('doc_variable_id', $request->variable_id)->first();
            if (!$mapping) {
                return response()->json(['error' => 'Không tìm thấy ánh xạ cho variable_id: ' . $request->variable_id], 404);
            }
            // Ghi log trước khi cập nhật
            Log::info('Trước khi cập nhật primary_key', [
                'variable_id' => $request->variable_id,
                'var_name' => $mapping->var_name,
                'current_primary_key' => $mapping->primary_key,
                'new_primary_key' => $request->primary_key
            ]);
            
            // Cập nhật primary_key bằng save()
            $mapping->primary_key = $request->primary_key;
            $mapping->save();

            // Ghi log sau khi cập nhật
            Log::info('Sau khi cập nhật primary_key', [
                'variable_id' => $request->variable_id,
                'var_name' => $mapping->var_name,
                'updated_primary_key' => $mapping->primary_key
            ]);
            
            DB::commit();
            // Sửa: Trả về thông báo tùy theo primary_key
            return response()->json([
                'success' => $request->primary_key === '1' 
                    ? 'Đã cập nhật primary key cho ' . $mapping->var_name 
                    : 'Bạn đã bỏ tích primary_key của ' . $mapping->var_name,
                'variable_id' => $request->variable_id,
                'primary_key' => $mapping->primary_key,
                'var_name' => $mapping->var_name
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi cập nhật primary_key: ' . $e->getMessage(), ['request' => $request->all()]);
            return response()->json(['error' => 'Không thể cập nhật primary key: ' . $e->getMessage()], 500);
        }
    }

    // Sửa: Cập nhật ánh xạ và lưu doc_name từ bảng động DocFile với debug chi tiết
    public function mapField(Request $request)
    {
        $request->validate([
            'variable_id' => 'required|exists:doc_variables,id',
            'field' => 'required|string',
            'table_name' => 'required|string',
            'sheet_id' => 'required|exists:excel_sheets,id',
            'column_index' => 'required|integer'
        ]);

        try {
            // Tìm DocVariable
            $variable = DocVariable::find($request->variable_id);
            if (!$variable) {
                Log::error('Không tìm thấy DocVariable', ['variable_id' => $request->variable_id]);
                return response()->json(['error' => 'Không tìm thấy DocVariable cho variable_id: ' . $request->variable_id], 404);
            }

            // Sửa: Tìm bảng động DocFile từ docfile_id (thay vì doc_id)
            $docFile = \App\Models\Docfile::find($variable->docfile_id);
            if (!$docFile) {
                Log::error('Không tìm thấy Docfile', [
                    'variable_id' => $request->variable_id,
                    'docfile_id' => $variable->docfile_id
                ]);
                return response()->json(['error' => 'Không tìm thấy Docfile cho docfile_id: ' . ($variable->docfile_id ?? 'NULL')], 404);
            }

            // Sửa: Lấy tên bảng động từ docfile
            $docTableName = $docFile->table_name ?? null; // Ví dụ: doc_246_05_evaluate_the_quality_of_consulting_repaired
            if (!$docTableName) {
                Log::warning('Docfile không có table_name', [
                    'variable_id' => $request->variable_id,
                    'docfile_id' => $variable->docfile_id,
                    'docfile' => $docFile->toArray()
                ]);
                return response()->json(['error' => 'Docfile không có table_name cho docfile_id: ' . $variable->docfile_id], 400);
            }

            // Sửa: Lưu ánh xạ và cập nhật doc_name
            $mapping = Mapping::updateOrCreate(
                ['doc_variable_id' => $request->variable_id],
                [
                    'var_name' => $variable->var_name,
                    'field' => $request->field,
                    'table_name' => $request->table_name,
                    'original_headers_id' => $request->sheet_id,
                    'original_headers_index' => $request->column_index,
                    'fields_mapping' => $variable->var_name . '&' . $request->field,
                    'doc_name' => $docTableName // Sửa: Đảm bảo lưu doc_name
                ]
            );

            // Sửa: Ghi log chi tiết để debug
            Log::info('Ánh xạ thành công', [
                'variable_id' => $request->variable_id,
                'var_name' => $variable->var_name,
                'field' => $request->field,
                'table_name' => $request->table_name,
                'doc_name' => $docTableName,
                'mapping_id' => $mapping->id,
                'doc_name_stored' => $mapping->fresh()->doc_name // Kiểm tra giá trị thực sự lưu trong DB
            ]);

            return response()->json([
                'success' => 'Ánh xạ thành công cho ' . $variable->var_name . ', thuộc bảng ' . $docTableName,
                'variable_id' => $request->variable_id,
                'field' => $request->field,
                'table_name' => $request->table_name,
                'doc_name' => $docTableName // Sửa: Trả về doc_name
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi ánh xạ: ' . $e->getMessage(), [
                'request' => $request->all(),
                'variable_id' => $request->variable_id
            ]);
            return response()->json(['error' => 'Không thể ánh xạ: ' . $e->getMessage()], 500);
        }
    }
    
}