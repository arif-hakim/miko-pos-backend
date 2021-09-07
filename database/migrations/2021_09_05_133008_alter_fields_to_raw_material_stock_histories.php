<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterFieldsToRawMaterialStockHistories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('raw_material_stock_histories', function (Blueprint $table) {
            $table->float('from')->change();
            $table->float('to')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('raw_material_stock_histories', function (Blueprint $table) {
            $table->integer('from')->change();
            $table->integer('to')->change();
        });
    }
}
