
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tạo bảng mappings để lưu ánh xạ giữa biến trong Word và cột trong sheet Excel.
     */
    public function up(): void
    {
        Schema::create('mappings', function (Blueprint $table) {
            $table->id(); // ID duy nhất cho mỗi ánh xạ
            $table->unsignedBigInteger('doc_variable_id'); // ID của biến từ bảng doc_variables
            $table->string('var_name'); // Tên biến trong file Word (ví dụ: Tên DN SMEs)
            $table->string('table_name'); // Tên bảng động của sheet Excel (ví dụ: sheet_172_general)
            $table->unsignedBigInteger('original_headers_id'); // ID của sheet từ bảng excel_sheets
            $table->unsignedInteger('original_headers_index'); // Chỉ số của cột trong mảng original_headers
            $table->string('field'); // Tên cột gốc trong sheet Excel (ví dụ: Company name)
            $table->string('fields_mapping'); // Chuỗi kết hợp var_name và field (ví dụ: Tên DN SMEs&Company name)
            $table->timestamps(); // Cột created_at và updated_at

            // Khóa ngoại liên kết với doc_variables và excel_sheets, xóa ánh xạ khi bản ghi cha bị xóa
            $table->foreign('doc_variable_id')->references('id')->on('doc_variables')->onDelete('cascade');
            $table->foreign('original_headers_id')->references('id')->on('excel_sheets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     * Xóa bảng mappings khi rollback migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('mappings');
    }
};
