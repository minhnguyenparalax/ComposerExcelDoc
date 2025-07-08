<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('excel_sheets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('excelfile_id');
            $table->string('name');
            $table->timestamps();

            $table->foreign('excelfile_id')
                ->references('id')->on('excelfiles')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('excel_sheets');
    }
};