<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSeatRequest extends FormRequest
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
            'row' => 'required',
            'number' => [
                'required',
                Rule::unique('seats')->where(function ($query) {
                    return $query->where('row', $this->row)
                                 ->where('screen_id', $this->screen_id)
                                 ->whereNull('deleted_at'); // Thêm để bỏ qua các bản ghi đã xóa mềm
                }),
            ],
            'type' => 'required',
            'price' => 'required|numeric|min:0', // Kiểm tra giá trị số, không âm
            'screen_id' => [
                'required',
                Rule::exists('screens', 'id')->whereNull('deleted_at') //  Chỉ chấp nhận screen_id chưa bị xóa mềm
            ]
        ];
    }

    public function messages()
    {
        return [
            'number.unique' => 'Số ghế này đã tồn tại trong hàng ' . $this->row . ' của phòng chiếu!',
            'row.required' => 'Vui lòng nhập hàng ghế.',
            'number.required' => 'Vui lòng nhập số ghế.',
            'type.required' => 'Vui lòng nhập loại ghế.',
            'price.required' => 'Vui lòng nhập giá ghế.',
            'price.numeric' => 'Giá ghế phải là số.',
            'price.min' => 'Giá ghế không được nhỏ hơn 0.',
            'screen_id.required' => 'Vui lòng nhập phòng.',
            'screen_id.exists' => 'Phòng chiếu không tồn tại.',
        ];
    }
}
