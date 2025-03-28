<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Kiểm tra xem người dùng đã đăng nhập chưa
        if (!$request->user()) {
            return response()->json(["status" => "Failed", "message" => "Vui lòng đăng nhập trước."], 401); // Trả về 401 Unauthorized nếu chưa đăng nhập
        }

        // Kiểm tra vai trò của người dùng
        if (!in_array($request->user()->role_id, $roles)) {
            return response()->json(["status" => "Failed", "message" => "Bạn không có quyền truy cập."], 403); // Trả về 403 Forbidden nếu không có quyền truy cập
        }

        return $next($request); // Cho phép request tiếp tục nếu có quyền truy cập
    }
}
