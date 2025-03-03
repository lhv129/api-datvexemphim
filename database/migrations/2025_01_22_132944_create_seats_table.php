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
        Schema::create('seats', function (Blueprint $table) {
            $table->id();

            $table->string('row', 5);
            $table->unsignedSmallInteger('number');
            $table->string('type', 50)->default('regular');
            $table->unsignedDecimal('price', 8, 2);
            $table->enum('status', ['available', 'booked', 'reserved'])->default('available');
            $table->unsignedBigInteger('screen_id');
            $table->foreign('screen_id')->references('id')->on('screens')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
