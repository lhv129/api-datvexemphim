<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::select('id', 'code', 'name', 'price', 'image', 'fileName')
            ->get();
        return $this->responseCommon(200, "Lấy danh sách sản phẩm thành công.", $products);
    }


    public function store(StoreProductRequest $request)
    {
        $product = $request->all();
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            // Tạo ngẫu nhiên tên ảnh 12 kí tự
            $imageName = Str::random(12) . "." . $file->getClientOriginalExtension();
            // Đường dẫn ảnh
            $imageDirectory = 'images/products/';

            $file->move($imageDirectory, $imageName);

            $path_image   = 'http://127.0.0.1:8000/' . ($imageDirectory . $imageName);

            $product['image'] = $path_image;
            $product['fileName'] = $imageName;

            Product::create($product);
            return $this->responseCommon(200, "Thêm mới sản phẩm thành công.", $product);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);

            $rules = $this->validateUpdateProduct($id);

            $alert = $this->alertUpdateProduct();

            $validator = Validator::make($request->all(), $rules, $alert);

            if ($validator->fails()) {
                return $this->responseError(422, "Dữ liệu không hợp lệ", $validator->errors());
            } else {
                if ($request->hasFile('image')) {
                    $file = $request->file('image');
                    // Đường dẫn ảnh
                    $imageDirectory = 'images/products/';
                    // Xóa ảnh nếu ảnh cũ
                    File::delete($imageDirectory . $product->fileName);
                    // Tạo ngẫu nhiên tên ảnh 12 kí tự
                    $imageName = Str::random(12) . "." . $file->getClientOriginalExtension();

                    $file->move($imageDirectory, $imageName);

                    $path_image   = 'http://127.0.0.1:8000/' . ($imageDirectory . $imageName);
                } else {
                    $path_image = $product->image;
                }
                $product->update([
                    'code' => $request->code,
                    'name' => $request->name,
                    'price' => $request->price,
                    'image' => $path_image,
                    'fileName' => $imageName ?? $product->fileName, // Dùng toán tử 3 ngôi, nếu không thêm ảnh mới thì giữ lại tên ảnh cũ
                    'status' => $request->status
                ]);
                return $this->responseCommon(200, "Cập nhật sản phẩm thành công.", $product);
            }
        } catch (\Exception $e) {
            return $this->responseCommon(404, "Sản phẩm này không tồn tại hoặc đã bị xóa.", []);
        }
    }

    public function show($id)
    {
        try {
            $product = Product::findOrFail($id);
            return $this->responseCommon(200, "Tìm sản phẩm thành công.", $product);
        } catch (\Exception $e) {
            return $this->responseCommon(404, "Sản phẩm này không tồn tại hoặc đã bị xóa.", []);
        }
    }

    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);

            // Đường dẫn ảnh
            $imageDirectory = 'images/products/';
            // Xóa sản phẩm thì xóa luôn ảnh sản phẩm đó
            File::delete($imageDirectory . $product->fileName);

            $product->delete();

            return $this->responseCommon(200, "Xóa sản phẩm thành công.", []);
        } catch (\Exception $e) {
            return $this->responseCommon(404, "Sản phẩm này không tồn tại hoặc đã bị xóa.", []);
        }
    }
}
