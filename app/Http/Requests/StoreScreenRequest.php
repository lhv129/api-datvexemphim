<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScreenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'min:5',
                'max:255',
                Rule::unique('screens')->where(function ($query) {
                    return $query->where('cinema_id', $this->cinema_id);
                })
            ],
            'cinema_id' => [
                'required',
                Rule::exists('cinemas', 'id')->whereNull('deleted_at') //  Chỉ chấp nhận cinema_id chưa bị xóa mềm
            ]

        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Không được để trống.',
            'name.unique' => 'Tên phòng đã tồn tại trong rạp này.',
            'name.min' => 'Tên phải ít nhất 5 kí tự.',
            'name.max' => 'Tên quá dài.',
            'cinema_id.required' => 'Vui lòng chọn rạp.',
            'cinema_id.exists' => 'Rạp không tồn tại.',
        ];
    }
}
