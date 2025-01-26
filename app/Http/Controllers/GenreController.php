<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GenreController extends Controller
{
    public function index()
    {
        $genres = Genre::select('id', 'name')
            ->get();
        return $this->responseCommon(200, "Lấy danh sách thể loại phim thành công.", $genres);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:30|unique:genres'
        ], [
            'required' => 'Không được để trống tên thể loại.',
            'min' => 'Tối thiểu ít nhất 2 kí tự.',
            'max' => 'Tên thể loại quá dài.',
            'unique' => 'Tên thể loại không được trùng.',
        ]);
        if ($validator->fails()) {
            return $this->responseValidate(422, "Dữ liệu không hợp lệ", $validator->errors());
        } else {
            $genre = $request->all();
            Genre::create($genre);
            return $this->responseCommon(200, "Thêm mới thể loại phim thành công.", $genre);
        }
    }

    public function update(Request $request,$id)
    {
        try{
            $genre = Genre::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|min:2|max:30|unique:genres,name,'.$id,
            ], [
                'required' => 'Không được để trống tên thể loại.',
                'min' => 'Tối thiểu ít nhất 2 kí tự.',
                'max' => 'Tên thể loại quá dài.',
                'unique' => 'Tên thể loại không được trùng.',
            ]);
            if ($validator->fails()) {
                return $this->responseValidate(422, "Dữ liệu không hợp lệ", $validator->errors());
            } else {
                $genre->update([
                    'name' => $request->name
                ]);
                return $this->responseCommon(200, "Cập nhật thể loại phim thành công.", $genre);
            }
        }catch(\Exception $e){
            return $this->responseCommon(404, "Thể loại phim này không tồn tại hoặc đã bị xóa.",[]);
        }
    }

    public function show($id){
        try {
            $genre = Genre::findOrFail($id);
            return $this->responseCommon(200, "Tìm thể loại phim thành công.", $genre);
        } catch (\Exception $e) {
            return $this->responseCommon(404, "Thể loại phim này không tồn tại hoặc đã bị xóa.",[]);
        }
    }

    public function destroy($id){
        try{
            $genre = Genre::findOrFail($id);
            $genre->delete();
            return $this->responseCommon(200, "Xóa thể loại phim thành công.",[]);
        }catch(\Exception $e){
            return $this->responseCommon(404, "Thể loại phim này không tồn tại hoặc đã bị xóa.",[]);
        }
    }
}
