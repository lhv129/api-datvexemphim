<?php

namespace App\Http\Controllers;

use App\Models\Showtime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShowtimeController extends Controller
{
    public function index() {
        $showtimes = Showtime::select('id', 'start_time', 'end_time', 'movie_id','screen_id')
        ->with(['screen:id,name'])
        ->with(['movie:id,title'])
        ->get();
        return $this->responseCommon(200,"Lấy Danh Sách Thành Công",$showtimes);

    }

    public function store(Request $request) {
        // Kiểm tra và validate request
        $rules = $this->validateCreateShowtime();
        $alert = $this->alertCreateShowtime();
        $validator = Validator::make($request->all(), $rules, $alert);

        if ($validator->fails()) {
            return $this->responseError(422, 'Dữ liệu không hợp lệ.', $validator->errors());
        }

        try {
            $showtime = Showtime::create([
                'movie_id' => $request->movie_id,
                'screen_id' => $request->screen_id,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'date' => $request->date,

            ]);

            return $this->responseCommon(201, "Thêm mới thành công.", $showtime);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", [
                'error' => $e->getMessage()
            ]);
        }
    }


    public function update(Request $request,$id) {
        // dd($request->all());
        $showtime = Showtime::findOrFail($id);

        $rules = $this->validateUpdateShowtime($id);
        $alert = $this->alertUpdateShowtime();
        $validator = Validator::make($request->all(),$rules,$alert);

        if($validator->fails()) {
            return $this->responseError(422,'Dữ Liệu Không hợp lệ.',$validator->errors());
        }

        try {
            $showtime->update([
                'movie_id' => $request->movie_id,
                'screen_id' => $request->screen_id,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'date' => $request->date,
            ]);
            return $this->responseCommon(200,"Cập nhật thành công.",$showtime);
        } catch (\Exception $e) {
            return $this->responseError(500,"Lỗi xử lý",[
                'error' => $e->getMessage()
            ]);
        }
    }

    public function show($id) {
        try {
            $showtime = Showtime::with(['screen:id,name'])
                                ->with(['movie:id,title'])
                                ->findOrFail($id);

            return $this->responseCommon(200,"Tìm Giờ Chiếu thành công.",$showtime);
        } catch (\Exception $e) {
            return $this->responseError(404,"Giờ Chiếu Không tồn tại",[]);
        }
    }

    public function destroy($id) {
        try {
            $showtime = Showtime::with(['screen:id,name'])
                                ->with(['movie:id,title'])
                                ->findOrFail($id);

            $showtime->delete();
            return $this->responseCommon(200,"Xóa Giờ Chiếu thành công.",$showtime);

        } catch (\Exception $e) {
            return $this->responseError(404,"Giờ Chiếu Không tồn tại",[]);
        }
    }




    //Validate

    public function validateCreateShowtime()
    {
        return ([
            'movie_id' => 'required|exists:movies,id', // ID phim phải tồn tại
            'screen_id' => 'required|exists:screens,id', // ID màn hình phải tồn tại
            'start_time' => 'required|date_format:Y-m-d H:i:s|after:now', // Định dạng đúng, phải lớn hơn thời gian hiện tại
            'end_time' => 'required|date_format:Y-m-d H:i:s|after:start_time', // Phải sau start_time
            'date' => 'required|date_format:Y-m-d|after_or_equal:today', // Ngày chiếu không được là quá khứ
        ]);
    }

    public function alertCreateShowtime()
    {
        return [
            'movie_id.required' => 'Vui lòng chọn phim.',
            'movie_id.exists' => 'Phim không tồn tại.',
            'screen_id.required' => 'Vui lòng chọn màn hình chiếu.',
            'screen_id.exists' => 'Màn hình chiếu không tồn tại.',
            'start_time.required' => 'Vui lòng nhập thời gian bắt đầu.',
            'start_time.date_format' => 'Thời gian bắt đầu không đúng định dạng (YYYY-MM-DD HH:MM:SS).',
            'start_time.after' => 'Thời gian bắt đầu phải lớn hơn thời gian hiện tại.',
            'end_time.required' => 'Vui lòng nhập thời gian kết thúc.',
            'end_time.date_format' => 'Thời gian kết thúc không đúng định dạng (YYYY-MM-DD HH:MM:SS).',
            'end_time.after' => 'Thời gian kết thúc phải sau thời gian bắt đầu.',
            'date.required' => 'Vui lòng chọn ngày chiếu.',
            'date.date_format' => 'Ngày chiếu phải đúng định dạng (YYYY-MM-DD).',
            'date.after_or_equal' => 'Ngày chiếu phải là hôm nay hoặc sau hôm nay.',
        ];
    }

    public function validateUpdateShowtime($id)
{
    return ([
        'movie_id' => 'required|exists:movies,id', // ID phim phải tồn tại
        'screen_id' => 'required|exists:screens,id', // ID màn hình phải tồn tại
        'start_time' => 'required|date_format:Y-m-d H:i:s|after:now', // Định dạng đúng, phải lớn hơn thời gian hiện tại
        'end_time' => 'required|date_format:Y-m-d H:i:s|after:start_time', // Phải sau start_time
        'date' => 'required|date_format:Y-m-d|after_or_equal:today', // Ngày chiếu không được là quá khứ
    ]);
}


    public function alertUpdateShowtime()
    {
        return [
            'movie_id.required' => 'Vui lòng chọn phim.',
            'movie_id.exists' => 'Phim không tồn tại.',
            'screen_id.required' => 'Vui lòng chọn màn hình chiếu.',
            'screen_id.exists' => 'Màn hình chiếu không tồn tại.',
            'start_time.required' => 'Vui lòng nhập thời gian bắt đầu.',
            'start_time.date_format' => 'Thời gian bắt đầu không đúng định dạng (YYYY-MM-DD HH:MM:SS).',
            'start_time.after' => 'Thời gian bắt đầu phải lớn hơn thời gian hiện tại.',
            'end_time.required' => 'Vui lòng nhập thời gian kết thúc.',
            'end_time.date_format' => 'Thời gian kết thúc không đúng định dạng (YYYY-MM-DD HH:MM:SS).',
            'end_time.after' => 'Thời gian kết thúc phải sau thời gian bắt đầu.',
            'date.required' => 'Vui lòng chọn ngày chiếu.',
            'date.date_format' => 'Ngày chiếu phải đúng định dạng (YYYY-MM-DD).',
            'date.after_or_equal' => 'Ngày chiếu phải là hôm nay hoặc sau hôm nay.',
        ];
    }

}

