<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->integer('author_id');
            $table->enum('paid',['yes','no']);
            $table->double('amount')->default(0.00);
            $table->integer('duration');
            $table->float('passmark')->default(0.00);
            $table->integer('number_of_questions_to_display');
            $table->string('description')->nullable();
            $table->enum('award_certificate',['yes','no'])->default('no');
            $table->enum('published',['yes','no'])->default('no');
            $table->dateTime('deadline')->nullable();
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
        Schema::dropIfExists('tests');
    }
}
