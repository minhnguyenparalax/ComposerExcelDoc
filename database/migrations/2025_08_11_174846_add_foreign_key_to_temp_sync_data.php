<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeyToTempSyncData extends Migration
{
    public function up()
    {
        Schema::table('temp_sync_data', function (Blueprint $table) {
            $table->foreign('variable_id')->references('id')->on('doc_variables')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('temp_sync_data', function (Blueprint $table) {
            $table->dropForeign(['variable_id']);
        });
    }
}