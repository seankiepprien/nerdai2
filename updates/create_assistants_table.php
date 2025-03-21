<?php namespace Nerd\Nerdai\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateAssistantsTable Migration
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
        if (!Schema::hasTable('nerd_nerdai_assistants')) {
            Schema::create('nerd_nerdai_assistants', function(Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('assistant_id')->unique();
                $table->text('description')->nullable();
                $table->text('instructions')->nullable();
                $table->string('model');
                $table->text('tools')->nullable();
                $table->boolean('enable_code_interpreter')->default(false);
                $table->boolean('enable_retrieval')->default(false);
                $table->boolean('enable_function_calling')->default(false);
                $table->text('function_schemas')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        if (Schema::hasTable('nerd_nerdai_assistants')) {
            Schema::dropIfExists('nerd_nerdai_assistants');
        }
    }
};
