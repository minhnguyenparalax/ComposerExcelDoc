<?php
namespace App\Http\Controllers;

use App\Models\DocVariable;
use App\Models\ExcelSheets;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class ExcelDocMappingController extends Controller
{
    public function getFields($variableId)
    {
        try {
            $variable = DocVariable::findOrFail($variableId);
            $sheets = ExcelSheets::where('is_table_created', true)
                ->with('excelfile')
                ->get();
            $sheetsData = [];
            foreach ($sheets as $sheet) {
                $tableName = $sheet->table_name;
                if (Schema::hasTable($tableName)) {
                    $columns = Schema::getColumnListing($tableName); // Không lọc id, null_0
                    $sheetsData[] = [
                        'excel_file' => $sheet->excelfile->name,
                        'sheet_name' => $sheet->name,
                        'columns' => array_values($columns)
                    ];
                }
            }
            return response()->json([
                'variable_id' => $variableId,
                'variable_name' => $variable->var_name,
                'sheets' => $sheetsData
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy danh sách cột: ' . $e->getMessage());
            return response()->json(['error' => 'Không thể tải danh sách cột'], 500);
        }
    }
}