<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShowtimeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    public function expectsJson()
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
            'movie_id' => [
                'required',
                Rule::exists('movies', 'id')->whereNull('deleted_at') //  Kiểm tra chỉ các movie chưa bị xóa mềm
            ],
            'screen_id' => [
                'required',
                Rule::exists('screens', 'id')->whereNull('deleted_at') //  Kiểm tra chỉ các screen chưa bị xóa mềm
            ],
            'start_time' => 'required|date_format:Y-m-d H:i:s|after:now', // Định dạng đúng, phải lớn hơn thời gian hiện tại
            'end_time' => 'required|date_format:Y-m-d H:i:s|after:start_time', // Phải sau start_time
            'date' => 'required|date_format:Y-m-d|after_or_equal:today', // Ngày chiếu không được là quá khứ
        ];
    }
    public function messages()
    {
        return (new StoreShowtimeRequest)->messages();
    }
}
