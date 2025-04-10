<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Actor;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\Review;
use App\Models\Actor_movie;
use App\Models\Movie_genre;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Http\Requests\StoreMovieRequest;
use App\Http\Requests\UpdateMovieRequest;


class MovieController extends Controller
{
    public function moviesShowing()
    {
        $today = now()->toDateString();
        $movies = Movie::select('id', 'title', 'description', 'poster', 'fileName', 'trailer', 'duration', 'rating', 'release_date', 'end_date')
            ->where('release_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->get();

        $dataMovie = $movies->map(function ($movie) {
            return [
                'id' => $movie->id,
                'genres' => Movie_genre::select('movie_genres.genre_id', 'genres.name')
                    ->join('genres', 'genres.id', 'movie_genres.genre_id')
                    ->where('movie_genres.movie_id', $movie->id)
                    ->get(),
                'actors' => Actor_movie::select('actor_movies.actor_id', 'actors.name')
                    ->join('actors', 'actors.id', 'actor_movies.actor_id')
                    ->where('actor_movies.movie_id', $movie->id)
                    ->get(),
                'title' => $movie->title,
                'description' => $movie->description,
                'poster' => $movie->poster,
                'fileName' => $movie->fileName,
                'trailer' => $movie->trailer,
                'duration' => $movie->duration,
                'rating' => $movie->rating,
                'release_date' => $movie->release_date,
                'end_date' => $movie->end_date,
            ];
        });
        return $this->responseCommon(200, "Lấy danh sách phim thành công.", $dataMovie);
    }

    public function moviesUpcoming()
    {
        $futureTime = Carbon::now()->addMonths(1)->toDateString();
        $movies = Movie::select('id', 'title', 'description', 'poster', 'fileName', 'trailer', 'duration', 'rating', 'release_date', 'end_date')
            ->where('release_date', '>=', $futureTime)
            ->get();

        $dataMovie = $movies->map(function ($movie) {
            return [
                'id' => $movie->id,
                'genres' => Movie_genre::select('movie_genres.genre_id', 'genres.name')
                    ->join('genres', 'genres.id', 'movie_genres.genre_id')
                    ->where('movie_genres.movie_id', $movie->id)
                    ->get(),
                'actors' => Actor_movie::select('actor_movies.actor_id', 'actors.name')
                    ->join('actors', 'actors.id', 'actor_movies.actor_id')
                    ->where('actor_movies.movie_id', $movie->id)
                    ->get(),
                'title' => $movie->title,
                'description' => $movie->description,
                'poster' => $movie->poster,
                'fileName' => $movie->fileName,
                'trailer' => $movie->trailer,
                'duration' => $movie->duration,
                'rating' => $movie->rating,
                'release_date' => $movie->release_date,
                'end_date' => $movie->end_date,
            ];
        });
        return $this->responseCommon(200, "Lấy danh sách phim thành công.", $dataMovie);
    }

    public function searchMovie(Request $request)
    {
        $today = now()->toDateString();

        $name = $request->name;
        if (!$name) {
            return $this->responseError(404, 'Vui lòng nhập tên phim để tìm kiếm', []);
        }
        $movies = Movie::where('title', 'LIKE', "%$name%")
            ->where('end_date', '>=', $today)
            ->get();

        $dataMovie = $movies->map(function ($movie) {
            return [
                'id' => $movie->id,
                'genres' => Movie_genre::select('movie_genres.genre_id', 'genres.name')
                    ->join('genres', 'genres.id', 'movie_genres.genre_id')
                    ->where('movie_genres.movie_id', $movie->id)
                    ->get(),
                'actors' => Actor_movie::select('actor_movies.actor_id', 'actors.name')
                    ->join('actors', 'actors.id', 'actor_movies.actor_id')
                    ->where('actor_movies.movie_id', $movie->id)
                    ->get(),
                'title' => $movie->title,
                'description' => $movie->description,
                'poster' => $movie->poster,
                'fileName' => $movie->fileName,
                'trailer' => $movie->trailer,
                'duration' => $movie->duration,
                'rating' => $movie->rating,
                'release_date' => $movie->release_date,
            ];
        });
        return $this->responseCommon(200, "Lấy danh sách phim thành công.", $dataMovie);
    }

    public function store(StoreMovieRequest $request)
    {
        try {
            //Kiểm tra sự tồn tại của các thể loại trong yêu cầu tạo phim.
            //-> whereIn để tìm kiếm tất cả các bản ghi trong bảng genres có id trong mảng
            //-> == count để đếm số lượng bản ghi tìm thấy với số lượng id trong mảng 
            $genresExist = Genre::whereIn('id', $request->genres)->count() == count($request->genres);
            if (!$genresExist) {
                return $this->responseCommon(400, "Một hoặc nhiều thể loại không tồn tại.", []);
            }
            $actorsExist = Actor::whereIn('id', $request->actors)->count() == count($request->actors);
            if (!$actorsExist) {
                return $this->responseCommon(400, "Một hoặc nhiều diễn viên không tồn tại.", []);
            }
            if ($request->hasFile('poster')) {
                $file = $request->file('poster');
                // Tạo ngẫu nhiên tên ảnh 12 kí tự
                $imageName = Str::random(12) . "." . $file->getClientOriginalExtension();
                // Đường dẫn ảnh
                $imageDirectory = 'images/movies/';

                $file->move($imageDirectory, $imageName);
                $path_image   = 'http://filmgo.io.vn/' . ($imageDirectory . $imageName);
                $movie = Movie::create([
                    'title' => $request->title,
                    'description' => $request->description,
                    'poster' => $path_image,
                    'fileName' => $imageName,
                    'trailer' => $request->trailer,
                    'duration' => $request->duration,
                    'rating' => $request->rating,
                    'release_date' => $request->release_date,
                    'end_date' => $request->end_date,
                ]);
                $movie['genres'] = $request->genres;
                $movie['actors'] = $request->actors;

                // Khi thêm phim thì cũng phải thêm thể loại cho phim đó.
                // Lấy ra id của phim vừa thêm (mới nhất)
                $latestIdMovie = Movie::orderBy('id', 'desc')->first()->id;
                foreach ($request->genres as $genre) {
                    Movie_genre::create([
                        'movie_id' => $latestIdMovie,
                        'genre_id' => $genre
                    ]);
                }
                // Khi thêm phim thì cũng phải thêm diễn viên cho phim đó.
                foreach ($request->actors as $actor) {
                    Actor_movie::create([
                        'movie_id' => $latestIdMovie,
                        'actor_id' => $actor
                    ]);
                }

                return $this->responseCommon(201, "Thêm mới phim thành công.", $movie);
            }
        } catch (\Exception $e) {
            return $this->responseError(500, 'Lỗi xử lý.', $e->getMessage());
        }
    }

    public function update(UpdateMovieRequest $request, $id)
    {
        try {
            $movie = Movie::findOrFail($id);
            $genresExist = Genre::whereIn('id', $request->genres)->count() == count($request->genres);
            if (!$genresExist) {
                return $this->responseCommon(400, "Một hoặc nhiều thể loại phim không tồn tại.", []);
            }
            $actorsExist = Actor::whereIn('id', $request->actors)->count() == count($request->actors);
            if (!$actorsExist) {
                return $this->responseCommon(400, "Một hoặc nhiều diễn viên không tồn tại.", []);
            }
            if ($request->hasFile('poster')) {
                $file = $request->file('poster');
                // Đường dẫn ảnh
                $imageDirectory = 'images/movies/';
                // Xóa ảnh nếu ảnh cũ
                File::delete($imageDirectory . $movie->fileName);
                // Tạo ngẫu nhiên tên ảnh 12 kí tự
                $imageName = Str::random(12) . "." . $file->getClientOriginalExtension();

                $file->move($imageDirectory, $imageName);

                $path_image   = 'http://filmgo.io.vn/' . ($imageDirectory . $imageName);
            } else {
                $path_image = $movie->poster;
            }
            $movie->update([
                'title' => $request->title,
                'description' => $request->description,
                'poster' => $path_image,
                'fileName' => $imageName ?? $movie->fileName, // Dùng toán tử 3 ngôi, nếu không thêm ảnh mới thì giữ lại tên ảnh cũ
                'trailer' => $request->trailer,
                'duration' => $request->duration,
                'rating' => $request->rating,
                'release_date' => $request->release_date,
                'end_date' => $request->end_date,
            ]);
            $movie['genres'] = $request->genres;
            $movie['actors'] = $request->actors;
            //Xóa toàn bộ thể loại phim cũ và thêm lại thể loại cho phim đó dựa theo update
            $deleteGenres = Movie_genre::where('movie_id', $id)->delete();
            if ($deleteGenres) {
                foreach ($request->genres as $genre) {
                    Movie_genre::create([
                        'movie_id' => $id,
                        'genre_id' => $genre
                    ]);
                }
            }

            //Xóa toàn bộ diễn viên phim cũ và thêm lại diễn viên cho phim đó dựa theo update
            $deleteActor = Actor_movie::where('movie_id', $id)->delete();
            if ($deleteActor) {
                foreach ($request->actors as $actor) {
                    Actor_movie::create([
                        'movie_id' => $id,
                        'actor_id' => $actor
                    ]);
                }
            }

            return $this->responseCommon(200, "Cập nhật phim thành công.", $movie);
        } catch (\Exception $e) {
            return $this->responseCommon(404, "Phim này đã bị xóa hoặc không tồn tại.", []);
        }
    }

    public function show($id)
    {
        try {
            $movie = Movie::findOrFail($id);
            $movie['genres'] = Movie_genre::select('movie_genres.genre_id', 'genres.name')
                ->join('genres', 'genres.id', 'movie_genres.genre_id')
                ->where('movie_genres.movie_id', $id)
                ->get();
            $movie['actors'] = Actor_movie::select('actor_movies.actor_id', 'actors.name')
                ->join('actors', 'actors.id', 'actor_movies.actor_id')
                ->where('actor_movies.movie_id', $id)
                ->get();
            return $this->responseCommon(200, "Tìm phim thành công.", $movie);
        } catch (\Exception $e) {
            return $this->responseCommon(404, "Phim này không tồn tại hoặc đã bị xóa.", []);
        }
    }

    public function destroy($id)
    {
        try {
            $movie = Movie::findOrFail($id);

            // Đường dẫn ảnh
            $imageDirectory = 'images/movies/';
            // Xóa sản phẩm thì xóa luôn ảnh sản phẩm đó
            File::delete($imageDirectory . $movie->fileName);

            //Xóa luôn những thể loại phim đó.
            $movie_genres = Movie_genre::where('movie_id', $id)->delete();

            //Xóa luôn những diễn viên phim đó.
            $actor_movies = Actor_movie::where('movie_id', $id)->delete();

            //Xóa luôn bình luận về phim đó.
            $reviews = Review::where('movie_id', $id)->delete();

            $movie->delete();

            return $this->responseCommon(200, "Xóa phim thành công.", []);
        } catch (\Exception $e) {
            return $this->responseCommon(404, "Phim này không tồn tại hoặc đã bị xóa.", []);
        }
    }
}
