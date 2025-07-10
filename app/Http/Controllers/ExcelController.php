<?php

namespace App\Http\Controllers;

use App\Models\Excelfiles;
use App\Models\ExcelSheets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ExcelController extends Controller
{
    public function viewExcelFiles()
    {
        $excelFiles = Excelfiles::with('sheets')->get();
        $docFiles = \App\Models\Docfile::all();
        $excelFilesWithCreatedSheets = Excelfiles::whereHas('sheets', function ($query) {
            $query->where('is_table_created', true);
        })->with(['sheets' => function ($query) {
            $query->where('is_table_created', true);
        }])->get();
        return view('file_reader', compact('excelFiles', 'docFiles', 'excelFilesWithCreatedSheets'));
    }

    public function addExcel(Request $request)
    {
        $request->validate([
            'file_path' => 'required|string',
        ]);

        $filePath = trim($request->input('file_path'), "\"'");
        $filePath = str_replace('/', '\\', $filePath);

        if (!file_exists($filePath)) {
            return back()->with('error', 'File không tồn tại tại: ' . $filePath);
        }

        $allowedExtensions = ['xlsx', 'xls'];
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            return back()->with('error', 'Định dạng file không hợp lệ. Chỉ hỗ trợ .xlsx hoặc .xls.');
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
            if (!$spreadsheet->getSheetCount()) {
                return back()->with('error', 'File rỗng hoặc không hợp lệ.');
            }

            $fileName = basename($filePath);
            if (Excelfiles::where('path', $filePath)->exists()) {
                return back()->with('error', 'File Excel với đường dẫn này đã tồn tại trong hệ thống.');
            }

            $excelfile = Excelfiles::create([
                'name' => $fileName,
                'path' => $filePath,
            ]);

            foreach ($spreadsheet->getSheetNames() as $sheetIndex => $sheetName) {
                $tableName = 'sheet_' . $excelfile->id . '_' . Str::slug($sheetName, '_');

                ExcelSheets::create([
                    'excelfile_id' => $excelfile->id,
                    'name' => $sheetName,
                    'table_name' => $tableName,
                ]);
            }

            return redirect()->route('file.index')->with('success', 'Đã thêm file Excel: ' . $fileName);
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            Log::error('Lỗi khi đọc file Excel: ' . $e->getMessage());
            return back()->with('error', 'Không thể đọc file Excel: Định dạng sai hoặc file hỏng.');
        } catch (\Exception $e) {
            Log::error('Lỗi hệ thống: ' . $e->getMessage());
            return back()->with('error', 'Lỗi không xác định: ' . $e->getMessage());
        }
    }

    public function removeExcel($id)
    {
        $excelfile = Excelfiles::with('sheets')->findOrFail($id);

        foreach ($excelfile->sheets as $sheet) {
            $tableName = 'sheet_' . $excelfile->id . '_' . Str::slug($sheet->name, '_');
            if (Schema::hasTable($tableName)) {
                Schema::dropIfExists($tableName);
            }
        }

        $excelfile->delete();

        return redirect()->route('file.index')->with('success', 'Đã xoá file Excel và các bảng liên quan.');
    }

    public function readSheet($fileId, $sheetId, $action = 'view')
    {
        $excelFile = Excelfiles::findOrFail($fileId);
        $sheet = ExcelSheets::where('excelfile_id', $fileId)->findOrFail($sheetId);

        $filePath = $excelFile->path;
        if (!file_exists($filePath)) {
            return redirect()->route('file.index')->with('error', 'File không tồn tại tại: ' . $filePath);
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getSheetByName($sheet->name);
            if (!$worksheet) {
                return redirect()->route('file.index')->with('error', 'Sheet không tồn tại trong file Excel.');
            }

            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            $mergeCells = $worksheet->getMergeCells();
            $mergeInfo = [];
            foreach ($mergeCells as $mergeRange) {
                [$startCell, $endCell] = explode(':', $mergeRange);
                [$startCol, $startRow] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::coordinateFromString($startCell);
                [$endCol, $endRow] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::coordinateFromString($endCell);
                $startColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($startCol);
                $endColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($endCol);
                $colspan = $endColIndex - $startColIndex + 1;
                $mergeInfo[$startRow][$startColIndex] = $colspan;
            }

            $data = [];
            $statusColumnIndex = null;

            for ($row = 1; $row <= $highestRow; $row++) {
                $rowData = [];
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $colspan = isset($mergeInfo[$row][$col]) ? $mergeInfo[$row][$col] : 1;
                    $cell = $worksheet->getCellByColumnAndRow($col, $row);
                    $value = $cell->getCalculatedValue();
                    $value = is_null($value) ? '' : (string)$value;
                    $rowData[$col - 1] = [
                        'value' => $value,
                        'colspan' => $colspan,
                    ];

                    if ($row === 1 && strtolower($value) === 'status') {
                        $statusColumnIndex = $col - 1;
                    }

                    if ($colspan > 1) {
                        for ($i = 1; $i < $colspan; $i++) {
                            $rowData[$col - 1 + $i] = ['value' => '', 'colspan' => 0];
                        }
                        $col += $colspan - 1;
                    }
                }
                $data[] = $rowData;
            }

            if (empty($data)) {
                return redirect()->route('file.index')->with('error', 'Không tìm thấy dữ liệu trong sheet.');
            }

            $lastNonEmptyRowIndex = 0;
            foreach ($data as $rowIndex => $rowData) {
                foreach ($rowData as $cell) {
                    if ($cell['value'] !== '' && trim($cell['value']) !== '') {
                        $lastNonEmptyRowIndex = max($lastNonEmptyRowIndex, $rowIndex);
                    }
                }
            }

            $nonEmptyColumns = [];
            for ($col = 0; $col < $highestColumnIndex; $col++) {
                $hasData = false;
                foreach ($data as $rowIndex => $rowData) {
                    if ($rowIndex === 0 && isset($rowData[$col]) && trim($rowData[$col]['value']) !== '') {
                        $hasData = true;
                        break;
                    }
                    if ($rowIndex > $lastNonEmptyRowIndex) {
                        continue;
                    }
                    if (isset($rowData[$col]) && $rowData[$col]['value'] !== '' && $rowData[$col]['colspan'] !== 0) {
                        $hasData = true;
                        break;
                    }
                }
                if ($hasData) {
                    $nonEmptyColumns[] = $col;
                }
            }

            if (empty($nonEmptyColumns)) {
                for ($col = 0; $col < $highestColumnIndex; $col++) {
                    if (isset($data[0][$col]) && trim($data[0][$col]['value']) !== '') {
                        $nonEmptyColumns[] = $col;
                    }
                }
            }

            $filteredData = [];
            foreach ($data as $rowIndex => $rowData) {
                if ($rowIndex > $lastNonEmptyRowIndex && $rowIndex !== 0) {
                    continue;
                }
                $filteredRow = [];
                foreach ($nonEmptyColumns as $col) {
                    $filteredRow[] = $rowData[$col] ?? ['value' => '', 'colspan' => 1];
                }
                $filteredData[] = $filteredRow;
            }

            if ($statusColumnIndex !== null) {
                $statusColumnIndex = array_search($statusColumnIndex, $nonEmptyColumns);
                if ($statusColumnIndex === false) {
                    $statusColumnIndex = null;
                }
            }

            $headers = array_map(fn($cell) => $cell['value'], $filteredData[0]);
            Log::debug("Tiêu đề sheet sau lọc: fileId={$fileId}, sheetId={$sheetId}, headers=" . json_encode($headers));

            $sheetData = session('sheet_data', []);
            $sheetData[$fileId][$sheetId] = $filteredData;
            session(['sheet_data' => $sheetData]);

            if ($action === 'select') {
                if ($sheet->is_table_created || Schema::hasTable($sheet->table_name)) {
                    return redirect()->route('file.index')->with('error', "Sheet {$sheet->name} đã có trong Database.");
                }

                $tableName = $sheet->table_name;
                if (!Schema::hasTable($tableName)) {
                    Schema::create($tableName, function (Blueprint $table) use ($filteredData) {
                        $table->id();
                        $usedColumns = [];

                        $headers = array_map('trim', array_column($filteredData[0], 'value'));
                        $totalColumns = count($headers);

                        for ($index = 0; $index < $totalColumns; $index++) {
                            $field = $headers[$index];
                            $baseName = !empty($field) ? Str::slug($field, '_') : 'null_' . $index;
                            $columnName = $baseName;
                            $suffix = 2;

                            while (in_array($columnName, $usedColumns)) {
                                $columnName = $baseName . '_' . $suffix++;
                            }

                            $usedColumns[] = $columnName;
                            $table->text($columnName)->nullable()->default(!empty($field) ? null : '-');
                        }

                        $table->timestamps();
                    });
                }

                $insertedCount = 0;
                foreach (array_slice($filteredData, 1) as $row) {
                    $rowData = ['created_at' => now(), 'updated_at' => now()];
                    foreach ($nonEmptyColumns as $index => $col) {
                        $value = $row[$index]['value'] ?? null;
                        $header = trim($filteredData[0][$index]['value']);
                        $columnName = !empty($header) ? Str::slug($header, '_') : 'null_' . $col;
                        $rowData[$columnName] = is_null($value) || $value === '' ? '-' : (string)$value;
                    }
                    DB::table($tableName)->insert($rowData);
                    $insertedCount++;
                }

                $sheet->update(['is_table_created' => true]);

                return redirect()->route('file.index')->with('success', "Đã tạo bảng {$sheet->table_name} (fileId: {$fileId}) và chèn $insertedCount dòng thành công.");
            }

            return view('sheet_data', [
                'excelFiles' => Excelfiles::with('sheets')->get(),
                'docFiles' => \App\Models\Docfile::all(),
                'data' => $filteredData,
                'statusColumnIndex' => $statusColumnIndex,
                'currentFileId' => $fileId,
                'currentSheetId' => $sheetId,
                'sheetName' => $sheet->name,
                'fileName' => $excelFile->name,
                'success' => "Đã đọc dữ liệu từ sheet '" . $sheet->name . "' của file '" . $excelFile->name . "' thành công."
            ]);

        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            Log::error('Lỗi khi đọc sheet: ' . $e->getMessage());
            return redirect()->route('file.index')->with('error', 'Không thể đọc sheet: Định dạng sai hoặc sheet hỏng.');
        } catch (\Exception $e) {
            Log::error('Lỗi hệ thống: ' . $e->getMessage());
            return redirect()->route('file.index')->with('error', 'Lỗi không xác định: ' . $e->getMessage());
        }
    }
}