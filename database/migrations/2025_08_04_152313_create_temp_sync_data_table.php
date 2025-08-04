<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTempSyncDataTable extends Migration
{
    public function up()
    {
        Schema::create('temp_sync_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('variable_id');
            $table->string('excel_table_name');
            $table->string('field');
            $table->text('values')->nullable(); // Lưu mảng giá trị dưới dạng JSON
            $table->string('doc_table_name');
            $table->string('var_name_column');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('temp_sync_data');
    }
}