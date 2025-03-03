<?php

namespace App\Http\Controllers;

use App\Models\Actor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class ActorController extends Controller
{
    public function index()
    {
        $actors = Actor::select('id', 'name', 'avatar', 'fileName')
            ->get();
        return $this->responseCommon(200, "Lấy danh sách diễn viên thành công.", $actors);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:50',
            'avatar' => 'required|mimes:jpeg,jpg,png'
        ], [
            'required' => 'Không được để trống :attribute.',
            'min' => 'Tên diễn viên thiểu ít nhất 2 kí tự.',
            'max' => 'Tên diễn viên quá dài.',
            'avatar.mimes' => 'Bạn chỉ được nhập file ảnh có đuôi jpeg,jpg,png.',
        ]);
        if ($validator->fails()) {
            return $this->responseError(422, "Dữ liệu không hợp lệ", $validator->errors());
        } else {
            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                // Tạo ngẫu nhiên tên ảnh 12 kí tự
                $imageName = Str::random(12) . "." . $file->getClientOriginalExtension();
                // Đường dẫn ảnh
                $imageDirectory = 'images/actors/';

                $file->move($imageDirectory, $imageName);
                $path_image   = 'http://filmgo.io.vn/' . ($imageDirectory . $imageName);
            }
            $actor = Actor::create([
                'name' => $request->name,
                'avatar' => $path_image,
                'fileName' => $imageName,
            ]);
            return $this->responseCommon(200, "Thêm mới diễn viên thành công.", $actor);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $actor = Actor::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|min:2|max:50',
                'avatar' => 'mimes:jpeg,jpg,png'
            ], [
                'required' => 'Không được để trống tên thể loại.',
                'min' => 'Tên diễn viên thiểu ít nhất 2 kí tự.',
                'max' => 'Tên diễn viên quá dài.',
                'avatar.mimes' => 'Bạn chỉ được nhập file ảnh có đuôi jpeg,jpg,png.',
            ]);
            if ($validator->fails()) {
                return $this->responseError(422, "Dữ liệu không hợp lệ", $validator->errors());
            } else {
                if ($request->hasFile('avatar')) {
                    $file = $request->file('avatar');
                    // Đường dẫn ảnh
                    $imageDirectory = 'images/actors/';
                    // Xóa ảnh nếu ảnh cũ
                    File::delete($imageDirectory . $actor->fileName);
                    // Tạo ngẫu nhiên tên ảnh 12 kí tự
                    $imageName = Str::random(12) . "." . $file->getClientOriginalExtension();

                    $file->move($imageDirectory, $imageName);

                    $path_image   = 'http://filmgo.io.vn/' . ($imageDirectory . $imageName);
                } else {
                    $path_image = $actor->avatar;
                }
                $actor->update([
                    'name' => $request->name,
                    'avatar' => $path_image,
                    'fileName' => $imageName ?? $actor->fileName, // Dùng toán tử 3 ngôi, nếu không thêm ảnh mới thì giữ lại tên ảnh cũ
                ]);
                return $this->responseCommon(200, "Cập nhật thông tin diễn viên thành công.", $actor);
            }
        } catch (\Exception $e) {
            return $this->responseCommon(404, "Diễn viên này không tồn tại hoặc đã bị xóa.", []);
        }
    }

    public function show($id)
    {
        try {
            $actor = Actor::findOrFail($id);
            return $this->responseCommon(200, "Tìm thông tin diễn viên thành công.", $actor);
        } catch (\Exception $e) {
            return $this->responseCommon(404, "Diễn viên này không tồn tại hoặc đã bị xóa.", []);
        }
    }

    public function destroy($id)
    {
        try {
            $actor = Actor::findOrFail($id);
            $actor->delete();
            return $this->responseCommon(200, "Xóa thông tin diễn viên thành công.", []);
        } catch (\Exception $e) {
            return $this->responseCommon(404, "Diễn viên này không tồn tại hoặc đã bị xóa.", []);
        }
    }
}
