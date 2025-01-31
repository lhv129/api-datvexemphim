<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class BannerController extends Controller
{
    public function index(){
        $banners = Banner::select('id','name','image','fileName')->get();
        return $this->responseCommon(200,'Lấy danh sách hình ảnh quảng cáo thành công',$banners);
    }

    public function store(Request $request){
        $rules = $this->validateCreateBanner();
        $alert = $this->alertCreateBanner();
        $validator = Validator::make($request->all(),$rules,$alert);
        if($validator->fails()){
            return $this->responseValidate(422, 'Dữ liệu không hợp lệ', $validator->errors());
        }else{
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
    }

    public function update(Request $request,$id){
        try {
            $banner = Banner::findOrFail($id);

            $rules = $this->validateUpdateBanner();
            $alert = $this->alertUpdateBanner();
            $validator = Validator::make($request->all(), $rules, $alert);

            if ($validator->fails()) {
                return $this->responseValidate(422, 'Dữ liệu không hợp lệ', $validator->errors());
            } else {
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
            }
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

    //Validate

    public function validateCreateBanner()
    {
        return [
            'name' => 'required|min:5|max:255',
            'image' => 'required|mimes:jpeg,jpg,png',
        ];
    }

    public function alertCreateBanner()
    {
        return [
            'required' => 'Không được để trống thông tin :attribute.',
            'name.min' => 'Tên ảnh quảng cáo phải ít nhất 5 kí tự.',
            'name.max' => 'Tên ảnh quảng cáo quá dài.',
            'mimes' => 'Bạn chỉ được nhập file ảnh có đuôi jpeg,jpg,png',
        ];
    }

    public function validateUpdateBanner()
    {
        return [
            'name' => 'required|min:5|max:255',
            'image' => 'mimes:jpeg,jpg,png',
        ];
    }

    public function alertUpdateBanner()
    {
        return [
            'required' => 'Không được để trống thông tin :attribute.',
            'name.min' => 'Tên ảnh quảng cáo phải ít nhất 5 kí tự.',
            'name.max' => 'Tên ảnh quảng cáo quá dài.',
            'mimes' => 'Bạn chỉ được nhập file ảnh có đuôi jpeg,jpg,png',
        ];
    }
}
