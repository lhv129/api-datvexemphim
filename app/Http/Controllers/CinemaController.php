<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCinemaRequest;
use App\Http\Requests\UpdateCinemaRequest;
use App\Models\Cinema;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CinemaController extends Controller
{
    public function index() {
        $cinemas = Cinema::select('id', 'code', 'name', 'address', 'image', 'contact', 'province_id')
            ->with(['province:id,name'])
            ->get();
        return $this->responseCommon(200, "Lấy danh sách thành công", $cinemas);
    }

    public function getAllByProvinceId(Request $request) {
        $province = Province::find($request->province_id);
        if(!$province) {
            return $this->responseCommon(404,"Tỉnh thành không tồn tại",[]);
        }
        $cinemas = Cinema::select('id', 'code', 'name', 'address', 'image', 'contact', 'province_id')
            ->with(['province:id,name'])
            ->where('province_id',$request->province_id)
            ->get();
        return $this->responseCommon(200, "Lấy danh sách thành công", $cinemas);
    }

    public function store(StoreCinemaRequest $request) {
        try {
            $data = $request->validated();

            // if ($request->hasFile('image')) {
            //     $file = $request->file('image');
            //     $imageName = Str::random(12) . "." . $file->getClientOriginalExtension();
            //     $imageDirectory = 'storage/images/cinemas/';

            //     $file->storeAs('public/images/cinemas', $imageName);

            //     $data['image'] = 'http://filmgo.io.vn/' . $imageDirectory . $imageName;
            // }
            $data['image'] = Str::random(12);
            $cinema = Cinema::create($data);

            return $this->responseCommon(201, "Thêm mới thành công.", $cinema);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function update(UpdateCinemaRequest $request, $id) {
        try {
            $cinema = Cinema::where('id', $id)->whereNull('deleted_at')->first();

            if (!$cinema) {
                return $this->responseCommon(404, "Rạp không tồn tại hoặc đã bị xóa.",[]);
            }

            $data = $request->validated();

            // if ($request->hasFile('image')) {
            //     if (!empty($cinema->image)) {
            //         $oldImagePath = str_replace(url('/storage/'), 'public/', $cinema->image);
            //         Storage::delete($oldImagePath);
            //     }

            //     $file = $request->file('image');
            //     $imageName = Str::random(12) . "." . $file->getClientOriginalExtension();
            //     $imagePath = $file->storeAs('public/images/cinemas', $imageName);
            //     $data['image'] = url(Storage::url($imagePath));
            // }
            $data['image'] = Str::random(12);
            $cinema->update($data);

            return $this->responseCommon(200, "Cập nhật thành công.", $cinema);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function show($id) {
        try {
            $cinema = Cinema::with('province:id,name')->where('id', $id)->whereNull('deleted_at')->first();

            if (!$cinema) {
                return $this->responseError(404, "Rạp không tồn tại hoặc đã bị xóa.",[]);
            }

            return $this->responseCommon(200, "Tìm rạp thành công.", $cinema);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function destroy($id) {
        try {
            $cinema = Cinema::where('id', $id)->whereNull('deleted_at')->first();

            if (!$cinema) {
                return $this->responseCommon(404, "Rạp không tồn tại hoặc đã bị xóa.",[]);
            }

            // Xóa ảnh trước khi xóa rạp
            if (!empty($cinema->image)) {
                $oldImagePath = str_replace(url('/storage/'), 'public/', $cinema->image);
                Storage::delete($oldImagePath);
            }

            $cinema->delete();

            return $this->responseCommon(200, "Xóa rạp thành công.",[]);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", [
                'error' => $e->getMessage()
            ]);
        }
    }
}
