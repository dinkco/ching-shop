<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersTable extends Migration
{
    const TABLE_NAME = 'orders';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            self::TABLE_NAME,
            function (Blueprint $table) {
                $table->increments('id');

                // An order belongs to a user.
                $table->integer('user_id')
                    ->unsigned()
                    ->nullable()
                    ->index();
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users');

                $table->timestamps();
                $table->softDeletes();
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(self::TABLE_NAME);
    }
}
