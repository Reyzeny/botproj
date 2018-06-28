<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserDatasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_datas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_id');
            $table->enum('context', ['greeting', 'email', 'firstname', 'lastname', 'test_name', 'test_selection', 'payment', 'test_start', 'test_on','test_finished', 'test_completed'])->nullable();
            $table->integer('test_id')->nullable();
            $table->string('test_name')->nullable();
            $table->integer('test_by_author_id')->nullable();
            $table->string('question')->nullable();
            $table->string('options')->nullable();
            $table->string('question_answers')->nullable();
            $table->string('user_selected_answers')->nullable();
            $table->string('explanations')->nullable();
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
        Schema::dropIfExists('user_datas');
    }
}
