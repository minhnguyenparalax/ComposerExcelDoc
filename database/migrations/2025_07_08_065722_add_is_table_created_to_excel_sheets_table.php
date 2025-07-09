<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsTableCreatedToExcelSheetsTable extends Migration
{
    public function up()
    {
        Schema::table('excel_sheets', function (Blueprint $table) {
            $table->boolean('is_table_created')->default(false);
        });
    }

    public function down()
    {
        Schema::table('excel_sheets', function (Blueprint $table) {
            $table->dropColumn('is_table_created');
        });
    }
}