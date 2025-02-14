<?php

namespace App\Http\Requests;

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
        return [
            'title' => 'required|min:5|max:255|unique:movies,title,' . $this->id,
            'description' => 'required|min:5|max:500',
            'poster' => 'mimes:jpeg,jpg,png',
            'trailer' => 'required',
            'duration' => 'required',
            'rating' => 'required',
            'release_date' => 'required',
            'genres' => 'required|array ',
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
        ];
    }
}
