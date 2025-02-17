<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::select('id', 'name')
            ->get();
        return $this->responseCommon(200, "Lấy danh sách chức vụ thành công.", $roles);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:30|unique:roles'
        ], [
            'required' => 'Không được để trống tên chức vụ.',
            'min' => 'Tối thiểu ít nhất 2 kí tự.',
            'max' => 'Tên chức vụ quá dài.',
            'unique' => 'Tên chức vụ không được trùng.',
        ]);
        if ($validator->fails()) {
            return $this->responseError(422, "Dữ liệu không hợp lệ", $validator->errors());
        } else {
            $role = $request->all();
            $role = Role::create($role);
            return $this->responseCommon(200, "Thêm mới chức vụ thành công.", $role);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $role = Role::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|min:2|max:30|unique:roles,name,' . $id,
            ], [
                'required' => 'Không được để trống tên chức vụ.',
                'min' => 'Tối thiểu ít nhất 2 kí tự.',
                'max' => 'Tên chức vụ quá dài.',
                'unique' => 'Tên chức vụ không được trùng.',
            ]);
            if ($validator->fails()) {
                return $this->responseError(422, "Dữ liệu không hợp lệ", $validator->errors());
            } else {
                $role->update([
                    'name' => $request->name
                ]);
                return $this->responseCommon(200, "Cập tên chức vụ thành công.", $role);
            }
        } catch (\Exception $e) {
            return $this->responseCommon(404, "Chức vụ này không tồn tại hoặc đã bị xóa.", []);
        }
    }

    public function show($id){
        try {
            $role = Role::findOrFail($id);
            return $this->responseCommon(200, "Tìm chức vụ thành công.", $role);
        } catch (\Exception $e) {
            return $this->responseCommon(404, "Chức vụ này không tồn tại hoặc đã bị xóa.",[]);
        }
    }

    public function destroy($id){
        try{
            $role = Role::findOrFail($id);
            $role->delete();
            return $this->responseCommon(200, "Xóa chức vụ thành công.",[]);
        }catch(\Exception $e){
            return $this->responseCommon(404, "Chức vụ này không tồn tại hoặc đã bị xóa.",[]);
        }
    }
}
