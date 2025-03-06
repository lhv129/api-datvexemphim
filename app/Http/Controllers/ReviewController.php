<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = Review::select('reviews.id', 'user_id', 'users.name AS username', 'movie_id','movies.title AS movie_title','reviews.rating','comment')
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
        $reviews = Review::select('id', 'user_id', 'movie_id', 'rating', 'comment')
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
}
