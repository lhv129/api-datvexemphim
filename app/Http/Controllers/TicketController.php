<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Models\Product;
use App\Models\Promo_code;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\Ticket;
use App\Models\TicketDetail;
use App\Models\TicketProductDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class TicketController extends Controller
{
    public function index()
    {
        $user = JWTAuth::user();
        $tickets = Ticket::with(['showtime.movie'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $tickets->map(function ($ticket) {
            $seats = $ticket->ticketDetails()->with('seat')->get();
            $seatList = $seats->map(fn($item) => $item->seat->row . $item->seat->number)->implode(',');
            return [
                'ticket_code' => $ticket->code,
                'movie_name' => $ticket->showtime->movie->title,
                'showtime' => $ticket->showtime->start_time,
                'seats' => $seatList,
                'total_amount' => number_format($ticket->total_amount, 0, ',', '.') . ' đ',
                'status' => $ticket->status
            ];
        });

        return $this->responseCommon(200, 'Lấy danh sách vé thành công.', $data);
    }

    public function store(StoreTicketRequest $request)
    {
        $user = JWTAuth::user();

        // Kiểm tra suất chiếu hết hạn chưa
        $showtime = Showtime::find($request->showtime_id);
        if (!$showtime) {
            return $this->responseError(404, 'Suất chiếu không tồn tại.');
        }
        if (now()->greaterThan($showtime->end_time)) {
            return $this->responseError(400, 'Suất chiếu đã kết thúc, không thể đặt vé.');
        }

        // Truy vấn danh sách ghế có trong phòng chiếu
        $seats = Seat::whereIn('id', $request->seat_ids)
            ->where('screen_id', $showtime->screen_id)
            ->get();

        // Kiểm tra ghế không hợp lệ
        $validSeatIds = $seats->pluck('id')->toArray();
        $invalidSeats = array_diff($request->seat_ids, $validSeatIds);

        if (!empty($invalidSeats)) {
            return $this->responseError(400, 'Ghế không hợp lệ hoặc không thuộc phòng chiếu: ' . implode(', ', $invalidSeats));
        }

        // kiểm tra ghế bị trùng trong suất chiếu
        $reservedSeats = DB::table('ticket_details')
            ->join('tickets', 'ticket_details.ticket_id', '=', 'tickets.id')
            ->where('tickets.showtime_id', $request->showtime_id)
            ->whereIn('ticket_details.seat_id', $request->seat_ids)
            ->pluck('ticket_details.seat_id')
            ->toArray();

        if (!empty($reservedSeats)) {
            return $this->responseError(400, 'Ghế đã được đặt: ' . implode(', ', $reservedSeats));
        }

        // $seats = Seat::whereIn('id', $request->seat_ids)->get();
        // if ($seats->count() != count($request->seat_ids)) {
        //     return $this->responseError(400, 'Một số ghế không hợp lệ.');
        // }

        $seatPrices = $seats->sum('price');
        $productPrices = $this->calculateProductPrices($request->products);
        $totalBeforeDiscount = $seatPrices + $productPrices;

        $discount = $this->calculateDiscount($request->promo_code_id, $totalBeforeDiscount);
        $totalAmount = $totalBeforeDiscount - $discount;

        $ticketCode = now()->format('Ymd') . random_int(100000, 999999);

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'showtime_id' => $request->showtime_id,
            'payment_method_id' => $request->payment_method_id,
            'promo_code_id' => $request->promo_code_id,
            'code' => $ticketCode,
            'total_amount' => $totalAmount,
            'discount_price' => $discount,
            'status' => 'pending'
        ]);

        $this->saveTicketDetails($ticket, $seats, $request->products);
        // Seat::whereIn('id', $request->seat_ids)->update(['status' => 'booked']);

        if ($request->payment_method_id == '1') {
            $paymentController = new PaymentMethodController();
            return $paymentController->createPayment(new Request(['ticket_id' => $ticket->id]));
        }

        // if (in_array($request->payment_method_id, [1, 2])) {
        //     $ticket->update(['status' => 'paid']);
        // }

        return $this->responseCommon(200, 'Tạo vé thành công.', $ticket);
    }

    public function show($id)
    {
        $user = JWTAuth::user();
        $ticket = Ticket::with(['ticketDetails.seat', 'ticketProductDetails.product', 'showtime.movie', 'promoCode'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$ticket) {
            return $this->responseError(404, 'Vé không tồn tại hoặc không thuộc về bạn.');
        }

        // Tính tổng tiền ghế
        $seatPrices = $ticket->ticketDetails->sum('price');

        // Tính tổng tiền sản phẩm
        $productPrices = $ticket->ticketProductDetails->sum(function ($productDetail) {
            return $productDetail->price;
        });

        $totalBeforeDiscount = $seatPrices + $productPrices;

        // Kiểm tra và tính giảm giá
        $discount = $ticket->discount_price ?? $this->calculateDiscount($ticket->promo_code_id, $totalBeforeDiscount);

        // Tính tổng tiền cuối cùng
        $finalAmount = $totalBeforeDiscount - $discount;

        $response = [
            'ticket_code' => $ticket->code,
            'movie_name' => $ticket->showtime->movie->title,
            'showtime' => $ticket->showtime->start_time,
            'screen' => $ticket->showtime->screen->name,
            'cinema' => $ticket->showtime->screen->cinema->name,
            'province' => $ticket->showtime->screen->cinema->province->name,
            'seats' => $ticket->ticketDetails->map(function ($detail) {
                return [
                    'seat_row' => $detail->seat->row,
                    'seat_number' => $detail->seat->number,
                    'price' => number_format($detail->price, 0, ',', '.') . ' đ'
                ];
            }),
            'products' => $ticket->ticketProductDetails->map(function ($productDetail) {
                return [
                    'product_name' => optional($productDetail->product)->name,
                    'quantity' => $productDetail->quantity,
                    'unit_price' => number_format($productDetail->price / $productDetail->quantity, 0, ',', '.') . ' đ',
                    'total_price' => number_format($productDetail->price, 0, ',', '.') . ' đ'
                ];
            }),
            'total_amount' => number_format($totalBeforeDiscount, 0, ',', '.') . ' đ',
            'promo_code' => optional($ticket->promoCode)->code ?? null,
            'discount' => number_format($discount, 0, ',', '.') . ' đ',
            'final_amount' => number_format($finalAmount, 0, ',', '.') . ' đ',
            'status' => $ticket->status
        ];

        return $this->responseCommon(200, 'Lấy chi tiết vé thành công.', $response);
    }


    private function calculateProductPrices($products)
    {
        $productPrices = 0;
        if ($products) {
            foreach ($products as $product) {
                $productInfo = Product::find($product['product_id']);
                $productPrices += $productInfo->price * $product['quantity'];
            }
        }
        return $productPrices;
    }

    private function calculateDiscount($promo_code_id, $totalAmount)
    {
        $discount = 0;

        if ($promo_code_id) {
            $promo = Promo_code::find($promo_code_id);
            if ($promo && $promo->status === 'active') {
                $today = now()->toDateString();

                // Kiểm tra thời gian hiệu lực
                if ($promo->start_date <= $today && $promo->end_date >= $today) {
                    $discount = $promo->discount_amount;
                }
            }
        }

        return $discount;
    }


    private function saveTicketDetails($ticket, $seats, $products)
    {
        foreach ($seats as $seat) {
            TicketDetail::create([
                'ticket_id' => $ticket->id,
                'seat_id' => $seat->id,
                'price' => $seat->price
            ]);
        }

        if ($products) {
            foreach ($products as $product) {
                $productInfo = Product::find($product['product_id']);
                TicketProductDetail::create([
                    'ticket_id' => $ticket->id,
                    'product_id' => $product['product_id'],
                    'quantity' => $product['quantity'],
                    'price' => $productInfo->price * $product['quantity']
                ]);
            }
        }
    }

    public function responseCommon($status, $message, $data)
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    public function responseError($status, $message, $errors = [])
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }

    //admin
    public function adminIndex()
    {
        $tickets = Ticket::with(['user', 'showtime.movie'])
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $tickets->map(function ($ticket) {
            return [
                'ticket_id' => $ticket->id,
                'ticket_code' => $ticket->code,
                'user' => $ticket->user->name ?? 'Unknown', // Hiển thị tên người dùng
                'movie_name' => $ticket->showtime->movie->title,
                'showtime' => $ticket->showtime->start_time,
                'total_amount' => number_format($ticket->total_amount, 0, ',', '.') . ' đ',
                'status' => $ticket->status,
                'created_at' => $ticket->created_at->format('Y-m-d H:i:s')
            ];
        });

        return $this->responseCommon(200, 'Lấy danh sách vé thành công (Admin).', $data);
    }

    public function adminShow($id)
    {
        $ticket = Ticket::with(['ticketDetails.seat', 'ticketProductDetails.product', 'showtime.movie', 'promoCode'])
            ->find($id);

        if (!$ticket) {
            return $this->responseError(404, 'Vé không tồn tại.');
        }

        // Tính tổng giá trước khi giảm giá
        $seatPrices = $ticket->ticketDetails->sum('price');
        $productPrices = $ticket->ticketProductDetails->sum('price');
        $totalBeforeDiscount = $seatPrices + $productPrices;

        // Kiểm tra và tính lại giảm giá
        $discount = $ticket->discount_price ?? $this->calculateDiscount($ticket->promo_code_id, $totalBeforeDiscount);

        // Tính tổng tiền sau giảm giá
        $finalAmount = $totalBeforeDiscount - $discount;

        // Chuẩn bị dữ liệu phản hồi
        $response = [
            'ticket_code' => $ticket->code,
            'movie_name' => $ticket->showtime->movie->title,
            'showtime' => $ticket->showtime->start_time,
            'screen' => $ticket->showtime->screen->name,
            'cinema' => $ticket->showtime->screen->cinema->name,
            'province' => $ticket->showtime->screen->cinema->province->name,
            'seats' => $ticket->ticketDetails->map(function ($detail) {
                return [
                    'seat_row' => $detail->seat->row,
                    'seat_number' => $detail->seat->number,
                    'price' => number_format($detail->price, 0, ',', '.') . ' đ'
                ];
            }),
            'products' => $ticket->ticketProductDetails->map(function ($productDetail) {
                return [
                    'product_name' => optional($productDetail->product)->name,
                    'quantity' => $productDetail->quantity,
                    'unit_price' => number_format($productDetail->product->price, 0, ',', '.') . ' đ',
                    'total_price' => number_format($productDetail->price, 0, ',', '.') . ' đ'
                ];
            }),
            'total_amount' => number_format($totalBeforeDiscount, 0, ',', '.') . ' đ',
            'promo_code' => optional($ticket->promoCode)->code ?? null,
            'discount' => number_format($discount, 0, ',', '.') . ' đ',
            'final_amount' => number_format($finalAmount, 0, ',', '.') . ' đ',
            'status' => $ticket->status,
            'created_at' => $ticket->created_at->format('Y-m-d H:i:s')
        ];

        return $this->responseCommon(200, 'Lấy chi tiết vé cho admin thành công.', $response);
    }

    public function checkTicket(Request $request)
    {
        // Lấy barcode từ body
        $barcode = $request->input('barcode');

        if (!$barcode) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mã vạch không được để trống'
            ], 400);
        }

        // Tìm vé theo mã vạch
        $ticket = Ticket::where('code', $barcode)->first();

        if (!$ticket) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vé không tồn tại'
            ], 404);
        }

        return redirect()->route('adminShow', ['id' => $ticket->id]);

    }


    public function confirmTicketUsage(Request $request)
    {
        $ticket = Ticket::find($request->ticket_id);

        if (!$ticket) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy vé!'], 404);
        }

        if ($ticket->status === 'used') {
            return response()->json(['status' => 'error', 'message' => 'Vé đã được sử dụng!'], 400);
        }

        // Cập nhật trạng thái vé
        $ticket->update(['status' => 'used']);

        return response()->json(['status' => 'success', 'message' => 'Vé đã được xác nhận và sử dụng!']);
    }



}
