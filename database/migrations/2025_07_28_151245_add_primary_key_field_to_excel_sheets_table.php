```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrimaryKeyFieldToExcelSheetsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('excel_sheets', function (Blueprint $table) {
            // Thêm cột primary_key_field kiểu string, nullable
            $table->string('primary_key_field')->nullable()->after('table_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('excel_sheets', function (Blueprint $table) {
            // Xóa cột primary_key_field nếu rollback
            $table->dropColumn('primary_key_field');
        });
    }
}
