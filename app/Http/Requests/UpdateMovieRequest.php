<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMovieRequest extends FormRequest
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
        //Lấy ra thời gian hiện tại
        $now = Carbon::now();
        // Thời gian hiện tại + 3 tháng
        $futureTime = Carbon::now()->addMonths(2);

        return [
            'title' => 'required|min:5|max:255|unique:movies,title,' . $this->id,
            'description' => 'required|min:5|max:500',
            'poster' => 'mimes:jpeg,jpg,png',
            'trailer' => 'required',
            'duration' => 'required',
            'rating' => 'required',
            'release_date' => 'required|after:' . $now->toDateString() . '|before:' . $futureTime->toDateString(),
            'end_date' => 'required|after:' . Carbon::parse($this->release_date)->addDays(13)->toDateString() . '|before:' . Carbon::parse($this->release_date)->addDays(31)->toDateString(),
            'genres' => 'required|array ',
            'actors' => 'required|array ',
        ];
    }

    public function messages()
    {
        return [
            'required' => 'Không được để trống thông tin :attribute.',
            'title.unique' => 'Tiêu đề phim không được trùng.',
            'title.min' => 'Tiêu đề phim phải ít nhất 5 kí tự.',
            'title.max' => 'Tiêu đề phim quá dài.',
            'description.min' => 'Mô tả phim phải ít nhất 5 kí tự.',
            'description.max' => 'Mô tả phim quá dài.',
            'mimes' => 'Bạn chỉ được nhập file ảnh có đuôi jpeg,jpg,png',
            'genres.array' => 'Thể loại phim phải là 1 mảng array',
            'actors.array' => 'Diễn viên phim phải là 1 mảng array',
            'release_date.after' => 'Ngày công chiếu phải sau ngày hôm nay',
            'release_date.before' => 'Ngày công chiếu không được vượt quá thời điểm 2 tháng kể từ bây giờ.',
            'end_date.after' => 'Ngày kết thúc phải sau ngày khởi chiếu 2 tuần',
            'end_date.before' => 'Ngày kết thúc không được quá ngày khởi chiếu 1 tháng',
        ];
    }
}
