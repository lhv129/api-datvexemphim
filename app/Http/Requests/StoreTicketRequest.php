<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
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
            'showtime_id' => 'required|integer|exists:showtimes,id',
            'seat_ids' => 'required|array|max:8',
            'seat_ids.*' => 'integer|exists:seats,id',
            'payment_method_id' => 'required|integer',
            'promo_code_id' => 'nullable|integer|exists:promo_codes,id',
            'products' => 'nullable|array',
            'products.*.product_id' => 'integer|exists:products,id',
            'products.*.quantity' => 'integer|min:1|max:8'
        ];
    }
    public function messages()
    {
        return [
            'required' => 'Không được để trống thông tin :attribute.',
            'integer' => ':attribute phải là số nguyên.',
            'exists' => ':attribute không tồn tại trong hệ thống.',
            'array' => ':attribute phải là một mảng.',
            'max' => ':attribute không được lớn hơn :max.',
        ];
    }
}
