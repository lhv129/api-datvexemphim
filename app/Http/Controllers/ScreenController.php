<?php

namespace App\Http\Controllers;

use App\Models\Screen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ScreenController extends Controller
{
    public function index() {
        $screens = Screen::select('id', 'name','cinema_id')
        ->with(['cinema:id,name'])
        ->get();
        return $this->responseCommon(200,"Lấy Danh Sách Thành Công",$screens);

    }

    public function store(Request $request) {
        // Kiểm tra và validate request
        $rules = $this->validateCreateScreens();
        $alert = $this->alertCreateScreens();
        $validator = Validator::make($request->all(), $rules, $alert);

        if ($validator->fails()) {
            return $this->responseError(422, 'Dữ liệu không hợp lệ.', $validator->errors());
        }

        try {
            // Kiểm tra xem Phòng có bị xóa mềm không
            $screen = Screen::withTrashed()->where('name', $request->name)->first();

            if ($screen) {
                // Nếu đã bị xóa mềm, khôi phục lại
                $screen->restore();
                $screen->update([
                    'name' => $request->name,
                    'cinema_id' => $request->cinema_id
                ]);

                return $this->responseCommon(200, "Phòng đã được khôi phục và cập nhật.", $screen);
            }

            $screen = Screen::create([
                'name' => $request->name,
                'cinema_id' => $request->cinema_id
            ]);

            return $this->responseCommon(201, "Thêm mới thành công.", $screen);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", [
                'error' => $e->getMessage()
            ]);
        }
    }


    public function update(Request $request,$id) {
        // dd($request->all());
        $screen = Screen::findOrFail($id);

        $rules = $this->validateUpdateScreens($id);
        $alert = $this->alertUpdateScreens();
        $validator = Validator::make($request->all(),$rules,$alert);

        if($validator->fails()) {
            return $this->responseError(422,'Dữ Liệu Không hợp lệ.',$validator->errors());
        }

        try {
            $screen->update([
                'name' => $request->name,
                'cinema_id' => $request->cinema_id
            ]);
            return $this->responseCommon(200,"Cập nhật thành công.",$screen);
        } catch (\Exception $e) {
            return $this->responseError(500,"Lỗi xử lý",[
                'error' => $e->getMessage()
            ]);
        }
    }

    public function show($id) {
        try {
            $screen = Screen::with('cinema:id,name')->findOrFail($id);

            return $this->responseCommon(200,"Tìm Phòng thành công.",$screen);
        } catch (\Exception $e) {
            return $this->responseError(404,"Phòng Không tồn tại",[]);
        }
    }

    public function destroy($id) {
        try {
            $screen = Screen::with('cinema:id,name')->findOrFail($id);
            $screen->delete();
            return $this->responseCommon(200,"Xóa Phòng thành công.",$screen);

        } catch (\Exception $e) {
            return $this->responseError(404,"Phòng Không tồn tại",[]);
        }
    }




    //Validate

    public function validateCreateScreens()
    {
        return [
            'name' =>'required|min:5|max:255|unique:Screens,name,NULL,id,deleted_at,NULL',

        ];
    }

    public function alertCreateScreens()
    {
        return [
            'required' => 'Không được để trống.',
            'name.unique' => 'Tên không được trùng.',
            'name.min' => 'Tên phải ít nhất 5 kí tự.',
            'name.max' => 'Tên quá dài.',
        ];
    }

    public function validateUpdateScreens($id)
{
    return [
        'name' => 'required|min:5|max:255|unique:Screens,name,' . $id,
    ];
}


    public function alertUpdateScreens()
    {
        return [
           'required' => 'Không được để trống.',
            'name.unique' => 'Tên không được trùng.',
            'name.min' => 'Tên phải ít nhất 5 kí tự.',
            'name.max' => 'Tên quá dài.',
        ];
    }

}

