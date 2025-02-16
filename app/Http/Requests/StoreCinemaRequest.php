<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCinemaRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Cho phép mọi người có thể gửi request
    }

    public function rules()
    {
        return [
            'code' => [
                'required',
                Rule::unique('cinemas')->where(function ($query) {
                    return $query->where('province_id', $this->province_id)
                                 ->whereNull('deleted_at'); // Chỉ kiểm tra với dữ liệu chưa bị xóa mềm
                })
            ],
            'name' => [
                'required',
                'min:5',
                'max:255',
                Rule::unique('cinemas')->where(function ($query) {
                    return $query->where('province_id', $this->province_id)
                                 ->whereNull('deleted_at');
                })
            ],
            'address' => 'required',
            'image' => 'required|mimes:jpeg,jpg,png',
            'contact' => 'required',
            'province_id' => [
                'required',
                Rule::exists('provinces', 'id')->whereNull('deleted_at') // Đảm bảo province chưa bị xóa mềm
            ]
        ];
    }

    public function messages()
    {
        return [
            'required' => 'Không được để trống.',
            'code.unique' => 'Code đã tồn tại trong tỉnh này.',
            'name.unique' => 'Tên đã tồn tại trong tỉnh này.',
            'name.min' => 'Tên phải ít nhất 5 kí tự.',
            'name.max' => 'Tên quá dài.',
            'mimes' => 'Bạn chỉ được nhập file ảnh có đuôi jpeg, jpg, png.',
            'province_id.exists' => 'Tỉnh thành không tồn tại.',
        ];
    }
}
