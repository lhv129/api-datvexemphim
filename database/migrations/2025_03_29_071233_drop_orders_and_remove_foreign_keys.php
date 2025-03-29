<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Xóa khóa ngoại trong bảng seat_showtimes (nếu có)
        Schema::table('seat_showtimes', function (Blueprint $table) {
            $table->dropForeign(['order_id']); // Xóa khóa ngoại
            $table->dropColumn('order_id');    // Xóa cột order_id nếu cần
        });

        // Xóa bảng orders
        Schema::dropIfExists('orders');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tạo lại bảng orders
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('product');
            $table->string('combo')->nullable();
            $table->decimal('total', 10, 2);
            $table->boolean('is_checkout')->default(false);
            $table->timestamps();
        });

        // Thêm lại khóa ngoại trong bảng seat_showtimes
        Schema::table('seat_showtimes', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable();
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
        });
    }
};
