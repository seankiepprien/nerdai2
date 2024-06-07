<?php namespace Nerd\Nerdai\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateAiModelsTable Migration
 */
class CreateAiModelsTable extends Migration
{
    public function up()
    {
        Schema::create('nerd_nerdai_ai_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('model_name');
            $table->string('model_api')->default('OpenAI');
            $table->json('model_tasks')->nullable();
            $table->string('model_description')->nullable();
            $table->text('model_card')->nullable();
            $table->text('model_response_handler')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('nerd_nerdai_ai_models');
    }
}
