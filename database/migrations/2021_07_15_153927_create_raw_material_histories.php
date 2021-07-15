<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawMaterialHistories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('raw_material_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('raw_material_id');
            $table->integer('changes');
            $table->date('date');
            $table->string('description')->nullable();
            $table->string('source')->nullable();
            $table->unsignedInteger('source_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raw_material_histories');
    }
}
