<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Validator;

class CinemaController extends Controller
{
    public function index() {
        $cinemas = Cinema::select('id','code', 'name', 'address', 'image', 'contact','province_id')
        ->with(['province:id,name'])
        ->get();
        return $this->responseCommon(200,"Lấy Danh Sách Thành Công",$cinemas);

    }

    public function store(Request $request) {
        // Kiểm tra và validate request
        $rules = $this->validateCreateCinemas();
        $alert = $this->alertCreateCinemas();
        $validator = Validator::make($request->all(), $rules, $alert);

        if ($validator->fails()) {
            return $this->responseError(422, 'Dữ liệu không hợp lệ.', $validator->errors());
        }

        if (!$request->hasFile('image')) {
            return $this->responseError(422, "Vui lòng chọn ảnh.", $validator->errors());
        }

        try {
            // Kiểm tra xem rạp có bị xóa mềm không
            $cinema = Cinema::withTrashed()->where('code', $request->code)->first();

            if ($cinema) {
                // Nếu đã bị xóa mềm, khôi phục lại
                $cinema->restore();

                // Xóa ảnh cũ nếu có (tùy vào logic của bạn)
                if ($request->hasFile('image')) {
                    // Lưu ảnh mới
                    $file = $request->file('image');
                    $imageName = Str::random(12) . "." . $file->getClientOriginalExtension();
                    $imagePath = $file->storeAs('public/images/cinemas', $imageName);

                    // Xóa ảnh cũ nếu có
                    if ($cinema->image) {
                        Storage::delete(str_replace(url('/storage/'), 'public', $cinema->image));
                    }

                    // Cập nhật đường dẫn ảnh mới
                    $cinema->image = url(Storage::url($imagePath));
                }

                // Cập nhật thông tin mới
                $cinema->update([
                    'name' => $request->name,
                    'address' => $request->address,
                    'image' => $cinema->image,
                    'contact' => $request->contact,
                    'province_id' => $request->province_id
                ]);

                return $this->responseCommon(200, "Rạp đã được khôi phục và cập nhật.", $cinema);
            }

            // Nếu không có rạp bị xóa mềm, tạo mới
            $file = $request->file('image');
            $imageName = Str::random(12) . "." . $file->getClientOriginalExtension();
            $imagePath = $file->storeAs('public/images/cinemas', $imageName);
            $path_image = url(Storage::url($imagePath));

            $cinema = Cinema::create([
                'code' => $request->code,
                'name' => $request->name,
                'address' => $request->address,
                'image' => $path_image,
                'contact' => $request->contact,
                'province_id' => $request->province_id
            ]);

            return $this->responseCommon(201, "Thêm mới thành công.", $cinema);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", [
                'error' => $e->getMessage()
            ]);
        }
    }


    public function update(Request $request,$id) {
        // dd($request->all());
        $cinema = Cinema::findOrFail($id);

        $rules = $this->validateUpdateCinemas($id);
        $alert = $this->alertUpdateCinemas();
        $validator = Validator::make($request->all(),$rules,$alert);

        if($validator->fails()) {
            return $this->responseError(422,'Dữ Liệu Không hợp lệ.',$validator->errors());
        }

        try {
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                // Tạo tên ảnh ngẫu nhiên
                $imageName = Str::random(12) . "." . $file->getClientOriginalExtension();
                // Lưu ảnh mới
                $imagePath = $file->storeAs('public/images/cinemas', $imageName);

                // Xóa ảnh cũ nếu có
                if ($cinema->image) {
                    Storage::delete(str_replace(url('/storage/'), 'public', $cinema->image));
                }

                // Cập nhật đường dẫn ảnh mới
                $cinema->image = url(Storage::url($imagePath));
            }
            $cinema->update([
                'code' => $request->code,
                'name' => $request->name,
                'address' => $request->address,
                'image' => $cinema->image, // Giữ ảnh cũ nếu không upload mới
                'contact' => $request->contact,
                'province_id' => $request->province_id
            ]);
            return $this->responseCommon(200,"Cập nhật thành công.",$cinema);
        } catch (\Exception $e) {
            return $this->responseError(500,"Lỗi xử lý",[
                'error' => $e->getMessage()
            ]);
        }
    }

    public function show($id) {
        try {
            $cinema = Cinema::with('province:id,name')->findOrFail($id);

            return $this->responseCommon(200,"Tìm Rạp thành công.",$cinema);
        } catch (\Exception $e) {
            return $this->responseError(404,"Rạp Không tồn tại",[]);
        }
    }

    public function destroy($id) {
        try {
            $cinema = Cinema::with('province:id,name')->findOrFail($id);
            $cinema->delete();
            return $this->responseCommon(200,"Xóa Rạp thành công.",$cinema);

        } catch (\Exception $e) {
            return $this->responseError(404,"Rạp Không tồn tại",[]);
        }
    }




    //Validate

    public function validateCreateCinemas()
    {
        return [

            'code' =>'required|unique:cinemas,code,NULL,id,deleted_at,NULL',
            'name' =>'required|min:5|max:255|unique:cinemas,name,NULL,id,deleted_at,NULL',
            'address' =>'required' ,
            'image' =>'required|mimes:jpeg,jpg,png' ,
            'contact' =>'required' ,

        ];
    }

    public function alertCreateCinemas()
    {
        return [
            'required' => 'Không được để trống.',
            'code.unique' => 'Code không được trùng.',
            'name.unique' => 'Tên không được trùng.',
            'name.min' => 'Tên phải ít nhất 5 kí tự.',
            'name.max' => 'Tên quá dài.',
            'mimes' => 'Bạn chỉ được nhập file ảnh có đuôi jpeg,jpg,png',
        ];
    }

    public function validateUpdateCinemas($id)
{
    return [
        'code' => 'required|unique:cinemas,code,' . $id,
        'name' => 'required|min:5|max:255|unique:cinemas,name,' . $id,
        'address' => 'required',
        'image' => 'nullable|mimes:jpeg,jpg,png',
        'contact' => 'required',
    ];
}


    public function alertUpdateCinemas()
    {
        return [
           'required' => 'Không được để trống.',
            'code.unique' => 'Code không được trùng.',
            'name.unique' => 'Tên không được trùng.',
            'name.min' => 'Tên phải ít nhất 5 kí tự.',
            'name.max' => 'Tên quá dài.',
            'mimes' => 'Bạn chỉ được nhập file ảnh có đuôi jpeg,jpg,png',
        ];
    }

}
