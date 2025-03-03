<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
            'code' => 'required|min:2|max:20|unique:products,code,' . $this->id,
            'name' => 'required|min:5|max:255|unique:products,name,' . $this->id,
            'price' => 'required|integer|min:1',
            'status' => 'required|in:active,inactive'
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
            'price.integer' => 'Gía sản phẩm phải là số.',
            'price.min' => 'Gía sản phẩm phải lớn hơn 0.',
            'status.in' => 'Trạng thái phải là active hoặc inactive.',
        ];
    }
}
