<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProvinceRequest extends FormRequest
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
           'name' => [
                'required',
                'min:5',
                'max:255',
                Rule::unique('cinemas')->where(function ($query) {
                    return $query->where('province_id', $this->province_id)
                                 ->whereNull('deleted_at');
                })
            ],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Tên không được để trống.',
            'name.unique' => 'Tên đã tồn tại, vui lòng chọn tên khác.',
        ];
    }
}
