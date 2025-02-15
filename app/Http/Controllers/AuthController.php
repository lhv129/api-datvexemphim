<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Auth\Notifications\ResetPassword;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'refresh']]);
    }

    public function login(LoginRequest $request)
    {
        try {

            $credentials = $request->only(['email', 'password']);

            if (! $token = auth('api')->attempt($credentials)) {
                return $this->responseCommon(400, 'Thông tin đăng nhập chưa đúng, vui lòng kiểm tra lại', []);
            }

            $user = auth('api')->user();
            if ($user->status === 'inactive') {
                return $this->responseError(423, 'Tài khoản của bạn đã bị khóa.', []);
            }
            if ($user->email_verified_at === null) {
                return $this->responseError(403, 'Tài khoản của bạn chưa được kích hoạt, vui lòng vào email để kích hoạt tài khoản.', []);
            }

            $refreshToken = $this->createRefreshToken();

            return $this->respondWithToken($token, $refreshToken);
        } catch (\Exception $e) {
            return $this->responseError(500, 'Lỗi xử lý.', $e->getMessage());
        }
    }

    public function register(RegisterRequest $request)
    {
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            // Tạo ngẫu nhiên tên ảnh 12 kí tự
            $imageName = Str::random(12) . "." . $file->getClientOriginalExtension();
            // Đường dẫn ảnh
            $imageDirectory = 'images/users/avatars/';

            $file->move($imageDirectory, $imageName);
            $path_image   = 'http://127.0.0.1:8000/' . ($imageDirectory . $imageName);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role_id' => 2,
            'phone' => $request->phone,
            'address' => $request->address,
            'birthday' => $request->birthday,
            'avatar' => $path_image,
            'fileName' => $imageName,
        ]);
        $token = auth('api')->login($user);
        $refreshToken = $this->createRefreshToken();

        // Trả về access_token và thông tin user khi đăng ký thành công
        return $this->responseCommon(201, "Đăng ký thành công", [
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => $user,
        ]);
    }

    public function profile()
    {
        try {
            return $this->responseCommon(200, 'Tìm thấy thông tin user', auth('api')->user());
        } catch (\Exception $e) {
            return $this->responseError(500, 'Refresh Token không hợp lệ', $e);
        }
    }

    public function logout()
    {
        auth('api')->logout();

        return $this->responseCommon(200, 'Đăng xuất thành công', []);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $user = auth('api')->user();
        $password = $request->password;
        if (Hash::check($password, $user->password)) {
            // Mật khẩu khớp
            // Mật khẩu mới không được giống mật khẩu đặt gần đây nhất
            if (Hash::check($request->confirm_password, $user->password)) {
                return $this->responseCommon(422, 'Mật khẩu mới phải khác mật khẩu cũ, vui lòng chọn mật khẩu khác', []);
            }
            // Nếu không thì cập nhật
            $user = User::where('id', $user->id)
                ->update([
                    "password" => bcrypt($request->confirm_password)
                ]);
            return $this->responseCommon(200, "Thay đổi mật khẩu thành công.", auth('api')->user());
        } else {
            // Mật khẩu không khớp
            return $this->responseCommon(401, 'Mật khẩu không chính xác.', []);
        }
    }

    public function refresh()
    {
        $refreshToken = request()->refresh_token;
        try {
            $decoded = JWTAuth::getJWTProvider()->decode($refreshToken);

            // Cấp lại token mới
            $user = User::find($decoded['user_id']);

            if (!$user) {
                return $this->responseError(404, 'User không tồn tại', []);
            }
            // Tạo mới access token
            $token = auth('api')->login($user);
            $newRefreshToken = $this->createRefreshToken(); // Tạo mới refresh token

            return $this->respondWithToken($token, $newRefreshToken);
        } catch (JWTException $e) {
            return $this->responseError(500, 'Refresh Token không hợp lệ', $e);
        }
    }

    private function respondWithToken($token, $refreshToken)
    {
        return response()->json([
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => auth('api')->user(),
        ]);
    }

    private function createRefreshToken()
    {
        $data = [
            'user_id' => auth('api')->user()->id,
            'random' => rand() . time(),
            'exp' => time() + config('jwt.refresh_ttl')
        ];
        $refreshToken = JWTAuth::getJWTProvider()->encode($data);

        return $refreshToken;
    }
}
