<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProvinceRequest;
use App\Http\Requests\UpdateProvinceRequest;
use App\Models\Province;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{
    public function index() {
        $provinces = Province::select('id', 'name')->get();
        return $this->responseCommon(200, 'Lấy danh sách thành công!', $provinces);
    }

    public function store(StoreProvinceRequest $request) {
        try {
            $province = Province::create($request->validated());
            return $this->responseCommon(201, "Thêm thành công.", $province);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", ['error' => $e->getMessage()]);
        }
    }

    public function update(UpdateProvinceRequest $request, $id) {
        try {
            $province = Province::where('id', $id)->whereNull('deleted_at')->first();

            if (!$province) {
                return $this->responseCommon(404, "Tỉnh không tồn tại hoặc đã bị xóa.",[]);
            }

            $province->update($request->validated());
            return $this->responseCommon(200, "Cập nhật thành công.", $province);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", ['error' => $e->getMessage()]);
        }
    }

    public function show($id){
        try {
            $province = Province::findOrFail($id);
            return $this->responseCommon(200, "Tìm tỉnh thành thành công.", $province);
        } catch (\Exception $e) {
            return $this->responseCommon(404, "Tỉnh thành này không tồn tại hoặc đã bị xóa.",[]);
        }
    }

    public function destroy($id) {
        try {
            $province = Province::where('id', $id)->whereNull('deleted_at')->first();

            if (!$province) {
                return $this->responseCommon(404, "Tỉnh không tồn tại hoặc đã bị xóa.",[]);
            }

            $province->delete();
            return $this->responseCommon(200, "Xóa thành công.",[]);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", ['error' => $e->getMessage()]);
        }
    }
}
