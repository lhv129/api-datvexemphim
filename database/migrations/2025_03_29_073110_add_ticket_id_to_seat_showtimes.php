<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('seat_showtimes', function (Blueprint $table) {
            $table->unsignedBigInteger('ticket_id')->nullable()->after('status');

            // Tạo khóa ngoại
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('set null');
        });
    }

    public function down() {
        Schema::table('seat_showtimes', function (Blueprint $table) {
            // Xóa khóa ngoại trước khi xóa cột
            $table->dropForeign(['ticket_id']);
            $table->dropColumn('ticket_id');
        });
    }
};

