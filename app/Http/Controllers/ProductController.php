<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::select('id', 'code', 'name', 'price', 'image')
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

                $imageName = Str::random(12) . "." . $file->getClientOriginalExtension();

                $imageDirectory = 'images/products/';

                $file->move($imageDirectory, $imageName);

                $path_image   = 'http://127.0.0.1:8000/'.($imageDirectory . $imageName);

                $product['image'] = $path_image;
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
                    $imageDirectory = 'images/products/';


                    File::delete($imageDirectory . 'NlzNyGCNEzLE.png');
                } else {
                    $path_image = $product->image;
                }
            }
        } catch (\Exception $e) {
            return $this->responseCommon(404, "Id sản phẩm này không tồn tại.", []);
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
