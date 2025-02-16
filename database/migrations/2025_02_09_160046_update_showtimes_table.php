<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('showtimes', function (Blueprint $table) {
            // Xóa cột price và status
            $table->dropColumn(['price', 'status']);

            // Thêm cột date mới
            $table->date('date')->after('end_time');
        });
    }

    public function down()
    {
        Schema::table('showtimes', function (Blueprint $table) {
            // $table->decimal('price', 10, 2)->nullable();
            // $table->string('status')->nullable();

            // $table->dropColumn('date');
        });
    }
};
