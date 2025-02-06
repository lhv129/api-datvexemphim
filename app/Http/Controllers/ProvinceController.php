<?php

namespace App\Http\Controllers;

use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProvinceController extends Controller
{
    public function index() {
        $provinces = Province::select('id','name')->get();
        return $this->responseCommon(200,'Lấy Danh Sách Thành Công!',$provinces);
    }

    public function store(Request $request) {
        $rules = $this->validateCreateProvince();
        $alert = $this->alertCreateProvince();
        $validator = Validator::make($request->all(),$rules,$alert);
        if($validator->fails()) {
            return $this->responseError(422,'Dữ Liệu Không hợp lệ',$validator->errors());
        } else {
            $province = Province::create([
                'name' => $request->name,
            ]);
            return $this->responseCommon(201,"Thêm thành công.",$province);
        }
    }

    public function update(Request $request,$id) {
        try{
            $province = Province::findOrFail($id);
            $rules = $this->validateUpdateProvince($id);
            $alert = $this->alertUpdateProvince();
            $validator = Validator::make($request->all(),$rules,$alert);
            if($validator->fails()) {
                return $this->responseError(422,'Dữ Liệu không hợp lệ', $validator->errors());

            } else {
                $province->update([
                    'name' => $request->name,
                ]);
                return $this->responseCommon(200,"Cập Nhật thành công.",$province);
            }
        } catch (\Exception $e) {
            return $this->responseCommon(404,"Tỉnh Không tồn tại hoặc đã bị xóa.",[]);
        }
    }

    public function destroy($id) {
        try {
            $province = Province::findOrFail($id);
            $province->delete();
            return $this->responseCommon(200,"Xóa Thành công.",[]);
        } catch (\Exception $e) {
            return $this->responseCommon(404,"Tỉnh Không tồn tại hoặc đã bị xóa.",[]);

        }
    }

    //Validate

    public function validateCreateProvince(){
        return [
            'name' => 'required|unique:provinces,name',
        ];
    }
    public function alertCreateProvince(){
        return [
            'required' => 'không được để trống',
            'unique' => 'tên bị trùng',
        ];
    }
    public function validateUpdateProvince($id){
        return [
            'name' => 'required|unique:provinces,name'.$id,
        ];
    }
    public function alertUpdateProvince(){
        return [
            'required' => 'không được để trống',
            'unique' => 'tên bị trùng',
        ];
    }
}
