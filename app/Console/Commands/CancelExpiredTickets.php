<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            Log::info('[tickets:cancel-expired] Không có vé nào cần hủy.');
        } else {
            DB::transaction(function () use ($expiredTickets) {
                foreach ($expiredTickets as $ticket) {
                    $ticket->ticketDetails->each->delete();
                    $ticket->ticketProductDetails->each->delete();
                    $ticket->delete();
                }
            });

            $this->info('Đã hủy ' . $expiredTickets->count() . ' vé và giải phóng ghế.');
            Log::info('[tickets:cancel-expired] Đã hủy ' . $expiredTickets->count() . ' vé và giải phóng ghế.');
        }

        // Xử lý vé hết hạn chiếu từ bảng showtimes
        $expiredShowtimeTickets = Ticket::whereHas('showtime', function ($query) {
            $query->where('end_time', '<', Carbon::now()); // Kiểm tra end_time trong bảng showtimes
        })->get();

        if ($expiredShowtimeTickets->isEmpty()) {
            $this->info('Không có vé nào hết hạn chiếu.');
            Log::info('[tickets:cancel-expired] Không có vé nào hết hạn chiếu.');
        } else {
            foreach ($expiredShowtimeTickets as $ticket) {
                // Cập nhật trạng thái vé thành 'expired'
                $ticket->status = 'expired';
                $ticket->save();
                $this->info('Vé với ID ' . $ticket->id . ' đã hết hạn và đã được chuyển trạng thái sang expired.');
                Log::info('[tickets:cancel-expired] Vé ID ' . $ticket->id . ' chuyển sang expired.');
            }
        }
        // Gửi thông báo đến Slack sau khi xử lý xong
        $message = '[🎟️ FilmGO] Đã chạy lệnh tickets:cancel-expired lúc ' . now();
        Http::post(env('SLACK_WEBHOOK_URL'), [
            'text' => $message,
        ]);
    }
}
