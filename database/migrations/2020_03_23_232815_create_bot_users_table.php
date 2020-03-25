<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBotUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bot_users', function(Blueprint $table) {
            $table->id();
            $table->string('group_id', 20);
            $table->string('user_id', 20);
            $table->string('first_name', 500)->nullable();
            $table->string('last_name', 500)->nullable();
            $table->string('user_name', 500)->nullable();
            $table->tinyInteger('question_count')->default(0);
            $table->string('question_message_id', 20)->nullable();
            $table->tinyInteger('answer')->nullable();
            $table->tinyInteger('wrong_count')->default(0);
            $table->boolean('confirmed')->default(false);
            $table->timestamp('joined_at', 0)->nullable();
            $table->timestamp('question_at', 0)->nullable();
            $table->timestamp('confirmed_at', 0)->nullable();
            $table->timestamp('removed_at', 0)->nullable();
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
        Schema::dropIfExists('bot_users');
    }
}
