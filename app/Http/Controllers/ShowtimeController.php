<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreShowtimeRequest;
use App\Http\Requests\UpdateShowtimeRequest;
use App\Models\Showtime;
use Illuminate\Http\Request;

class ShowtimeController extends Controller
{
    public function index() {
        $showtimes = Showtime::select('id', 'start_time', 'end_time', 'movie_id', 'screen_id')
            ->with(['screen:id,name', 'movie:id,title'])
            ->get();
        return $this->responseCommon(200, "Lấy Danh Sách Thành Công", $showtimes);
    }

    public function store(StoreShowtimeRequest $request) {
        try {
            $showtime = Showtime::create($request->validated());
            return $this->responseCommon(201, "Thêm mới thành công.", $showtime);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", ['error' => $e->getMessage()]);
        }
    }

    public function update(UpdateShowtimeRequest $request, $id) {
        try {
            // Kiểm tra sự tồn tại của bản ghi (bao gồm cả trường hợp đã bị xóa mềm)
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
            // Kiểm tra sự tồn tại của bản ghi (bao gồm cả trường hợp đã bị xóa mềm)
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
            // Kiểm tra sự tồn tại của bản ghi (bao gồm cả trường hợp đã bị xóa mềm)
            $showtime = Showtime::where('id', $id)->whereNull('deleted_at')->first();

            if (!$showtime) {
                return $this->responseCommon(404, "Giờ Chiếu không tồn tại hoặc đã bị xóa.", []);
            }

            $showtime->delete();  // Xóa mềm
            return $this->responseCommon(200, "Xóa Giờ Chiếu thành công.", []);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", ['error' => $e->getMessage()]);
        }
    }
}
