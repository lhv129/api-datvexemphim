<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => 'required|unique:products|min:2|max:20',
            'name' => 'required|unique:products|min:5|max:255',
            'image' => 'required|mimes:jpeg,jpg,png',
            'price' => 'required|integer|min:1',
        ];
    }

    public function messages()
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
