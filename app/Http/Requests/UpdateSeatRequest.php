<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSeatRequest extends FormRequest
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
        $seatId = $this->route('seat'); // Lấy ID từ route

        return [
            'row' => 'required',
            'number' => ['required'],
            'type' => 'required',
            'price' => 'required|numeric|min:0',
            'screen_id' => [
                'required',
                Rule::exists('screens', 'id')->whereNull('deleted_at') //  Kiểm tra chỉ các screen chưa bị xóa mềm
            ],
        ];
    }

    public function messages()
    {
        return (new StoreSeatRequest)->messages();
    }
}
