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
        // Log::info('Dữ liệu request trong FormRequest:', $this->all());
        return [
            'showtimes' => 'required|array|min:1',
            'showtimes.*.movie_id' => [
                'required',
                Rule::exists('movies', 'id')->whereNull('deleted_at'),
            ],
            'showtimes.*.screen_id' => [
                'required',
                Rule::exists('screens', 'id')->whereNull('deleted_at'),
            ],
            'showtimes.*.start_time' => 'required|date_format:Y-m-d H:i:s|after:now',
            'showtimes.*.end_time' => 'required|date_format:Y-m-d H:i:s|after:showtimes.*.start_time',
            'showtimes.*.date' => 'required|date_format:Y-m-d|after_or_equal:today',
        ];
    }


    public function messages()
    {
        return [
            'showtimes.required' => 'Không để trống.',
            'showtimes.array' => 'Showtimes phải là một mảng.',
            'showtimes.min' => 'Phải có ít nhất một suất chiếu.',
            'showtimes.*.movie_id.required' => 'Vui lòng chọn phim.',
            'showtimes.*.movie_id.exists' => 'Phim không tồn tại.',
            'showtimes.*.screen_id.required' => 'Vui lòng chọn màn hình chiếu.',
            'showtimes.*.screen_id.exists' => 'Màn hình chiếu không tồn tại.',
            'showtimes.*.start_time.required' => 'Vui lòng nhập thời gian bắt đầu.',
            'showtimes.*.start_time.date_format' => 'Thời gian bắt đầu không đúng định dạng (YYYY-MM-DD HH:MM:SS).',
            'showtimes.*.start_time.after' => 'Thời gian bắt đầu phải lớn hơn thời gian hiện tại.',
            'showtimes.*.end_time.required' => 'Vui lòng nhập thời gian kết thúc.',
            'showtimes.*.end_time.date_format' => 'Thời gian kết thúc không đúng định dạng (YYYY-MM-DD HH:MM:SS).',
            'showtimes.*.end_time.after' => 'Thời gian kết thúc phải sau thời gian bắt đầu.',
            'showtimes.*.date.required' => 'Vui lòng chọn ngày chiếu.',
            'showtimes.*.date.date_format' => 'Ngày chiếu phải đúng định dạng (YYYY-MM-DD).',
            'showtimes.*.date.after_or_equal' => 'Ngày chiếu phải là hôm nay hoặc sau hôm nay.',
        ];
    }

}
