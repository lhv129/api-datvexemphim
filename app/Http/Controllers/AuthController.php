<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function login(Request $request)
    {

        $rules = $this->validateLogin();
        $alert = $this->alertLogin();
        $validator = Validator::make($request->all(), $rules, $alert);

        if ($validator->fails()) {
            return $this->responseError(422, "Dữ liệu không hợp lệ", $validator->errors());
        }
        $credentials = request(['email', 'password']);

        if (! $token = auth('api')->attempt($credentials)) {
            return $this->responseCommon(400, 'Thông tin đăng nhập chưa đúng, vui lòng kiểm tra lại', []);
        }

        $refreshToken = $this->createRefreshToken();

        return $this->respondWithToken($token, $refreshToken);
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

    public function refresh()
    {
        $refreshToken = request()->refresh_token;
        try {
            $decoded = JWTAuth::getJWTProvider()->decode($refreshToken);
            // Cấp lại token mới
            // -> Lấy thông tin user
            $user = User::find($decoded['user_id']);
            if (!$user) {
                return $this->responseError(500, 'User không tồn tại', []);
            }

            auth()->invalidate(); // Vô hiệu hóa access_token cũ

            $token = auth('api')->login($user);
            $refreshToken = $this->createRefreshToken(); // Tạo mới token

            return $this->respondWithToken($token, $refreshToken);
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
            'expires_in' => auth('api')->factory()->getTTL() * 60,
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


    public function validateLogin()
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
        ];
    }

    public function alertLogin()
    {
        return [
            'required' => 'Không được để trống thông tin :attribute.',
            'email.email' => 'Email không đúng định dạng'
        ];
    }
}
