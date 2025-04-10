<?php

namespace App\Http\Requests;

use App\Models\Movie;
use App\Models\Showtime;
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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $screenId = $this->input('screen_id');
            $startTime = Carbon::parse($this->input('start_time'));
            $endTime = Carbon::parse($this->input('end_time'));
            $date = $this->input('date');
            $movieId = $this->input('movie_id');


            $showtimeDuration  = $startTime->diffInMinutes($endTime);

            $movie = Movie::find($movieId);
            if($movie) {
                $movieDuration = $movie->duration;
                if($showtimeDuration < $movieDuration) {
                    $validator->errors()->add('end_time','Thời gian suất chiếu phải lớn hơn hoặc bằng thời lượng phim.');
                    return;
                }
            }

            $showtimeID = $this->route('showtime');
            // Lấy danh sách suất chiếu đã có trong cùng screen và cùng ngày
            $showtimes = Showtime::where('screen_id', $screenId)
                ->whereDate('start_time', $date)
                ->where('id','!=',$showtimeID)
                ->get();

            foreach ($showtimes as $showtime) {
                $existingStart = Carbon::parse($showtime->start_time);
                $existingEnd = Carbon::parse($showtime->end_time);

                // Nếu suất mới giao nhau với suất cũ (bị trùng)
                if (
                    $startTime < $existingEnd &&
                    $endTime > $existingStart
                ) {
                    $validator->errors()->add('start_time', 'Suất chiếu bị trùng thời gian với suất chiếu khác.');
                    break;
                }

                // Nếu khoảng cách giữa các suất < 15 phút
                if (
                    abs($startTime->diffInMinutes($existingEnd)) < 15 ||
                    abs($endTime->diffInMinutes($existingStart)) < 15
                ) {
                    $validator->errors()->add('start_time', 'Suất chiếu phải cách suất chiếu khác ít nhất 15 phút.');
                    break;
                }
            }
        });
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $movie = Movie::findOrFail($this->movie_id);
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
            'date' => 'required|date_format:Y-m-d|after' . $movie->release_date. '|before:' . $movie->date ,
        ];
    }

    public function messages()
    {
        $movie = Movie::findOrFail($this->movie_id);
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
            'date.after' => 'Ngày chiếu phải sau ngày khởi chiếu của phim ' . $movie->release_date ,
            'date.before' => 'Phim đã quá thời gian chiếu của hệ thống rạp.'
        ];
    }
}
