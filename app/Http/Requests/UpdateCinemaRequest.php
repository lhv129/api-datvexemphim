<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCinemaRequest extends FormRequest
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
                                ->whereNull('deleted_at');
                })->ignore($this->route('id')) // Bỏ qua ID hiện tại
            ],
            'name' => [
                'required',
                'min:5',
                'max:255',
                Rule::unique('cinemas')->where(function ($query) {
                    return $query->where('province_id', $this->province_id)
                                ->whereNull('deleted_at');
                })->ignore($this->route('id')) // Bỏ qua ID hiện tại
            ],
            'address' => 'required',
            'image' => 'nullable|mimes:jpeg,jpg,png',
            'contact' => 'required',
            'province_id' => [
                'required',
                Rule::exists('provinces', 'id')->whereNull('deleted_at')
            ]
        ];
    }


    public function messages()
    {
        return (new StoreCinemaRequest())->messages();
    }
}
