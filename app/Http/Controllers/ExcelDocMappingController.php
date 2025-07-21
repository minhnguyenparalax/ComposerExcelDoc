<?php
namespace App\Http\Controllers;

use App\Models\DocVariable;
use App\Models\ExcelSheets;
use App\Models\Excelfiles;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        // Lưu ánh xạ giữa biến Word và cột Excel vào bảng mappings
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

            // Sửa: Lấy var_name đầy đủ từ bảng doc_variables thay vì từ request
            $variable = DocVariable::findOrFail($request->variable_id);
            $varName = $variable->var_name; // Đảm bảo lấy var_name đầy đủ (ví dụ: Tên DN SMEs)

            // Sửa: Tạo chuỗi fields_mapping từ var_name của doc_variables và field từ request
            $fieldsMapping = $varName . ' & ' . $request->field;

            // Sửa: Lưu var_name từ doc_variables vào bảng mappings
            Mapping::create([
                'doc_variable_id' => $request->variable_id,
                'var_name' => $varName, // Sử dụng var_name từ doc_variables
                'table_name' => $request->table_name,
                'original_headers_id' => $request->excel_sheet_id,
                'original_headers_index' => $request->original_headers_index,
                'field' => $request->field,
                'fields_mapping' => $fieldsMapping,
            ]);

            // Trả về thông báo thành công
            return response()->json(['success' => 'Ánh xạ đã được lưu thành công.']);
        } catch (\Exception $e) {
            // Ghi log lỗi để debug
        Log::error('Lỗi khi lưu ánh xạ: ' . $e->getMessage(), [
                'request' => $request->all(),
                'variable_id' => $request->variable_id
            ]);
            return response()->json(['error' => 'Không thể lưu ánh xạ: ' . $e->getMessage()], 500);
        }
    }


}