<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreSeatRequest;
use App\Http\Requests\UpdateSeatRequest;
use App\Models\Screen;
use App\Models\Seat;
use App\Models\Showtime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SeatController extends Controller
{
    public function index(Request $request) {
        $seats = Seat::select('id', 'row','number','type','price','status','screen_id')
            ->with(['screen:id,name'])
            // ->where('screen_id',$request->screen_id)
            ->get();
        return $this->responseCommon(200, "Lấy Danh Sách Thành Công", $seats);
    }

    public function getAllByScreenId(Request $request) {
        $screen = Screen::find($request->screen_id);
        if(!$screen) {
            return $this->responseCommon(404,"Phòng không tồn tại.",[]);
        }
        $seats = Seat::select('id', 'row','number','type','price','status','screen_id')
            ->with(['screen:id,name'])
            ->where('screen_id',$request->screen_id)
            ->get();
        return $this->responseCommon(200, "Lấy Danh Sách Thành Công", $seats);
    }


    public function getSeatsByShowtime(Request $request)
    {
        $showtime = Showtime::find($request->showtime_id);

        if (!$showtime) {
            return response()->json(["message" => "Suất chiếu không tồn tại"], 404);
        }

        // Lấy danh sách ghế đã đặt kèm ticket_id
        $reservedSeats = DB::table('ticket_details')
            ->join('tickets', 'ticket_details.ticket_id', '=', 'tickets.id')
            ->where('tickets.showtime_id', $request->showtime_id)
            ->select('ticket_details.seat_id', 'tickets.id as ticket_id')
            ->get();

        // Chuyển danh sách ghế đã đặt thành mảng [seat_id => ticket_id]
        $reservedSeatsMap = $reservedSeats->pluck('ticket_id', 'seat_id')->toArray();

        return response()->json([
            "message" => "Lấy danh sách ghế đã đặt thành công",
            "data" => collect($reservedSeatsMap)->map(function ($ticket_id, $seat_id) use ($request) {
                return [
                    "id" => null,
                    "showtime_id" => $request->showtime_id,
                    "seat_id" => $seat_id,
                    "ticket_id" => $ticket_id
                ];
            })->values()
        ], 200);
    }



    public function store(StoreSeatRequest $request)
    {
        try {
            $screen_id = $request->input('screen_id');
            $row = strtoupper($request->input('row'));
            $seat_count = $request->input('number');
            $type = $request->input('type', 'Ghế Thường');
            $price = $request->input('price');
            // $status = $request->input('status');

            $seats = [];
            for ($i = 1; $i <= $seat_count; $i++) {
                $seat_number = str_pad($i, 2, '0', STR_PAD_LEFT);

                // Kiểm tra xem ghế đã tồn tại chưa
                $existingSeat = Seat::where([
                    ['screen_id', '=', $screen_id],
                    ['row', '=', $row],
                    ['number', '=', $seat_number],
                    ['deleted_at', '=', null],
                ])->exists();

                if (!$existingSeat) {
                    $seats[] = [
                        'screen_id' => $screen_id,
                        'row' => $row,
                        'number' => $seat_number,
                        'type' => $type,
                        'price' => $price,
                        // 'status' => $status,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($seats)) {
                Seat::insert($seats);
                return $this->responseCommon(201, "Thêm " . count($seats) . " ghế thành công.", $seats);
            }
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", [
                'error' => $e->getMessage()
            ]);
        }
    }



    public function update(UpdateSeatRequest $request, $id) {
        try {
            $seat = Seat::where('id', $id)->whereNull('deleted_at')->first();

            if (!$seat) {
                return $this->responseCommon(404, "Ghế không tồn tại hoặc đã bị xóa.", []);
            }

            $seat->update($request->validated());
            return $this->responseCommon(200, "Cập nhật thành công.", $seat);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý", [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function show($id) {
        try {
            $seat = Seat::with('screen:id,name')->where('id', $id)->whereNull('deleted_at')->first();

            if (!$seat) {
                return $this->responseCommon(404, "Ghế không tồn tại hoặc đã bị xóa.", []);
            }

            return $this->responseCommon(200, "Tìm Ghế thành công.", $seat);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý", [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function destroy($id) {
        try {
            $seat = Seat::where('id', $id)->whereNull('deleted_at')->first();

            if (!$seat) {
                return $this->responseCommon(404, "Ghế không tồn tại hoặc đã bị xóa.", []);
            }

            $seat->delete();
            return $this->responseCommon(200, "Xóa Ghế thành công.", []);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý", [
                'error' => $e->getMessage()
            ]);
        }
    }
}
