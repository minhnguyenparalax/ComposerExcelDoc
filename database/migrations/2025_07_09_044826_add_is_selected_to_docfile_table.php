<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('docfile', function (Blueprint $table) {
            $table->boolean('is_selected')->default(0); // 0: chưa chọn, 1: đã chọn
        });
    }

    public function down(): void
    {
        Schema::table('docfile', function (Blueprint $table) {
            $table->dropColumn('is_selected');
        });
    }
};