<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreShowtimeRequest;
use App\Http\Requests\UpdateShowtimeRequest;
use App\Models\Showtime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShowtimeController extends Controller
{
    public function index() {
        $showtimes = Showtime::select('id', 'start_time', 'end_time', 'movie_id', 'screen_id','date')
            ->with(['screen:id,name', 'movie:id,title'])
            ->get();
        return $this->responseCommon(200, "Lấy Danh Sách Thành Công", $showtimes);
    }

    public function store(StoreShowtimeRequest $request) {
        try {
            $validatedData = $request->validated();
            $showtimes = [];

            foreach ($validatedData['showtimes'] as $showtimeData) {
                // Kiểm tra trùng start_time với suất chiếu đã có trong cùng screen_id
                $exists = Showtime::where('screen_id', $showtimeData['screen_id'])
                    ->where('start_time', '<=', $showtimeData['start_time'])
                    ->where('end_time', '>=', $showtimeData['start_time'])
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'status' => 400,
                        'message' => 'Thời gian bắt đầu bị trùng với suất chiếu khác.',
                        'data' => $showtimeData
                    ], 400);
                }

                $showtimes[] = Showtime::create($showtimeData);
            }

            return $this->responseCommon(201, "Thêm mới thành công.", $showtimes);
        } catch (\Exception $e) {
            Log::error('Lỗi xử lý:', ['error' => $e->getMessage()]);
            return $this->responseError(500, "Lỗi xử lý.", ['error' => $e->getMessage()]);
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
