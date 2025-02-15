<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
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
            'password' => 'required',
            'new_password' => 'required|min:5|max:255',
            'confirm_password' => 'required|same:new_password',
        ];
    }

    public function messages()
    {
        return [
            'required' => 'Không được để trống thông tin :attribute.',
            'new_password.min' => 'Mật khẩu mới phải ít nhất 5 kí tự.',
            'new_password.max' => 'Mật khẩu mới quá dài.',
            'same' => 'Mật khẩu mới phải giống nhau.'
        ];
    }
}
