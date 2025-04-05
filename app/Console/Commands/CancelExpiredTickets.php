<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CancelExpiredTickets extends Command
{
    protected $signature = 'tickets:cancel-expired';
    protected $description = 'Hủy các vé có trạng thái pending quá 15 phút và giải phóng ghế, đồng thời chuyển trạng thái vé hết hạn chiếu sang expired';

    public function handle()
    {
        // Hủy vé pending quá 15 phút
        $expiredTime = Carbon::now()->subMinutes(15);

        $expiredTickets = Ticket::where('status', 'pending')
            ->where('created_at', '<', $expiredTime)
            ->get();

        if ($expiredTickets->isEmpty()) {
            $this->info('Không có vé nào cần hủy.');
        } else {
            DB::transaction(function () use ($expiredTickets) {
                foreach ($expiredTickets as $ticket) {
                    // Xóa các chi tiết vé
                    $ticket->ticketDetails()->delete();
                    $ticket->ticketProductDetails()->delete();

                    // Xóa vé
                    $ticket->delete();
                }
            });

            $this->info('Đã hủy ' . $expiredTickets->count() . ' vé và giải phóng ghế.');
        }

        // Xử lý vé hết hạn chiếu từ bảng showtimes
        $expiredShowtimeTickets = Ticket::whereHas('showtime', function ($query) {
            $query->where('end_time', '<', Carbon::now()); // Kiểm tra end_time trong bảng showtimes
        })->get();

        if ($expiredShowtimeTickets->isEmpty()) {
            $this->info('Không có vé nào hết hạn chiếu.');
        } else {
            foreach ($expiredShowtimeTickets as $ticket) {
                // Cập nhật trạng thái vé thành 'expired'
                $ticket->status = 'expired';
                $ticket->save();
                $this->info('Vé với ID ' . $ticket->id . ' đã hết hạn và đã được chuyển trạng thái sang expired.');
            }
        }
    }
}
