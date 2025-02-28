<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'name' => 'required|unique:users,name',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|max:100',
            "confirm_password" => 'required|min:6|max:100|same:password',
            'phone' => 'required|regex:/^(0)(98)[0-9]{7}$/',
            'address' => 'required|min:6|max:255',
            'birthday' => 'required',
            // 'avatar' => 'required|mimes:jpeg,jpg,png',
        ];
    }

    public function messages()
    {
        return [
            'required' => 'Không được để trống thông tin :attribute.',
            'email.email' => 'Email không đúng định dạng.',
            'name.unique' => 'Tên của bạn đã được đặt, vui lòng chọn tên khác.',
            'email.unique' => 'Email của bạn đã được tạo, vui lòng chọn email khác.',
            'min' => ':attribute. tối thiểu ít nhất 6 kí tự.',
            'max' => ':attribute. quá dài, vui lòng nhập lại.',
            'phone.regex' => 'Sai định dạng số điện thoại, vui lòng kiểm tra lại.',
            // 'avatar.mimes' => 'Bạn chỉ được nhập file ảnh có đuôi jpeg,jpg,png.',
            'same' => 'Mật khẩu phải giống nhau,vui lòng kiểm tra lại.'
        ];
    }
}
