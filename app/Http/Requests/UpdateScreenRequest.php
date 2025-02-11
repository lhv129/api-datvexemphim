<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScreenRequest extends FormRequest
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
                })->ignore($this->route('screen')) // Bỏ qua ID của screen hiện tại
            ],
            'cinema_id' => [
                'required',
                Rule::exists('cinemas', 'id')->whereNull('deleted_at') //  Chỉ chấp nhận cinema_id chưa bị xóa mềm
            ]
        ];
    }

    public function messages(): array
    {
        return (new StoreScreenRequest())->messages();
    }
}
