<?php

namespace App\Http\Controllers;

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


    public function store(Request $request)
    {
        $rules = $this->validateCreateProduct();
        $alert = $this->alertCreateProduct();
        $validator = Validator::make($request->all(), $rules, $alert);

        if ($validator->fails()) {
            return $this->responseValidate(422, "Dữ liệu không hợp lệ", $validator->errors());
        } else {
            $product = $request->all();
            if ($request->file('image')) {
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
    }

    public function update(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);

            $rules = $this->validateUpdateProduct($id);

            $alert = $this->alertUpdateProduct();

            $validator = Validator::make($request->all(), $rules, $alert);

            if ($validator->fails()) {
                return $this->responseValidate(422, "Dữ liệu không hợp lệ", $validator->errors());
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
            return $this->responseCommon(404, "Sản phẩm này không tồn tại hoặc đã bị xóa.",[]);
        }
    }

    public function show($id) {
        try {
            $product = Product::findOrFail($id);
            return $this->responseCommon(200, "Tìm sản phẩm thành công.", $product);
        } catch (\Exception $e) {
            return $this->responseCommon(404, "Sản phẩm này không tồn tại hoặc đã bị xóa.",[]);
        }
    }

    public function destroy($id){
        try{
            $product = Product::findOrFail($id);

            // Đường dẫn ảnh
            $imageDirectory = 'images/products/';
            // Xóa sản phẩm thì xóa luôn ảnh sản phẩm đó
            File::delete($imageDirectory . $product->fileName);

            $product->delete();

            return $this->responseCommon(200, "Xóa sản phẩm thành công.",[]);
        }catch(\Exception $e){
            return $this->responseCommon(404, "Sản phẩm này không tồn tại hoặc đã bị xóa.",[]);
        }
    }

    public function validateCreateProduct()
    {
        return [
            'code' => 'required|unique:products|min:2|max:20',
            'name' => 'required|unique:products|min:5|max:255',
            'image' => 'required|mimes:jpeg,jpg,png',
            'price' => 'required|integer|min:1',
        ];
    }

    public function alertCreateProduct()
    {
        return [
            'required' => 'Không được để trống thông tin :attribute.',
            'code.unique' => 'Mã sản phẩm không được trùng.',
            'code.min' => 'Mã sản phẩm phải ít nhất 2 kí tự.',
            'code.max' => 'Mã sản phẩm quá dài.',
            'name.unique' => 'Tên sản phẩm không được trùng.',
            'name.min' => 'Tên sản phẩm phải ít nhất 5 kí tự.',
            'name.max' => 'Tên sản phẩm quá dài.',
            'mimes' => 'Bạn chỉ được nhập file ảnh có đuôi jpeg,jpg,png',
            'price.integer' => 'Gía sản phẩm phải là số.',
            'price.min' => 'Gía sản phẩm phải lớn hơn 0.',
        ];
    }

    public function validateUpdateProduct($id)
    {
        return [
            'code' => 'required|min:2|max:20|unique:products,code,' . $id,
            'name' => 'required|min:5|max:255|unique:products,name,' . $id,
            'image' => 'mimes:jpeg,jpg,png',
            'price' => 'required|integer|min:1',
        ];
    }

    public function alertUpdateProduct()
    {
        return [
            'required' => 'Không được để trống thông tin :attribute.',
            'code.unique' => 'Mã sản phẩm không được trùng.',
            'code.min' => 'Mã sản phẩm phải ít nhất 2 kí tự.',
            'code.max' => 'Mã sản phẩm quá dài.',
            'name.unique' => 'Tên sản phẩm không được trùng.',
            'name.min' => 'Tên sản phẩm phải ít nhất 5 kí tự.',
            'name.max' => 'Tên sản phẩm quá dài.',
            'mimes' => 'Bạn chỉ được nhập file ảnh có đuôi jpeg,jpg,png',
            'price.integer' => 'Gía sản phẩm phải là số.',
            'price.min' => 'Gía sản phẩm phải lớn hơn 0.',
        ];
    }
}
