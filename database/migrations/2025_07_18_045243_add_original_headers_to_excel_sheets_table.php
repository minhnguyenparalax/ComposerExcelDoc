<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Models\Excelfiles;
use PhpOffice\PhpSpreadsheet\IOFactory;

use Illuminate\Support\Facades\Schema;

class AddOriginalHeadersToExcelSheetsTable extends Migration
{
    public function up()
    {
        Schema::table('excel_sheets', function (Blueprint $table) {
            $table->json('original_headers')->nullable()->after('name');
        });
        // Cập nhật dữ liệu cũ
        foreach (Excelfiles::all() as $excelFile) {
            $spreadsheet = IOFactory::load($excelFile->path);
            foreach ($excelFile->sheets as $sheet) {
                $worksheet = $spreadsheet->getSheetByName($sheet->name);
                $highestColumn = $worksheet->getHighestColumn();
                $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
                $headers = [];
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $value = $worksheet->getCellByColumnAndRow($col, 1)->getValue();
                    $headers[] = $value !== null ? preg_replace('/[\r\n]+/', ' ', trim($value)) : '';
                }
                $sheet->original_headers = json_encode($headers, JSON_UNESCAPED_UNICODE);
                $sheet->save();
            }
        }
    }

    public function down()
    {
        Schema::table('excel_sheets', function (Blueprint $table) {
            $table->dropColumn('original_headers');
        });
    }
}
