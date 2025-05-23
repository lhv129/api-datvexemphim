<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreShowtimeRequest;
use App\Http\Requests\UpdateShowtimeRequest;
use App\Models\Seat;
use App\Models\SeatShowtime;
use App\Models\Showtime;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShowtimeController extends Controller
{
    public function index() {
        $showtimes = Showtime::select('id', 'start_time', 'end_time', 'date', 'movie_id', 'screen_id')
            ->with([
                'screen:id,name,cinema_id',
                'screen.cinema:id,name,province_id',
                'screen.cinema.province:id,name',
                'movie:id,title',
            ])
            ->get()
            ->map(function($showtime) {
                $showtime->start_time = Carbon::parse($showtime->start_time)->format('H:i');
                $showtime->end_time = Carbon::parse($showtime->end_time)->format('H:i');
                return $showtime;
            })
            ;

        return $this->responseCommon(200, "Lấy Danh Sách Thành Công", $showtimes);
    }


    public function getAllByDate(Request $request) {
        $showtimes = Showtime::select('id', 'start_time', 'end_time','date', 'movie_id', 'screen_id')
           ->with([
                'screen:id,name,cinema_id',
                'screen.cinema:id,name,province_id',
                'screen.cinema.province:id,name',
                'movie:id,title'
            ])
            ->where('date' , $request->date)
            ->get();
        return $this->responseCommon(200, "Lấy Danh Sách Thành Công", $showtimes);
    }

    public function getAllByMovieTitle(Request $request) {
        $showtimes = Showtime::select('id', 'start_time', 'end_time', 'date', 'movie_id', 'screen_id')
            ->with([
                'screen:id,name,cinema_id',
                'screen.cinema:id,name,province_id',
                'screen.cinema.province:id,name',
                'movie:id,title'
            ])
            ->whereHas('movie', function ($query) use ($request) {
                $query->where('title', 'LIKE', "%{$request->title}%");
            })
            ->get();

        return $this->responseCommon(200, "Lấy Danh Sách Thành Công", $showtimes);
    }


    public function store(StoreShowtimeRequest $request) {
        try {
            $validatedData = $request->validated();

            $showtime = Showtime::create($validatedData);

            // Tạo ghế vào seat_showtimes
            $seats = Seat::where('screen_id', $validatedData['screen_id'])->get();
            foreach ($seats as $seat) {
                SeatShowtime::create([
                    'seat_id' => $seat->id,
                    'showtime_id' => $showtime->id,
                    'status' => 'available',
                ]);
            }

            return response()->json([
                'status' => 201,
                'message' => "Thêm mới thành công.",
                'data' => $showtime
            ], 201);
        } catch (\Exception $e) {
            Log::error('Lỗi xử lý:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'message' => "Lỗi xử lý.",
                'error' => $e->getMessage()
            ], 500);
        }
    }




    public function update(UpdateShowtimeRequest $request, $id) {
        try {
            $showtime = Showtime::where('id', $id)->whereNull('deleted_at')->first();

            if (!$showtime) {
                return $this->responseCommon(404, "Giờ Chiếu không tồn tại hoặc đã bị xóa.", []);
            }

            $validatedData = $request->validated();

            $showtime->update($validatedData);

            return $this->responseCommon(200, "Cập nhật thành công.", $showtime);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", ['error' => $e->getMessage()]);
        }
    }


    public function show($id) {
        try {
            $showtime = Showtime::with([
                'screen:id,name,cinema_id',
                'screen.cinema:id,name,province_id',
                'screen.cinema.province:id,name',
                'movie:id,title'
            ])
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

            if (!$showtime) {
                return $this->responseCommon(404, "Giờ Chiếu không tồn tại hoặc đã bị xóa.", []);
            }

            return $this->responseCommon(200, "Tìm Giờ Chiếu thành công.", $showtime);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", ['error' => $e->getMessage()]);
        }
    }

    public function destroy($id) {
        try {
            $showtime = Showtime::where('id', $id)->whereNull('deleted_at')->first();

            if (!$showtime) {
                return $this->responseCommon(404, "Giờ Chiếu không tồn tại hoặc đã bị xóa.", []);
            }

            //Ktr Có user nào đặt vé chưa
            $hasBooked = Ticket::where('showtime_id',$id)
            ->where('status',['paid','used'])
            ->exists();
            if($hasBooked) {
                return $this->responseCommon(400,'Không thể xóa xuất chiếu đã có người đặt hoặc sử dụng vé.',[]);
            }

            SeatShowtime::where('showtime_id', $id)->delete();
            $showtime->delete();

            return $this->responseCommon(200, "Xóa Giờ Chiếu thành công.", []);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", ['error' => $e->getMessage()]);
        }
    }
}
