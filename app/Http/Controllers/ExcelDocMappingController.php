<?php
namespace App\Http\Controllers;

use App\Models\DocVariable;
use App\Models\ExcelSheets;
use App\Models\Excelfiles;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class ExcelDocMappingController extends Controller
{
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
                    $mappedColumns[] = $originalHeaders[$index] ?? $column; // Dùng tên gốc nếu có
                }
                $sheets[] = [
                    'excel_file' => $excelFile->name,
                    'sheet_name' => $sheet->name,
                    'columns' => $mappedColumns,
                ];
            }
        }

        return response()->json([
            'variable_id' => $variableId,
            'variable_name' => DocVariable::findOrFail($variableId)->var_name,
            'sheets' => $sheets,
        ]);
    }
}