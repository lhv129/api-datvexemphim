<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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

    public function register(Request $request)
    {
        $rules = $this->validateRegister();
        $alert = $this->alertRegister();
        $validator = Validator::make($request->all(), $rules, $alert);

        if ($validator->fails()) {
            return $this->responseError(422, "Dữ liệu không hợp lệ", $validator->errors());
        }

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
        $token = JWTAuth::fromUser($user);

        // Trả về access_token và thông tin user khi đăng ký thành công
        return $this->responseCommon(201, "Đăng ký thành công", [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::getTTL() * 60,
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

    // public function refresh()
    // {
    //     $refreshToken = request()->refresh_token;
    //     try {
    //         $decoded = JWTAuth::getJWTProvider()->decode($refreshToken);
    //         // Cấp lại token mới
    //         // -> Lấy thông tin user
    //         $user = User::find($decoded['user_id']);
    //         if (!$user) {
    //             return $this->responseError(500, 'User không tồn tại', []);
    //         }

    //         // auth()->invalidate(); // Vô hiệu hóa access_token cũ


    //         $token = auth('api')->login($user);
    //         $refreshToken = $this->createRefreshToken(); // Tạo mới token

    //         return $this->respondWithToken($token, $refreshToken);
    //     } catch (JWTException $e) {
    //         return $this->responseError(500, 'Refresh Token không hợp lệ', $e);
    //     }
    // }
    public function refresh()
{
    $refreshToken = request()->refresh_token;
    try {
        $decoded = JWTAuth::getJWTProvider()->decode($refreshToken);

        // Cấp lại token mới
        $user = User::find($decoded['user_id']);
        if (!$user) {
            return $this->responseError(500, 'User không tồn tại', []);
        }

        // Vô hiệu hóa refresh_token cũ
        JWTAuth::invalidate($refreshToken); // Vô hiệu hóa refreshToken cũ

        // Tạo mới access_token
        $token = JWTAuth::fromUser($user);
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
            'expires_in' => JWTAuth::getTTL() * 60,
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

    public function validateRegister()
    {
        return [
            'name' => 'required|unique:users,name',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|max:255',
            'phone' => 'required|regex:/^(0)(98)[0-9]{7}$/',
            'address' => 'required|min:6|max:255',
            'birthday' => 'required',
            'avatar' => 'required|mimes:jpeg,jpg,png',
        ];
    }

    public function alertRegister()
    {
        return [
            'required' => 'Không được để trống thông tin :attribute.',
            'email.email' => 'Email không đúng định dạng',
            'min' => ':attribute. tối thiểu ít nhất 6 kí tự',
            'max' => ':attribute. quá dài, vui lòng nhập lại',
            'phone.regex' => 'Sai định dạng số điện thoại, vui lòng kiểm tra lại',
            'avatar.mimes' => 'Bạn chỉ được nhập file ảnh có đuôi jpeg,jpg,png',
        ];
    }
}
