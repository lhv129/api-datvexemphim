<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreSeatRequest;
use App\Http\Requests\UpdateSeatRequest;
use App\Models\Seat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SeatController extends Controller
{
    public function index() {
        $seats = Seat::select('id', 'row','number','type','price','status','screen_id')
            ->with(['screen:id,name'])
            ->get();
        return $this->responseCommon(200, "Lấy Danh Sách Thành Công", $seats);
    }

    public function store(StoreSeatRequest $request) {
        try {
            $seat = Seat::create($request->validated());

            return $this->responseCommon(201, "Thêm mới thành công.", $seat);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function update(UpdateSeatRequest $request, $id) {
        try {
            // Kiểm tra ID có tồn tại không (tránh lỗi `findOrFail`)
            $seat = Seat::where('id', $id)->whereNull('deleted_at')->first();

            if (!$seat) {
                return $this->responseCommon(404, "Ghế không tồn tại hoặc đã bị xóa.", []);
            }

            $seat->update($request->validated());
            return $this->responseCommon(200, "Cập nhật thành công.", $seat);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý", [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function show($id) {
        try {
            // Kiểm tra sự tồn tại của ghế và không bị xóa mềm
            $seat = Seat::with('screen:id,name')->where('id', $id)->whereNull('deleted_at')->first();

            if (!$seat) {
                return $this->responseCommon(404, "Ghế không tồn tại hoặc đã bị xóa.", []);
            }

            return $this->responseCommon(200, "Tìm Ghế thành công.", $seat);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý", [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function destroy($id) {
        try {
            // Kiểm tra sự tồn tại của ghế và không bị xóa mềm
            $seat = Seat::where('id', $id)->whereNull('deleted_at')->first();

            if (!$seat) {
                return $this->responseCommon(404, "Ghế không tồn tại hoặc đã bị xóa.", []);
            }

            $seat->delete();  // Xóa mềm
            return $this->responseCommon(200, "Xóa Ghế thành công.", []);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý", [
                'error' => $e->getMessage()
            ]);
        }
    }
}
