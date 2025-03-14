<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class StoreShowtimeRequest extends FormRequest
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
                Rule::exists('movies', 'id')->whereNull('deleted_at'),
            ],
            'screen_id' => [
                'required',
                Rule::exists('screens', 'id')->whereNull('deleted_at'),
            ],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
        ];
    }



    public function messages()
    {
        return [
            'movie_id.required' => 'Vui lòng chọn phim.',
            'movie_id.exists' => 'Phim không tồn tại.',
            'screen_id.required' => 'Vui lòng chọn màn hình chiếu.',
            'screen_id.exists' => 'Màn hình chiếu không tồn tại.',
            'start_time.required' => 'Vui lòng nhập thời gian bắt đầu.',
            'start_time.date_format' => 'Thời gian bắt đầu không đúng định dạng (H:i:s).',
            'start_time.after' => 'Thời gian bắt đầu phải lớn hơn thời gian hiện tại.',
            'end_time.required' => 'Vui lòng nhập thời gian kết thúc.',
            'end_time.date_format' => 'Thời gian kết thúc không đúng định dạng (H:i:s).',
            'end_time.after' => 'Thời gian kết thúc phải sau thời gian bắt đầu.',
            'date.required' => 'Vui lòng chọn ngày chiếu.',
            'date.date_format' => 'Ngày chiếu phải đúng định dạng (Y-m-d).',
            'date.after_or_equal' => 'Ngày chiếu phải là hôm nay hoặc sau hôm nay.',
        ];
    }

}
