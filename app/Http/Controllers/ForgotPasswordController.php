<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PasswordResetToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendTokenForgotPasswordEmail;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\RestorePasswordRequest;

class ForgotPasswordController extends Controller
{
    public function sendToken(ForgotPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if ($user->status === 'inactive') {
            return $this->responseCommon(401, 'Tài khoản của bạn đã bị khóa.', []);
        }
        $tokenCode = Str::random(5) . rand(1, 3);
        $expiresAt = Carbon::now()->addMinutes(10);

        //Check token forgot password
        $token = PasswordResetToken::where('email', $request->email)->first();
        // Kiểm tra thời gian hết hạn
        if ($token) {
            // Kiểm tra thời gian hết hạn
            if (now()->gt($token->expires_at)) {
                // Token đã hết hạn, xóa token cũ
                PasswordResetToken::where('email', $request->email)->delete();

                // Tạo token mới
                $token = PasswordResetToken::create([
                    'email' => $request->email,
                    'token' => $tokenCode,
                    'created_at' => now(),
                    'expires_at' => $expiresAt,
                ]);

                // Gửi email mới
                try {
                    Mail::to($request->email)->send(new SendTokenForgotPasswordEmail($token));
                    return $this->responseCommon(200, 'Mã đặt lại mật khẩu mới đã được gửi đến email của bạn.', []);
                } catch (\Exception $e) {
                    return $this->responseError(500, 'Lỗi gửi email: ' . $e->getMessage(), []);
                }
            } else {
                // Token chưa hết hạn, thông báo email đã gửi
                return $this->responseCommon(200, 'Mã đặt lại mật khẩu đã được gửi đến email của bạn trước đó.', []);
            }
        } else {
            // Không tìm thấy token, tạo token mới
            $token = PasswordResetToken::create([
                'email' => $request->email,
                'token' => $tokenCode,
                'created_at' => now(),
                'expires_at' => $expiresAt,
            ]);

            // Gửi email mới
            try {
                Mail::to($request->email)->send(new SendTokenForgotPasswordEmail($token));
                return $this->responseCommon(200, 'Mã đặt lại mật khẩu mới đã được gửi đến email của bạn.', []);
            } catch (\Exception $e) {
                return $this->responseError(500, 'Lỗi gửi email: ' . $e->getMessage(), []);
            }
        }
    }

    public function restorePassword(RestorePasswordRequest $request)
    {
        // Kiểm tra mã xác nhận
        $token = PasswordResetToken::where('email', $request->email)->where('token', $request->token)->first();

        if (!$token) {
            return $this->responseError(400, 'Mã xác nhận không hợp lệ.',[]);
        }

        // Kiểm tra thời gian hết hạn của mã
        if (Carbon::now()->gt($token->expires_at)) {
            PasswordResetToken::where('email', $request->email)->where('token', $request->token)->delete();
            return $this->responseError(400, 'Mã xác nhận đã hết hạn.',[]);
        }

        // Tìm người dùng theo email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->responseError(404, 'Người dùng không tồn tại.',[]);
        }

        // Cập nhật mật khẩu mới
        $user->password = Hash::make($request->password);
        $user->save();

        // Xóa token sau khi sử dụng
        PasswordResetToken::where('email', $request->email)->where('token', $request->token)->delete();

        return $this->responseCommon(200, 'Mật khẩu đã được đặt lại thành công.',[]);
    }
}
