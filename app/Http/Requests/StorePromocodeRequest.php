<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromocodeRequest extends FormRequest
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
        return [
            'code' => [
                'required',
                Rule::unique('promo_codes')->where(function ($query) {
                    return $query->whereNull('deleted_at'); // Kiểm tra chỉ trong các bản ghi chưa bị xóa mềm
                })
            ],
            'description' => 'required|string|max:255',
            'discount_amount' => 'required|numeric|min:0', // Số tiền giảm giá phải là số và không nhỏ hơn 0
            'start_date' => 'required|date|after_or_equal:today', // Ngày bắt đầu phải là ngày hôm nay hoặc sau
            'end_date' => 'required|date|after:start_date', // Ngày kết thúc phải sau ngày bắt đầu
        ];
    }

    public function messages()
    {
        return [
            'required' => 'Không được để trống.',
            'code.unique' => 'Mã giảm giá đã tồn tại.',
            'description.string' => 'Mô tả phải là một chuỗi văn bản.',
            'description.max' => 'Mô tả không được vượt quá 255 ký tự.',
            'discount_amount.numeric' => 'Số tiền giảm giá phải là một số.',
            'discount_amount.min' => 'Số tiền giảm giá không được nhỏ hơn 0.',
            'start_date.after_or_equal' => 'Ngày bắt đầu phải là hôm nay hoặc sau.',
            'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu.',
            'end_date.date' => 'Ngày kết thúc phải là một ngày hợp lệ.',
        ];
    }
}
