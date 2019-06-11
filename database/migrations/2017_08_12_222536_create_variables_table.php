<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVariablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('variables', function (Blueprint $table) {
            $table->increments('id');
			$table->unsignedInteger('dataset_id');
			$table->unsignedInteger('concept_id');
			$table->string('code');
			$table->string('label');
			$table->string('typecode');
			$table->string('typelabel');
            $table->timestamps();

			$table->unique(array('dataset_id', 'code'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('variables');
    }
}
