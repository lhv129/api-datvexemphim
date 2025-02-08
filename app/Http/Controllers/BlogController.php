<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class BlogController extends Controller
{
    public function index()
    {
        $blogs = Blog::select('id', 'title', 'content', 'image', 'description', 'created_at', 'updated_at')
            ->get();

        return $this->responseCommon(200, "Lấy danh sách bài blog thành công.", $blogs);
    }

    public function store(Request $request)
    {
        $user = JWTAuth::user();

        $rules = $this->validateCreateBlog();
        $alert = $this->alertCreateBlog();
        $validator = Validator::make($request->all(), $rules, $alert);

        if ($validator->fails()) {
            return $this->responseError(422, "Dữ liệu không hợp lệ", $validator->errors());
        } else {
            $blogData = $request->all();
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $imageName = Str::random(12) . "." . $file->getClientOriginalExtension();
                $imageDirectory = 'images/blogs/';

                $file->move($imageDirectory, $imageName);

                $path_image = 'http://127.0.0.1:8000/' . ($imageDirectory . $imageName);

                $blogData['image'] = $path_image;
                $blogData['fileName'] = $imageName;
            }

            $blogData['user_id'] = $user->id;  // Thêm user_id của người dùng đăng nhập vào blog

            // Tạo mới bài blog
            $blog = Blog::create($blogData);

            return $this->responseCommon(200, "Thêm mới bài blog thành công.", $blog);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $blog = Blog::findOrFail($id);

            $rules = $this->validateUpdateBlog($id);
            $alert = $this->alertUpdateBlog();
            $validator = Validator::make($request->all(), $rules, $alert);

            if ($validator->fails()) {
                return $this->responseError(422, "Dữ liệu không hợp lệ", $validator->errors());
            } else {
                if ($request->hasFile('image')) {
                    $file = $request->file('image');
                    $imageDirectory = 'images/blogs/';

                    // Xóa ảnh cũ
                    File::delete($imageDirectory . $blog->fileName);

                    // Tạo tên ảnh mới
                    $imageName = Str::random(12) . "." . $file->getClientOriginalExtension();
                    $file->move($imageDirectory, $imageName);
                    $path_image = 'http://127.0.0.1:8000/' . ($imageDirectory . $imageName);
                } else {
                    $path_image = $blog->image;
                }

                $blog->update([
                    'title' => $request->title,
                    'content' => $request->content,
                    'description' => $request->description,
                    'image' => $path_image,
                    'fileName' => $imageName ?? $blog->fileName, // Nếu không có ảnh mới thì giữ lại tên ảnh cũ
                ]);

                return $this->responseCommon(200, "Cập nhật bài blog thành công.", $blog);
            }
        } catch (\Exception $e) {
            return $this->responseCommon(404, "Bài blog này không tồn tại hoặc đã bị xóa.", []);
        }
    }

    public function show($id)
    {
        try {
            $blog = Blog::findOrFail($id);
            return $this->responseCommon(200, "Tìm bài blog thành công.", $blog);
        } catch (\Exception $e) {
            return $this->responseCommon(404, "Bài blog này không tồn tại hoặc đã bị xóa.", []);
        }
    }

    public function destroy($id)
    {
        try {
            $blog = Blog::findOrFail($id);

            // Đường dẫn ảnh
            $imageDirectory = 'images/blogs/';
            File::delete($imageDirectory . $blog->fileName); // Xóa ảnh đi cùng với bài viết

            $blog->delete();

            return $this->responseCommon(200, "Xóa bài blog thành công.", []);
        } catch (\Exception $e) {
            return $this->responseCommon(404, "Bài blog này không tồn tại hoặc đã bị xóa.", []);
        }
    }

    // Validate Create Blog
    public function validateCreateBlog()
    {
        return [
            'title' => 'required|min:5|max:255',
            'content' => 'required|string',
            'description' => 'nullable|string',
            'image' => 'required|mimes:jpeg,jpg,png',
        ];
    }

    public function alertCreateBlog()
    {
        return [
            'required' => 'Không được để trống thông tin :attribute.',
            'title.min' => 'Tiêu đề phải có ít nhất 5 kí tự.',
            'title.max' => 'Tiêu đề quá dài.',
            'mimes' => 'Bạn chỉ được nhập file ảnh có đuôi jpeg,jpg,png',
            'content.required' => 'Nội dung là bắt buộc.',
        ];
    }

    // Validate Update Blog
    public function validateUpdateBlog($id)
    {
        return [
            'title' => 'required|min:5|max:255|unique:blogs,title,' . $id,
            'content' => 'required|string',
            'description' => 'nullable|string',
            'image' => 'nullable|mimes:jpeg,jpg,png',
        ];
    }

    public function alertUpdateBlog()
    {
        return [
            'required' => 'Không được để trống thông tin :attribute.',
            'title.min' => 'Tiêu đề phải có ít nhất 5 kí tự.',
            'title.max' => 'Tiêu đề quá dài.',
            'mimes' => 'Bạn chỉ được nhập file ảnh có đuôi jpeg,jpg,png',
            'content.required' => 'Nội dung là bắt buộc.',
        ];
    }

}
