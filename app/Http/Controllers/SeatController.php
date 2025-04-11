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
            ->join('seats','ticket_details.seat_id','=','seats.id' )
            ->where('tickets.showtime_id', $request->showtime_id)
            ->where('seats.status','available')
            ->where('tickets.status',['paid','used'])
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
            $couple_count = (int)$request->input('number'); // Số cặp ghế đôi
            $type = strtolower($request->input('type', 'normal'));
            $price = $request->input('price');

            $seats = [];

            // Kiểm tra nếu là couple thì số ghế phải là số cặp
            if ($type === 'couple') {
                for ($i = 0; $i < $couple_count; $i++) {
                    $seat_number_1 = ($i * 2) + 1;
                    $seat_number_2 = $seat_number_1 + 1;
                    $seat_number = $seat_number_1 . ' ' . $seat_number_2;
                    $seat_code = $row . $seat_number_1 . ' ' . $row . $seat_number_2; // Ví dụ: H1H2

                    $existingSeat = Seat::where('screen_id', $screen_id)
                        ->where('seat_code', $seat_code)
                        ->whereNull('deleted_at')
                        ->exists();

                    if (!$existingSeat) {
                        $seats[] = [
                            'screen_id' => $screen_id,
                            'row' => $row,
                            'number' => $seat_number,
                            'type' => ucfirst($type),
                            'price' => $price,
                            'seat_code' => $seat_code,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            } else {
                // Ghế thường/VIP
                for ($i = 1; $i <= $couple_count; $i++) {
                    $seat_number = $i;
                    $seat_code = $row . $seat_number;

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
                            'type' => ucfirst($type),
                            'price' => $price,
                            'seat_code' => $seat_code,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }

            if (!empty($seats)) {
                Seat::insert($seats);
                return $this->responseCommon(201, "Thêm " . count($seats) . " ghế thành công.", $seats);
            } else {
                return $this->responseError(409, "Tất cả các ghế đã tồn tại.",[]);
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
