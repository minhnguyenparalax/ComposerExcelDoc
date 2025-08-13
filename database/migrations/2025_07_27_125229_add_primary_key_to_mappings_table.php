<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrimaryKeyToMappingsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mappings', function (Blueprint $table) {
            // Thêm cột primary_key kiểu string, nullable
            $table->string('primary_key')->nullable()->after('field');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mappings', function (Blueprint $table) {
            // Xóa cột primary_key nếu rollback
            $table->dropColumn('primary_key');
        });
    }
}
