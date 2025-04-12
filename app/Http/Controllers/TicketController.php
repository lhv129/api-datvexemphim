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
use Carbon\Carbon;
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
            $seatList = $seats->pluck('seat.seat_code')->implode(', ');
            return [
                'ticket_id' => $ticket->id,
                'ticket_code' => $ticket->code,
                'movie_name' => $ticket->showtime->movie->title,
                'showtime' => $ticket->showtime->start_time,
                'seats' => $seatList,
                'total_amount' => number_format($ticket->total_amount, 0, ',', '.'),
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

        $startTime = Carbon::parse($showtime->start_time);

        if (now()->greaterThan($startTime->addMinutes(30))) {
            return $this->responseError(400, 'Suất chiếu đã bắt đầu hơn 30 phút. Không thể đặt vé.');
        }

        // Truy vấn danh sách ghế có trong phòng chiếu
        $seats = Seat::whereIn('id', $request->seat_ids)
            ->where('screen_id', $showtime->screen_id)
            ->where('status', 'available') // Chỉ lấy ghế đang hoạt động
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
            ->whereIn('tickets.status',['paid','used','pending'])
            ->whereIn('ticket_details.seat_id', $request->seat_ids)
            ->pluck('ticket_details.seat_id')
            ->toArray();

        if (!empty($reservedSeats)) {
            return $this->responseError(400, 'Ghế đã được đặt: ' . implode(', ', $reservedSeats));
        }
        //check ghế đôi
        // foreach ($seats as $seat) {
        //     // Chỉ kiểm tra nếu là ghế đôi
        //     if ($seat->type === 'Ghế đôi') {
        //         // Xác định số ghế còn lại trong cặp
        //         if ($seat->number % 2 == 0) {
        //             $pairNumber = $seat->number - 1; // chẵn → lẻ trước
        //         } else {
        //             $pairNumber = $seat->number + 1; // lẻ → chẵn sau
        //         }

        //         // Tìm ghế đôi còn lại
        //         $pairedSeat = Seat::where('row', $seat->row)
        //             ->where('number', $pairNumber)
        //             ->where('screen_id', $seat->screen_id)
        //             ->where('status', 'available')
        //             ->first();

        //         // Nếu ghế đôi còn lại tồn tại mà không nằm trong danh sách ghế được chọn thì báo lỗi
        //         if ($pairedSeat && !in_array($pairedSeat->id, $request->seat_ids)) {
        //             return $this->responseError(
        //                 400,
        //                 "Ghế {$seat->row}{$seat->number} là ghế đôi, bạn phải đặt kèm với ghế {$pairedSeat->row}{$pairedSeat->number}."
        //             );
        //         }
        //     }
        // }

        //check 2 ghế cách nhau
        $normalSeats = $seats->filter(fn($seat) => $seat->type !== 'Ghế đôi');
        $groupedSeats = $normalSeats->groupBy('row');

        foreach ($groupedSeats as $row => $rowSeats) {
            $selectedNumbers = $rowSeats->pluck('number')->map(fn($num) => (int) $num)->sort()->values()->toArray();

            if (count($selectedNumbers) > 1) {
                $first = $selectedNumbers[0];
                $last = end($selectedNumbers);

                // Chỉ kiểm tra nếu khoảng cách giữa ghế đầu và cuối nhỏ hơn hoặc bằng 2
                if (($last - $first) <= 2) {
                    $expected = range($first, $last);

                    if ($selectedNumbers !== $expected) {
                        $missing = array_diff($expected, $selectedNumbers);
                        $missingText = implode(', ', array_map(fn($num) => $row . $num, $missing));

                        return $this->responseError(400, "Không được để trống ghế ở giữa: $missingText.");
                    }
                }
            }
            // Lấy toàn bộ ghế trong hàng từ DB
            $allSeats = Seat::where('row', $row)
                ->where('screen_id', $showtime->screen_id)
                ->where('type', '!=', 'Ghế đôi') // bỏ ghế đôi luôn từ DB
                ->get()
                ->sortBy(fn($seat) => (int) $seat->number)
                ->values();

            if ($allSeats->isEmpty())
                continue;

            // Tạo mảng số ghế đầy đủ
            $allNumbers = $allSeats->pluck('number')->map(fn($n) => (int) $n)->values()->toArray();

            $minSeat = $allNumbers[0];
            $maxSeat = end($allNumbers);

            // Check ghế đầu: nếu chọn ghế kế bên mà không chọn ghế đầu
            if (in_array($minSeat + 1, $selectedNumbers) && !in_array($minSeat, $selectedNumbers)) {
                return $this->responseError(400, "Không được bỏ trống ghế đầu hàng: $row$minSeat.");
            }

            // Check ghế cuối: nếu chọn ghế sát cuối mà không chọn ghế cuối
            if (in_array($maxSeat - 1, $selectedNumbers) && !in_array($maxSeat, $selectedNumbers)) {
                return $this->responseError(400, "Không được bỏ trống ghế cuối hàng: $row$maxSeat.");
            }

        }



        // $seats = Seat::whereIn('id', $request->seat_ids)->get();
        // if ($seats->count() != count($request->seat_ids)) {
        //     return $this->responseError(400, 'Một số ghế không hợp lệ.');
        // }

        $seatPrices = $seats->sum('price');

        // Tăng giá vé nếu suất chiếu vào thứ 7 hoặc Chủ nhật
        $dayOfWeek = Carbon::parse($showtime->date)->dayOfWeek;
        if ($dayOfWeek === Carbon::SATURDAY || $dayOfWeek === Carbon::SUNDAY) {
            foreach ($seats as $seat) {
                if ($seat->type === 'Ghế đôi') {
                    $seatPrices += 20000;
                } else {
                    $seatPrices += 10000;
                }
            }
        }

        try {
            $productPrices = $this->calculateProductPrices($request->products);
        } catch (\Exception $e) {
            return $this->responseError(400, $e->getMessage());
        }
        $totalBeforeDiscount = $seatPrices + $productPrices;

        $discount = $this->calculateDiscount($request->promo_code_id, $totalBeforeDiscount);
        //Giới hạn mã giảm giá chỉ được giảm tối đa 50% giá vé
        $maxDiscount = $totalBeforeDiscount * 0.5;
        $discount = min($discount, $maxDiscount);
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

        $seatPrices = $ticket->ticketDetails->sum('price');
        $productPrices = $ticket->ticketProductDetails->sum('price');
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
                    'seat_code' => $detail->seat->seat_code,
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
            'seat_price' => number_format($seatPrices, 0, ',', '.') . ' đ',
            'product_price' => number_format($productPrices, 0, ',', '.') . ' đ',
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
                $productInfo = Product::withTrashed()->find($product['product_id']);

                if (!$productInfo || $productInfo->trashed()) {
                    throw new \Exception("Sản phẩm ID {$product['product_id']} đã bị xóa, không thể đặt.");
                }

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
        $dayOfWeek = \Carbon\Carbon::parse($ticket->showtime->date)->dayOfWeek;

        foreach ($seats as $seat) {
            $price = $seat->price;

            // Áp dụng phụ thu nếu là thứ 7 hoặc Chủ nhật
            if ($dayOfWeek === \Carbon\Carbon::SATURDAY || $dayOfWeek === \Carbon\Carbon::SUNDAY) {
                if ($seat->type === 'Ghế đôi') {
                    $price += 20000;
                } else {
                    $price += 10000;
                }
            }
            TicketDetail::create([
                'ticket_id' => $ticket->id,
                'seat_id' => $seat->id,
                'price' => $price
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
    public function adminIndex(Request $request)
    {
        $query = Ticket::with(['user', 'showtime.movie'])
            ->orderBy('created_at', 'desc');

        // Nếu có truyền tham số 'date', thì lọc theo ngày của suất chiếu
        if ($request->has('date')) {
            $query->whereHas('showtime', function ($q) use ($request) {
                $q->whereDate('start_time', $request->date);
            });
        }

        $tickets = $query->get();

        $data = $tickets->map(function ($ticket) {
            return [
                'ticket_id' => $ticket->id,
                'ticket_code' => $ticket->code,
                'user_id' => $ticket->user->id ?? 'Unknown',
                'user_name' => $ticket->user->name ?? 'Unknown',
                'movie_name' => $ticket->showtime->movie->title,
                'showtime' => $ticket->showtime->start_time,
                'total_amount' => number_format($ticket->total_amount, 0, ',', '.'),
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
            'ticket_id' => $ticket->id,
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
                    'seat_code' => $detail->seat->seat_code,
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
            'seat_price' => number_format($seatPrices, 0, ',', '.') . ' đ',
            'product_price' => number_format($productPrices, 0, ',', '.') . ' đ',
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


        $today = Carbon::today()->toDateString();

        $ticket = Ticket::where('code', $barcode)
            ->whereHas('showtime', function ($query) use ($today) {
                $query->whereDate('date', $today);
            })
            ->first();

        if (!$ticket) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vé không tồn tại hoặc không áp dụng cho hôm nay'
            ], 404);
        }

        if (!$ticket) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vé không tồn tại'
            ], 404);
        }

        if ($ticket->status === 'used') {
            return response()->json([
                'status' => 'error',
                'message' => 'Vé đã được sử dụng!'
            ], 400);
        }

        if ($ticket->status === 'expired') {
            return response()->json([
                'status' => 'error',
                'message' => 'Vé đã hết hạn, suất chiếu đã kết thúc!'
            ], 400);
        }

        // Cập nhật trạng thái vé
        $ticket->update(['status' => 'used']);

        return redirect()->route('adminShow', ['id' => $ticket->id])
            ->with('success', 'Vé đã được quét và cập nhật trạng thái thành công!');

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
