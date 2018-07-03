<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_id');
            $table->string('email');
            $table->integer('test_id');
            $table->integer('author_id');
            $table->enum('payment_method', ['atm_card', 'bank_transfer'])->nullable();
            $table->double('amount')->default(0.00);
            $table->string('payment_ref')->nullable();
            $table->enum('status', ['unprocessed', 'processing', 'processed'])->default('unprocessed');
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
        Schema::dropIfExists('transactions');
    }
}
