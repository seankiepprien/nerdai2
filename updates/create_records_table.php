<?php namespace Nerd\Nerdai\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateRecordsTable Migration
 */
class CreateRecordsTable extends Migration
{
    public function up()
    {
        Schema::create('nerd_nerdai_records', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->string('record_type')->nullable();
            $table->json('prompt')->nullable();
            $table->text('generated')->nullable();
            $table->string('test_sentiment_analysis_source')->nullable();
            $table->string('test_sentiment_analysis')->nullable();
            $table->float('test-sentiment_analysis_score')->nullable();
            $table->text('test_classification_source')->nullable();
            $table->string('test_classification')->nullable();
            $table->boolean('do_not_purge')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('nerd_nerdai_records');
    }
}
