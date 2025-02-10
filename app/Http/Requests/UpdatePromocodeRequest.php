<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePromocodeRequest extends FormRequest
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
    public function rules()
    {
        $id = $this->route('id'); // Lấy id từ route parameters

        return [
            'code' => [
                'required',
                Rule::unique('promo_codes')->ignore($id)->whereNull('deleted_at') // Kiểm tra không trùng với mã code hiện tại và chưa bị xóa mềm
            ],
            'description' => 'required|string|max:255',
            'discount_amount' => 'required|numeric|min:0', // Số tiền giảm giá phải là số và không nhỏ hơn 0
            'start_date' => 'required|date|after_or_equal:today', // Ngày bắt đầu phải là ngày hôm nay hoặc sau
            'end_date' => 'required|date|after:start_date', // Ngày kết thúc phải sau ngày bắt đầu
        ];
    }

    /**
     * Get the custom error messages for validation.
     *
     * @return array
     */
    public function messages()
    {
        return (new StorePromocodeRequest())->messages();
    }
}
