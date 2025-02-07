<?php

namespace App\Http\Controllers;

use App\Models\Seat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SeatController extends Controller
{
    public function index() {
        $seats = Seat::select('id', 'row','number','type','price','status','screen_id')
        ->with(['screen:id,name'])
        ->get();
        return $this->responseCommon(200,"Lấy Danh Sách Thành Công",$seats);

    }

    public function store(Request $request) {
        // Kiểm tra và validate request
        $rules = $this->validateCreateSeats();
        $alert = $this->alertCreateSeats();
        $validator = Validator::make($request->all(), $rules, $alert);

        if ($validator->fails()) {
            return $this->responseError(422, 'Dữ liệu không hợp lệ.', $validator->errors());
        }

        try {
            // Kiểm tra xem Ghế có bị xóa mềm không
            $seat = Seat::withTrashed()->where('number', $request->number)->first();

            if ($seat) {
                // Nếu đã bị xóa mềm, khôi phục lại
                $seat->restore();
                $seat->update([
                    'row' => $request->row,
                    'number' => $request->number,
                    'type' => $request->type,
                    'price' => $request->price,
                    // 'status' => $request->status,
                    'screen_id' => $request->screen_id
                ]);

                return $this->responseCommon(200, "Ghế đã được khôi phục và cập nhật.", $seat);
            }

            $seat = Seat::create([
                'row' => $request->row,
                'number' => $request->number,
                'type' => $request->type,
                'price' => $request->price,
                // 'status' => $request->status,
                'screen_id' => $request->screen_id
            ]);

            return $this->responseCommon(201, "Thêm mới thành công.", $seat);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", [
                'error' => $e->getMessage()
            ]);
        }
    }


    public function update(Request $request,$id) {
        // dd($request->all());
        $seat = Seat::findOrFail($id);

        $rules = $this->validateUpdateSeats($id);
        $alert = $this->alertUpdateSeats();
        $validator = Validator::make($request->all(),$rules,$alert);

        if($validator->fails()) {
            return $this->responseError(422,'Dữ Liệu Không hợp lệ.',$validator->errors());
        }

        try {
            $seat->update([
                'row' => $request->row,
                'number' => $request->number,
                'type' => $request->type,
                'price' => $request->price,
                // 'status' => $request->status,
                'screen_id' => $request->screen_id
            ]);
            return $this->responseCommon(200,"Cập nhật thành công.",$seat);
        } catch (\Exception $e) {
            return $this->responseError(500,"Lỗi xử lý",[
                'error' => $e->getMessage()
            ]);
        }
    }

    public function show($id) {
        try {
            $seat = Seat::with('screen:id,name')->with('cinemas:id,name')->findOrFail($id);

            return $this->responseCommon(200,"Tìm Ghế thành công.",$seat);
        } catch (\Exception $e) {
            return $this->responseError(404,"Ghế Không tồn tại",[]);
        }
    }

    public function destroy($id) {
        try {
            $seat = Seat::with('screen:id,name')->findOrFail($id);
            $seat->delete();
            return $this->responseCommon(200,"Xóa Ghế thành công.",$seat);

        } catch (\Exception $e) {
            return $this->responseError(404,"Ghế Không tồn tại",[]);
        }
    }




    //Validate

    public function validateCreateSeats()
    {
        return [

            'row' =>'required|unique:seats,row,NULL,id,deleted_at,NULL',
            'number' =>'required|unique:seats,number,NULL,id,deleted_at,NULL',
            'type' =>'required' ,
            'price' =>'required' ,
            // 'status' =>'required' ,

        ];
    }

    public function alertCreateSeats()
    {
        return [
            'required' => 'Không được để trống.',
            'row.unique' => 'row không được trùng.',
            'number.unique' => 'Số ghế không được trùng.',

        ];
    }

    public function validateUpdateSeats($id)
{
    return [
        'row' => 'required|unique:seats,row,' . $id,
        'number' => 'required|unique:seats,number,' . $id,
        'type' =>'required' ,
        'price' =>'required' ,
        // 'status' =>'required' ,
    ];
}


    public function alertUpdateSeats()
    {
        return [
           'required' => 'Không được để trống.',
            'row.unique' => 'row không được trùng.',
            'number.unique' => 'Số ghế không được trùng.',
        ];
    }
}
