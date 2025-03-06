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
        Schema::table('actor_movies', function (Blueprint $table) {
            // Xóa khóa ngoại hiện tại (nếu có)
            $table->dropForeign(['actor_id']);

            // Thêm khóa ngoại mới liên kết với bảng actors
            $table->foreign('actor_id')
                ->references('id')
                ->on('actors')
                ->onDelete('cascade'); // Tùy chọn: Xóa các bản ghi actor_movies liên quan khi diễn viên bị xóa
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('actor_movies', function (Blueprint $table) {
            //
        });
    }
};
