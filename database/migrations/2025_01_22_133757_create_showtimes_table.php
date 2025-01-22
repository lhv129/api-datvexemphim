<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('showtimes', function (Blueprint $table) {
            $table->id();

            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->unsignedDecimal('price', 8, 2);
            $table->enum('status', ['available', 'cancelled', 'completed'])->default('available');
            $table->unsignedBigInteger('movie_id');
            $table->unsignedBigInteger('screen_id');
            $table->foreign('screen_id')->references('id')->on('screens')->onDelete('cascade');
            $table->foreign('movie_id')->references('id')->on('movies')->onDelete('cascade');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('showtimes');
    }
};
