<?php
namespace App\Http\Controllers;

use App\Models\Screen;
use App\Http\Requests\StoreScreenRequest;
use App\Http\Requests\UpdateScreenRequest;
use Illuminate\Http\Request;

class ScreenController extends Controller
{
    public function index() {
        $screens = Screen::select('id', 'name', 'cinema_id')
            ->with(['cinema:id,name'])
            ->get();
        return $this->responseCommon(200, "Lấy Danh Sách Thành Công", $screens);
    }

    public function store(StoreScreenRequest $request) {
        try {
            $screen = Screen::create($request->validated());
            return $this->responseCommon(201, "Thêm mới thành công.", $screen);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", ['error' => $e->getMessage()]);
        }
    }

    public function update(UpdateScreenRequest $request, $id) {
        try {
            // Kiểm tra ID có tồn tại không (tránh lỗi `findOrFail`)
            $screen = Screen::where('id', $id)->whereNull('deleted_at')->first();

            if (!$screen) {
                return $this->responseCommon(404, "Phòng không tồn tại hoặc đã bị xóa.", []);
            }

            $screen->update($request->validated());
            return $this->responseCommon(200, "Cập nhật thành công.", $screen);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", ['error' => $e->getMessage()]);
        }
    }

    public function show($id) {
        try {
            // Kiểm tra sự tồn tại của phòng và không bị xóa mềm
            $screen = Screen::with('cinema:id,name')->where('id', $id)->whereNull('deleted_at')->first();

            if (!$screen) {
                return $this->responseCommon(404, "Phòng không tồn tại hoặc đã bị xóa.", []);
            }

            return $this->responseCommon(200, "Tìm phòng thành công.", $screen);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", ['error' => $e->getMessage()]);
        }
    }

    public function destroy($id) {
        try {
            // Kiểm tra ID có tồn tại không
            $screen = Screen::where('id', $id)->whereNull('deleted_at')->first();

            if (!$screen) {
                return $this->responseCommon(404, "Phòng không tồn tại hoặc đã bị xóa.", []);
            }

            $screen->delete();  // Xóa mềm
            return $this->responseCommon(200, "Xóa phòng thành công.", []);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", ['error' => $e->getMessage()]);
        }
    }
}
