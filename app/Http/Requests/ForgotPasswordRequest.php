<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
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
            'email' => 'required|email|exists:users',
        ];
    }

    public function messages()
    {
        return [
            'required' => 'Vui lòng nhập email',
            'email' => 'Vui lòng nhập đúng định dạng email',
            'exists' => 'Email không tồn tại, vui lòng kiểm tra lại'
        ];
    }
}
