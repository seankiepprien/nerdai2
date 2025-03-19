<?php namespace Nerd\Nerdai\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateThreadsTable Migration
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
        if (!Schema::hasTable('nerd_nerdai_threads')) {
            Schema::create('nerd_nerdai_threads', function(Blueprint $table) {
                $table->id();
                $table->string('thread_id')->unique();
                $table->integer('assistant_id')->unsigned();
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->text('metadata')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('assistant_id')
                    ->references('id')
                    ->on('nerd_nerdai_assistants')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        if (Schema::hasTable('nerd_nerdai_threads')) {
            Schema::dropIfExists('nerd_nerdai_threads');
        }
    }
};
