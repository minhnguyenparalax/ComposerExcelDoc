<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.ph[]
     */
    public function up(): void
    {
        Schema::create('doc_variables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('docfile_id'); // Liên kết với bảng docfile
            $table->string('var_name'); // Tên biến gốc (ví dụ: Tên pháp nhân triển khai)
            $table->string('table_var_name')->nullable(); // Tên bảng động tương ứng
            $table->boolean('is_table_variable_created')->default(0); // 0: chưa tạo, 1: đã tạo
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('docfile_id')->references('id')->on('docfile')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doc_variables');
    }
};
