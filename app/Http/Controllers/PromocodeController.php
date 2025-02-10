<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePromocodeRequest;
use App\Http\Requests\UpdatePromocodeRequest;
use App\Models\Promo_code;
use Illuminate\Http\Request;

class PromocodeController extends Controller
{
    public function index() {
        $promo_codes = Promo_code::select('id', 'code', 'description', 'discount_amount', 'start_date', 'end_date', 'status')
            ->get();
        return $this->responseCommon(200, "Lấy danh sách thành công", $promo_codes);
    }

    public function store(StorePromocodeRequest $request) {
        try {
            $promo_code = Promo_code::create($request->validated());

            return $this->responseCommon(201, "Thêm mới thành công.", $promo_code);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function update(UpdatePromocodeRequest $request, $id) {
        try {
            // Kiểm tra mã giảm giá có tồn tại không
            $promo_code = Promo_code::where('id', $id)->whereNull('deleted_at')->first();

            if (!$promo_code) {
                return $this->responseCommon(404, "Mã giảm giá không tồn tại hoặc đã bị xóa.", []);
            }
            $promo_code->update($request->validated());

            return $this->responseCommon(200, "Cập nhật thành công.", $promo_code);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function show($id) {
        try {
            // Tìm mã giảm giá
            $promo_code = Promo_code::where('id', $id)->whereNull('deleted_at')->first();

            if (!$promo_code) {
                return $this->responseCommon(404, "Mã giảm giá không tồn tại hoặc đã bị xóa.", []);
            }

            return $this->responseCommon(200, "Tìm mã giảm giá thành công.", $promo_code);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function destroy($id) {
        try {
            // Kiểm tra mã giảm giá có tồn tại không
            $promo_code = Promo_code::where('id', $id)->whereNull('deleted_at')->first();

            if (!$promo_code) {
                return $this->responseCommon(404, "Mã giảm giá không tồn tại hoặc đã bị xóa.", []);
            }

            // Xóa mã giảm giá
            $promo_code->delete();

            return $this->responseCommon(200, "Xóa mã giảm giá thành công.", []);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", [
                'error' => $e->getMessage()
            ]);
        }
    }
}
