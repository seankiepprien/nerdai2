<?php namespace Nerd\Nerdai\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateRecordsTable Migration
 */

class CreateModelsTasksTable extends Migration
{
    public function up()
    {
        Schema::create('nerd_nerdai_models_tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('model_id');
            $table->integer('task_id');
            $table->text('specific_prompt')->nullable();
            $table->text('response_handler')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('nerd_nerdai_models_tasks');
    }
}
