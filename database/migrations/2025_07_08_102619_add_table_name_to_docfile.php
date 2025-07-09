<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTableNameToDocfile extends Migration
{
    public function up()
    {
        Schema::table('docfile', function (Blueprint $table) {
            $table->string('table_name')->nullable()->after('content');
        });
    }

    public function down()
    {
        Schema::table('docfile', function (Blueprint $table) {
            $table->dropColumn('table_name');
        });
    }
}