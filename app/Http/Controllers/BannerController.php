<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBannerRequest;
use App\Http\Requests\UpdateBannerRequest;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::select('id', 'name', 'image', 'fileName')->get();
        return $this->responseCommon(200, 'Lấy danh sách hình ảnh quảng cáo thành công', $banners);
    }

    public function store(StoreBannerRequest $request)
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            // Tạo ngẫu nhiên tên ảnh 12 kí tự
            $imageName = Str::random(12) . "." . $file->getClientOriginalExtension();
            // Đường dẫn ảnh
            $imageDirectory = 'images/banners/';

            $file->move($imageDirectory, $imageName);
            $path_image   = 'http://127.0.0.1:8000/' . ($imageDirectory . $imageName);

            $banner = Banner::create([
                'name' => $request->name,
                'image' => $path_image,
                'fileName' => $imageName,
            ]);

            return $this->responseCommon(201, "Thêm ảnh quảng cáo thành công.", $banner);
        }
    }

    public function update(UpdateBannerRequest $request, $id)
    {
        try {
            $banner = Banner::findOrFail($id);
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                // Đường dẫn ảnh
                $imageDirectory = 'images/banners/';
                // Xóa ảnh nếu ảnh cũ
                File::delete($imageDirectory . $banner->fileName);
                // Tạo ngẫu nhiên tên ảnh 12 kí tự
                $imageName = Str::random(12) . "." . $file->getClientOriginalExtension();

                $file->move($imageDirectory, $imageName);

                $path_image   = 'http://127.0.0.1:8000/' . ($imageDirectory . $imageName);
            } else {
                $path_image = $banner->image;
            }
            $banner->update([
                'name' => $request->name,
                'image' => $path_image,
                'fileName' => $imageName ?? $banner->fileName, // Dùng toán tử 3 ngôi, nếu không thêm ảnh mới thì giữ lại tên ảnh cũ
            ]);
            return $this->responseCommon(200, "Cập nhật ảnh quảng cáo thành công.", $banner);
        } catch (\Exception $e) {
            return $this->responseCommon(404, "Ảnh quảng cáo này không tồn tại hoặc đã bị xóa.", []);
        }
    }

    public function destroy($id)
    {
        try {
            $banner = Banner::findOrFail($id);

            // Đường dẫn ảnh
            $imageDirectory = 'images/banners/';
            // Xóa sản phẩm thì xóa luôn ảnh sản phẩm đó
            File::delete($imageDirectory . $banner->fileName);

            $banner->delete();

            return $this->responseCommon(200, "Xóa ảnh quảng cáo thành công.", []);
        } catch (\Exception $e) {
            return $this->responseCommon(404, "Ảnh quảng cáo này không tồn tại hoặc đã bị xóa.", []);
        }
    }
}
