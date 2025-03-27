<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

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
     * Chuẩn hóa dữ liệu trước khi validation
     */
    protected function prepareForValidation()
    {
        if ($this->has(['date', 'start_time', 'end_time'])) {
            $this->merge([
                'start_time' => Carbon::createFromFormat('Y-m-d H:i', $this->date . ' ' . $this->start_time)
                    ->format('Y-m-d H:i:s'),
                'end_time' => Carbon::createFromFormat('Y-m-d H:i', $this->date . ' ' . $this->end_time)
                    ->format('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'movie_id' => [
                'required',
                Rule::exists('movies', 'id')->whereNull('deleted_at')
            ],
            'screen_id' => [
                'required',
                Rule::exists('screens', 'id')->whereNull('deleted_at')
            ],
            'start_time' => 'required|date_format:Y-m-d H:i:s',
            'end_time' => 'required|date_format:Y-m-d H:i:s|after:start_time',
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
        ];
    }

    public function messages()
    {
        return [
            'movie_id.required' => 'Vui lòng chọn phim.',
            'movie_id.exists' => 'Phim không tồn tại hoặc đã bị xóa.',

            'screen_id.required' => 'Vui lòng chọn màn hình chiếu.',
            'screen_id.exists' => 'Màn hình chiếu không tồn tại hoặc đã bị xóa.',

            'start_time.required' => 'Vui lòng nhập thời gian bắt đầu.',
            'start_time.date_format' => 'Thời gian bắt đầu không đúng định dạng (Y-m-d H:i:s).',

            'end_time.required' => 'Vui lòng nhập thời gian kết thúc.',
            'end_time.date_format' => 'Thời gian kết thúc không đúng định dạng (Y-m-d H:i:s).',
            'end_time.after' => 'Thời gian kết thúc phải sau thời gian bắt đầu.',

            'date.required' => 'Vui lòng chọn ngày chiếu.',
            'date.date_format' => 'Ngày chiếu phải đúng định dạng (YYYY-MM-DD).',
            'date.after_or_equal' => 'Ngày chiếu phải là hôm nay hoặc sau hôm nay.',
        ];
    }
}
