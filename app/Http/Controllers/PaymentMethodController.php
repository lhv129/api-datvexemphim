<?php

namespace App\Http\Controllers;

use App\Mail\TicketMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Ticket;
use Illuminate\Support\Facades\Mail;
use Milon\Barcode\DNS1D;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentMethodController extends Controller
{
    public function createPayment(Request $request)
    {
        $ticket = Ticket::find($request->ticket_id);
        if (!$ticket) {
            return response()->json(['message' => 'Vé không tồn tại'], 404);
        }

        $vnp_TmnCode = env('VNP_TMN_CODE');
        $vnp_HashSecret = env('VNP_HASH_SECRET');
        $vnp_Url = env('VNP_URL');
        $vnp_Returnurl = env('VNP_RETURN_URL');
        $vnp_TxnRef = $ticket->code;
        $vnp_OrderInfo = "Thanh toán vé xem phim";
        $vnp_OrderType = "billpayment";
        $vnp_Amount = $ticket->total_amount * 100;
        $vnp_Locale = "vn";
        $vnp_BankCode = "";
        $vnp_IpAddr = request()->ip();

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => now()->format('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef
        ];

        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&';
                $query .= '&';
            }
            $hashdata .= urlencode($key) . '=' . urlencode($value);
            $query .= urlencode($key) . '=' . urlencode($value);
            $i = 1;
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            $vnp_Url .= '&vnp_SecureHash=' . $vnpSecureHash;
        }

        return response()->json(['payment_url' => $vnp_Url]);
    }

    public function vnpayCallback(Request $request)
    {
        $inputData = $request->all();
        $ticket = Ticket::where('code', $inputData['vnp_TxnRef'])->first();

        if ($ticket) {
            if ($inputData['vnp_ResponseCode'] == '00') {
                $ticket->update(['status' => 'paid']);

                $showtime = $ticket->showtime;
                $cinema = $showtime->screen->cinema;
                $seats = $ticket->ticketDetails()->with('seat')->get();
                $seatList = $seats->pluck('seat.seat_code')->implode(', ');
                $total_price = number_format($ticket->total_amount, 0, ',', '.') . 'đ';

                $products = $ticket->ticketProductDetails()->with('product')->get();
                $productList = $products->map(function ($item) {
                    return $item->product->name . ': ' . $item->quantity . ' x ' . number_format($item->product->price, 0, ',', '.') . 'đ';
                })->implode(', ');

                $barcodeGenerator = new DNS1D();
                $barcodeData = $barcodeGenerator->getBarcodePNG($ticket->code, "C128", 2.5, 80, [0, 0, 0], true);

                $barcodeName = $ticket->code . '.png';
                $barcodeDirectory = 'images/tickets/barcodes/';
                $barcodePath = public_path($barcodeDirectory . $barcodeName);

                if (!file_exists(public_path($barcodeDirectory))) {
                    mkdir(public_path($barcodeDirectory), 0777, true);
                }

                file_put_contents($barcodePath, base64_decode($barcodeData));

                $barcodeUrl = public_path($barcodeDirectory . $barcodeName);

                $emailData = [
                    'ticket_code' => $ticket->code,
                    'movie_name' => $showtime->movie->title,
                    'cinema_name' => $cinema->name,
                    'screen_name' => $showtime->screen->name,
                    'show_time' => \Carbon\Carbon::parse($showtime->start_time)->format('d/m/Y H:i'),
                    'seats' => $seatList,
                    'price' => count($seats) . ' x ' . number_format($ticket->ticketDetails->first()->price, 0, ',', '.') . 'đ',
                    'total_amount' => $total_price,
                    'user_email' => $ticket->user->email,
                    'promotion' => number_format($ticket->discount_price, 0, ',', '.') . 'đ',
                    'products' => $productList,
                    'barcode_url' => $barcodeUrl,
                ];

                Mail::to($emailData['user_email'])->send(new TicketMail($emailData));

                return response()->json([
                    'status' => 'success',
                    'redirect_url' => env('FRONTEND_URL') . '/?payment=success'
                ]);
            } else {
                $ticket->ticketDetails->each->delete();
                $ticket->ticketProductDetails->each->delete();
                $ticket->delete();
                return response()->json([
                    'status' => 'success',
                    'redirect_url' => env('FRONTEND_URL') . '/?payment=failed'
                ]);
            }
        }
        return response()->json(['message' => 'Dữ liệu không hợp lệ'], 400);
    }

}
