<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDocNameToMappingsTable extends Migration
{
    public function up()
    {
        Schema::table('mappings', function (Blueprint $table) {
            // Thêm cột doc_name, kiểu VARCHAR, có thể NULL
            $table->string('doc_name')->nullable()->after('table_name');
        });
    }

    public function down()
    {
        Schema::table('mappings', function (Blueprint $table) {
            $table->dropColumn('doc_name');
        });
    }
}