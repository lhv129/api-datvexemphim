<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
            'role_id' => 'required|integer|exists:roles,id',
            'name' => 'required|unique:users,name,' . $this->id,
            'email' => 'required|email|unique:users,email,' . $this->id,
            'phone' => 'required|regex:/^(0)(98)[0-9]{7}$/',
            'address' => 'required|min:6|max:255',
            'birthday' => 'required',
            'status' => 'required|in:inactive,active',
            'avatar' => 'mimes:jpeg,jpg,png',
        ];
    }

    public function messages()
    {
        return [
            'required' => 'Không được để trống thông tin :attribute.',
            'role_id.integer' => 'Role_id phải là 1 số',
            'role_id.exists' => 'Role_id không tồn tại, vui lòng kiểm tra lại',
            'email.email' => 'Email không đúng định dạng',
            'name.unique' => 'Tên của bạn đã được đặt, vui lòng chọn tên khác',
            'email.unique' => 'Email của bạn đã được tạo, vui lòng chọn email khác',
            'min' => ':attribute. tối thiểu ít nhất 6 kí tự',
            'max' => ':attribute. quá dài, vui lòng nhập lại',
            'phone.regex' => 'Sai định dạng số điện thoại, vui lòng kiểm tra lại',
            'avatar.mimes' => 'Bạn chỉ được nhập file ảnh có đuôi jpeg,jpg,png',
            'status.in' => 'Trạng thái thì chỉ có inactive hoặc active'
        ];
    }
}
