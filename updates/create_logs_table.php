<?php namespace Nerd\Nerdai\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateLogsTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
return new class extends Migration
{
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::create('nerd_nerdai_logs', function(Blueprint $table) {
            $table->id();
            $table->string('model');
            $table->string('task');
            $table->string('mode');
            $table->boolean('taken_prompt')->default(false);
            $table->json('request');
            $table->json('response');
            $table->timestamps();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('nerd_nerdai_logs');
    }
};
