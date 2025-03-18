<?php namespace Nerd\Nerdai\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateMessagesTable Migration
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
        Schema::create('nerd_nerdai_messages', function(Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique();
            $table->integer('thread_id')->unsigned();
            $table->enum('role', ['user', 'assistant', 'system']);
            $table->text('content');
            $table->text('metadata')->nullable();
            $table->timestamps();

            $table->foreign('thread_id')
                ->references('id')
                ->on('nerd_nerdai_threads')
                ->onDelete('cascade');
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('nerd_nerdai_messages');
    }
};
