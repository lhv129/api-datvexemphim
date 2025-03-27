<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
     /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('showtimes', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time']); // Xóa cột cũ
        });

        Schema::table('showtimes', function (Blueprint $table) {
            $table->time('start_time')->after('id'); // Thêm lại cột mới kiểu time
            $table->time('end_time')->after('start_time');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('showtimes', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time']); // Xóa cột time

            $table->date('start_time')->after('id'); // Khôi phục cột date
            $table->date('end_time')->after('start_time');
        });
    }
};
