<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreShowtimeRequest;
use App\Http\Requests\UpdateShowtimeRequest;
use App\Models\Showtime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShowtimeController extends Controller
{
    public function index() {
        $showtimes = Showtime::select('id', 'start_time', 'end_time','date', 'movie_id', 'screen_id')
            ->with(['screen:id,name', 'movie:id,title'])
            ->get();
        return $this->responseCommon(200, "Lấy Danh Sách Thành Công", $showtimes);
    }

    public function getAllByDate(Request $request) {
        $showtimes = Showtime::select('id', 'start_time', 'end_time','date', 'movie_id', 'screen_id')
            ->with(['screen:id,name', 'movie:id,title'])
            ->where('date' , $request->date)
            ->get();
        return $this->responseCommon(200, "Lấy Danh Sách Thành Công", $showtimes);
    }

    public function store(StoreShowtimeRequest $request) {
        try {
            $validatedData = $request->validated();

            // Sử dụng Carbon để định dạng start_time và end_time
            $validatedData['start_time'] = Carbon::createFromFormat('Y-m-d H:i', $validatedData['date'] . ' ' . $validatedData['start_time'])
                ->format('Y-m-d H:i:s');

            $validatedData['end_time'] = Carbon::createFromFormat('Y-m-d H:i', $validatedData['date'] . ' ' . $validatedData['end_time'])
                ->format('Y-m-d H:i:s');

            // Kiểm tra suất chiếu trùng lặp
            $exists = Showtime::where('screen_id', $validatedData['screen_id'])
                ->whereDate('start_time', $validatedData['date']) // Thêm điều kiện ngày
                ->where(function ($query) use ($validatedData) {
                    $query->where('start_time', '<=', $validatedData['start_time'])
                        ->where('end_time', '>=', $validatedData['start_time']);
                })
                ->exists();


            if ($exists) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Thời gian bắt đầu bị trùng với suất chiếu khác.',
                    'data' => $validatedData
                ], 400);
            }

            $showtime = Showtime::create($validatedData);

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

            $showtime->update($request->validated());
            return $this->responseCommon(200, "Cập nhật thành công.", $showtime);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", ['error' => $e->getMessage()]);
        }
    }

    public function show($id) {
        try {
            $showtime = Showtime::with(['screen:id,name', 'movie:id,title'])
                ->where('id', $id)->whereNull('deleted_at')->first();

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

            $showtime->delete();
            return $this->responseCommon(200, "Xóa Giờ Chiếu thành công.", []);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", ['error' => $e->getMessage()]);
        }
    }
}
