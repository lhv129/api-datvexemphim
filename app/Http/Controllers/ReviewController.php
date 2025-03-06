<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = Review::select('reviews.id', 'user_id', 'users.name AS username', 'movie_id', 'movies.title AS movie_title', 'reviews.rating', 'comment')
            ->join('users', 'users.id', 'reviews.user_id')
            ->join('movies', 'movies.id', 'reviews.movie_id')
            ->where('reviews.status', 'public')
            ->get();
        return $this->responseCommon(200, 'Lấy thành công danh sách bình luận.', $reviews);
    }

    public function getReviewByMovieId(Request $request)
    {
        $movie = $request->movie_id;
        if (!$movie) {
            return $this->responseError(400, 'Vui lòng chọn phim để lấy ra danh sách bình luận', []);
        }
        $reviews = Review::select('reviews.id', 'user_id', 'users.name AS username', 'movie_id', 'movies.title AS movie_title', 'reviews.rating', 'comment')
            ->join('users', 'users.id', 'reviews.user_id')
            ->join('movies', 'movies.id', 'reviews.movie_id')
            ->where('reviews.status', 'public')
            ->where('movie_id', $request->movie_id)
            ->orderBy('id', 'desc')
            ->get();
        return $this->responseCommon(200, 'Lấy thành công danh sách bình luận', $reviews);
    }

    public function store(StoreReviewRequest $request)
    {
        $user = auth('api')->user();
        $review = Review::create([
            'user_id' => $user->id,
            'movie_id' => $request->movie_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);
        return $this->responseCommon(201, 'Thêm mới bình luận thành công.', $review);
    }

    public function update(StoreReviewRequest $request, $id)
    {
        try {
            //Tìm ra bình luận đó
            $review = Review::find($id);
            $user = auth('api')->user();
            // Check chỉ được sửa bình luận của người dùng đó, không được sửa bình luận của người khác
            if ($user->id === $review->user_id && $review->movie_id === $request->movie_id) {
                $review->rating = $request->rating;
                $review->comment = $request->comment;
                $review->save(); // Lưu các thay đổi vào cơ sở dữ liệ
                return $this->responseCommon(200, 'Sửa bình luận thành công.',$review);
            }
            return $this->responseError(404, 'Bạn không có quyền sửa bình luận này.', []);
        } catch (\Exception $e) {
            return $this->responseCommon(404, "Bình luận này không tồn tại hoặc đã bị xóa.", []);
        }
    }

    public function destroy($id)
    {
        try {
            //Tìm ra bình luận đó
            $review = Review::find($id);
            $user = auth('api')->user();

            // Nếu là admin thì có thể xóa mọi bình luận
            if ($user->role_id === 1) {
                $review->delete();
                return $this->responseCommon(200, 'Xóa thành công bình luận.', []);
            }
            // Check chỉ được xóa bình luận của người dùng đó, không được xóa bình luận của người khác
            if ($user->id === $review->user_id) {
                $review->delete();
                return $this->responseCommon(200, 'Xóa bình luận thành công.', []);
            }
            return $this->responseError(404, 'Bạn không có quyền xóa bình luận này.', []);
        } catch (\Exception $e) {
            return $this->responseCommon(404, "Bình luận này không tồn tại hoặc đã bị xóa.", []);
        }
    }
}
