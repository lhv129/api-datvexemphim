<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreShowtimeRequest;
use App\Http\Requests\UpdateShowtimeRequest;
use App\Models\Seat;
use App\Models\SeatShowtime;
use App\Models\Showtime;
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

            // Kiểm tra suất chiếu trùng lặp
            $exists = Showtime::where('screen_id', $validatedData['screen_id'])
                ->whereDate('start_time', $validatedData['date'])
                ->where(function ($query) use ($validatedData) {
                    $query->whereBetween('start_time', [$validatedData['start_time'], $validatedData['end_time']])
                          ->orWhereBetween('end_time', [$validatedData['start_time'], $validatedData['end_time']])
                          ->orWhere(function ($query) use ($validatedData) {
                              $query->where('start_time', '<=', $validatedData['start_time'])
                                    ->where('end_time', '>=', $validatedData['end_time']);
                          });
                })
                ->exists();

            if ($exists) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Thời gian bắt đầu bị trùng với suất chiếu khác.',
                    'data' => $validatedData
                ], 400);
            }

            // Tạo showtime mới
            $showtime = Showtime::create($validatedData);

            // Lấy danh sách ghế thuộc screen_id
            $seats = Seat::where('screen_id', $validatedData['screen_id'])->get();

            // Lưu vào bảng seat_showtimes
            foreach ($seats as $seat) {
                SeatShowtime::create([
                    'seat_id' => $seat->id,
                    'showtime_id' => $showtime->id,
                    'status' => 'available' // Trạng thái ghế ban đầu
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

            // Kiểm tra suất chiếu trùng lặp
            $exists = Showtime::where('screen_id', $validatedData['screen_id'])
                ->whereDate('start_time', $validatedData['date'])
                ->where(function ($query) use ($validatedData, $id) {
                    $query->where('start_time', '<=', $validatedData['start_time'])
                        ->where('end_time', '>=', $validatedData['start_time'])
                        ->where('id', '!=', $id); // Loại trừ suất chiếu hiện tại
                })
                ->exists();

            if ($exists) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Thời gian bắt đầu bị trùng với suất chiếu khác.',
                    'data' => $validatedData
                ], 400);
            }

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

            SeatShowtime::where('showtime_id', $id)->delete();
            $showtime->delete();

            return $this->responseCommon(200, "Xóa Giờ Chiếu thành công.", []);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", ['error' => $e->getMessage()]);
        }
    }
}
