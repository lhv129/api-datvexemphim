<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class UserController extends Controller
{
    public function index()
    {
        $users = User::select('users.id', 'role_id', 'roles.name as role_name', 'users.name', 'email', 'email_verified_at', 'phone', 'address', 'birthday', 'avatar', 'status', 'fileName')
            ->join('roles', 'roles.id', 'users.role_id')
            ->get();
        return $this->responseCommon(200, 'Lấy danh sách user thành công', $users);
    }

    public function store(CreateUserRequest $request)
    {
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            // Tạo ngẫu nhiên tên ảnh 12 kí tự
            $imageName = Str::random(12) . "." . $file->getClientOriginalExtension();
            // Đường dẫn ảnh
            $imageDirectory = 'images/users/avatars/';

            $file->move($imageDirectory, $imageName);
            $path_image   = 'http://filmgo.io.vn/' . ($imageDirectory . $imageName);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role_id' => $request->role_id,
            'phone' => $request->phone,
            'address' => $request->address,
            'birthday' => $request->birthday,
            'avatar' => $path_image,
            'fileName' => $imageName,
            'status' => $request->status,
        ]);
        // Trả về access_token và thông tin user khi đăng ký thành công
        return $this->responseCommon(201, "Đăng ký thành công người dùng", $user);
    }

    public function update(UpdateUserRequest $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                // Đường dẫn ảnh
                $imageDirectory = 'images/users/avatars/';
                // Xóa ảnh nếu ảnh cũ
                File::delete($imageDirectory . $user->fileName);
                // Tạo ngẫu nhiên tên ảnh 12 kí tự
                $imageName = Str::random(12) . "." . $file->getClientOriginalExtension();

                $file->move($imageDirectory, $imageName);

                $path_image   = 'http://filmgo.io.vn/' . ($imageDirectory . $imageName);
            } else {
                $path_image = $user->avatar;
            }
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'role_id' => $request->role_id,
                'phone' => $request->phone,
                'address' => $request->address,
                'birthday' => $request->birthday,
                'avatar' => $path_image,
                'fileName' => $imageName ?? $user->fileName,
                'status' => $request->status,
            ]);
            return $this->responseCommon(200, "Cập nhật người dùng thành công.", $user);

        } catch (\Exception $e) {
            return $this->responseError(404, "Người dùng này không tồn tại hoặc đã bị xóa.", $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            // Đường dẫn ảnh
            $imageDirectory = 'images/users/avatars/';
            // Xóa sản phẩm thì xóa luôn ảnh sản phẩm đó
            File::delete($imageDirectory . $user->fileName);

            $user->delete();
            return $this->responseCommon(200, "Xóa người dùng thành công.", []);
        } catch (\Exception $e) {
            return $this->responseCommon(404, "Người dùng này không tồn tại hoặc đã bị xóa.", []);
        }
    }
}
