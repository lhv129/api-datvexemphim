<?php

namespace App\Http\Controllers;

use App\Mail\TicketMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Ticket;
use Illuminate\Support\Facades\Mail;

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
        // $secureHash = env('VNP_HASH_SECRET');

        $vnp_SecureHash = $inputData['vnp_SecureHash'];
        // unset($inputData['vnp_SecureHash']);
        // ksort($inputData);
        // $hashData = "";
        // foreach ($inputData as $key => $value) {
        //     $hashData .= '&' . $key . '=' . $value;
        // }
        // $hashData = ltrim($hashData, '&');
        // $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);


        // if ($secureHash === $vnp_SecureHash) {
        $ticket = Ticket::where('code', $inputData['vnp_TxnRef'])->first();
        if ($ticket) {
            if ($inputData['vnp_ResponseCode'] == '00') {
                $ticket->update(['status' => 'paid']);
                $userEmail = $ticket->user->email ?? null;
                if ($userEmail) {
                    Mail::to($userEmail)->send(new TicketMail($ticket));
                }
                return response()->json(['message' => 'Thanh toán thành công'], 200);

            } else {
                return response()->json(['message' => 'Thanh toán không thành công'], 400);
            }
        }
        // }

        return response()->json(['message' => 'Dữ liệu không hợp lệ'], 400);
    }
}
