<?php

use App\Models\BoardMembers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('board_members', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("board_id")->unsigned();
            $table->bigInteger("user_id")->unsigned();
            $table->foreign("board_id")->references("id")->on("boards")->onDelete("cascade");
            $table->string("role")->default("Member");
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
        Schema::dropIfExists('board_members');
    }
};
