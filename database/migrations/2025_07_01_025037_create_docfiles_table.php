<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docfile', function (Blueprint $table) {
            $table->id();
            $table->string('path', 255)->unique(); // Đường dẫn file Word
            $table->string('name', 255)->nullable()->after('path'); // Thêm cột name
            $table->longText('content')->nullable(); // Nội dung HTML của file Word
            $table->string('primary_key')->nullable(); // Biến khóa chính (tạm thời)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docfile');
    }
};