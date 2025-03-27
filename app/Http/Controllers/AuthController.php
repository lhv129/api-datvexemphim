<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Mail\VerifyEmail;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\RegisterRequest;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'refresh', 'verifyEmail']]);
    }

    public function login(LoginRequest $request)
    {
        try {

            $credentials = $request->only(['email', 'password']);

            if (! $token = auth('api')->attempt($credentials)) {
                return $this->responseCommon(400, 'Thông tin đăng nhập chưa đúng, vui lòng kiểm tra lại', []);
            }

            $user = auth('api')->user();
            if ($user->email_verified_at === null) {
                return $this->responseError(403, 'Tài khoản của bạn chưa được kích hoạt, vui lòng vào email để kích hoạt tài khoản.', []);
            }
            if ($user->status === 'inactive') {
                return $this->responseError(423, 'Tài khoản của bạn đã bị khóa.', []);
            }

            $refreshToken = $this->createRefreshToken();

            return $this->respondWithToken($token, $refreshToken);
        } catch (\Exception $e) {
            return $this->responseError(500, 'Lỗi xử lý.', $e->getMessage());
        }
    }

    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role_id' => 3,
            'phone' => $request->phone,
            'address' => $request->address,
            'birthday' => $request->birthday,
            'avatar' => 'http://filmgo.io.vn/images/avatars/default.jpg',
            'fileName' => 'fileName.png',
            'verification_token' => Str::random(40)
        ]);
        $data = [
            'title' => 'Xác thực Email',
            'name' => $user->name,
            'token' => $user->verification_token
        ];
        Mail::to($request->email)->send(new VerifyEmail($data));
        // Trả về access_token và thông tin user khi đăng ký thành công
        return $this->responseCommon(201, "Cảm ơn bạn đã đăng ký! Vui lòng kiểm tra email {$request->email} hoặc trong thư rác để kích hoạt tài khoản", $user);
    }


    public function verifyEmail($token)
    {
        $user = User::where('verification_token', $token)->first();
        if ($user) {
            $user->email_verified_at = now();
            $user->status = 'active';
            $user->verification_token = null;
            $user->save();

            $accessToken = auth('api')->login($user);
            $refreshToken = $this->createRefreshToken();

            return $this->responseCommon(201, "Kích hoạt tài khoản thành công", [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
                'user' => $user,
            ]);
        } else {
            return $this->responseError(404, "Token không hợp lệ hoặc đã hết hạn.", []);
        }
    }

    public function profile()
    {
        try {
            $user = User::select('users.id', 'role_id', 'roles.name as role_name', 'email', 'phone', 'address', 'birthday', 'avatar', 'fileName', 'status')
                ->join('roles', 'roles.id', 'roles.id')
                ->find(auth('api')->user()->id);
            return $this->responseCommon(200, 'Tìm thấy thông tin user', $user);
        } catch (\Exception $e) {
            return $this->responseError(500, 'Token không hợp lệ', $e);
        }
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        try {
            $user = User::where('id',Auth::user()->id)
            ->first();
            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                // Đường dẫn ảnh
                $imageDirectory = 'images/users/avatars/';
                // Xóa ảnh nếu ảnh cũ
                File::delete($imageDirectory . $user->fileName);
                // Tạo ngẫu nhiên tên ảnh 12 kí tự
                $imageName = Str::random(12) . "." . $file->getClientOriginalExtension();

                $file->move($imageDirectory, $imageName);

                $path_image   = 'http://filmgo.io.vn/' . ($imageDirectory . $imageName);
            } else {
                $path_image = $user->avatar;
                $imageName = $user->fileName;
            }
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'birthday' => $request->birthday,
                'avatar' => $path_image,
                'fileName' => $imageName,
            ]);
            return $this->responseCommon(200, "Cập nhật hồ sơ cá nhân thành công.", $user);
        } catch (\Exception $e) {
            return $this->responseError(404, 'Người dùng không tồn tại', []);
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
        $user = User::select('users.id', 'users.name', 'role_id', 'roles.name as role_name', 'email', 'email_verified_at', 'phone', 'address', 'birthday', 'avatar', 'fileName', 'status')
            ->join('roles', 'roles.id', 'role_id')
            ->where('users.id', auth('api')->user()->id)
            ->get();
        return response()->json([
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            // 'expires_in' => JWTAuth::getTTL() * 60,
            'expires_in' => config('jwt.ttl') * 60,
            'user' => $user,
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
