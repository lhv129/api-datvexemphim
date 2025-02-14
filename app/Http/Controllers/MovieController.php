<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMovieRequest;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\Movie_genre;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class MovieController extends Controller
{
    public function index()
    {
        $movies = Movie::select('id', 'title', 'description', 'poster', 'fileName', 'trailer', 'duration', 'rating', 'release_date')
            ->get();

        $movie_genres = $movies->map(function ($movie) {
            return [
                'id' => $movie->id,
                'genres' => Movie_genre::select('movie_genres.genre_id', 'genres.name')
                    ->join('genres', 'genres.id', 'movie_genres.genre_id')
                    ->where('movie_genres.movie_id', $movie->id)
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
        return $this->responseCommon(200, "Lấy danh sách phim thành công.", $movie_genres);
    }

    public function store(StoreMovieRequest $request)
    {
        $validateData = $request->validated();
        
        // try {
        //     //Kiểm tra sự tồn tại của các thể loại trong yêu cầu tạo phim.
        //     //-> whereIn để tìm kiếm tất cả các bản ghi trong bảng genres có id trong mảng
        //     //-> == count để đếm số lượng bản ghi tìm thấy với số lượng id trong mảng 
        //     $genresExist = Genre::whereIn('id', $request->genres)->count() == count($request->genres);
        //     if (!$genresExist) {
        //         return $this->responseCommon(400, "Một hoặc nhiều thể loại không tồn tại.", []);
        //     }
        //     if ($request->hasFile('poster')) {
        //         $file = $request->file('poster');
        //         // Tạo ngẫu nhiên tên ảnh 12 kí tự
        //         $imageName = Str::random(12) . "." . $file->getClientOriginalExtension();
        //         // Đường dẫn ảnh
        //         $imageDirectory = 'images/movies/';

        //         $file->move($imageDirectory, $imageName);
        //         $path_image   = 'http://127.0.0.1:8000/' . ($imageDirectory . $imageName);

        //         $movie = Movie::create([
        //             'title' => $request->title,
        //             'description' => $request->description,
        //             'poster' => $path_image,
        //             'fileName' => $imageName,
        //             'trailer' => $request->trailer,
        //             'duration' => $request->duration,
        //             'rating' => $request->rating,
        //             'release_date' => $request->release_date,
        //         ]);
        //         $movie['genres'] = $request->genres;

        //         // Khi thêm phim thì cũng phải thêm thể loại cho phim đó.
        //         // Lấy ra id của phim vừa thêm (mới nhất)
        //         $latestIdMovie = Movie::orderBy('id', 'desc')->first()->id;
        //         foreach ($request->genres as $genre) {
        //             Movie_genre::create([
        //                 'movie_id' => $latestIdMovie,
        //                 'genre_id' => $genre
        //             ]);
        //         }

        //         return $this->responseCommon(201, "Thêm mới phim thành công.", $movie);
        //     }
        // } catch (\Exception $e) {
        //     return $this->responseError(500, 'Lỗi xử lý.', $e->getMessage());
        // }
    }

    public function update(Request $request, $id)
    {
        try {
            $movie = Movie::findOrFail($id);

            $rules = $this->validateUpdateMovie($id);
            $alert = $this->alertUpdateMovie();
            $validator = Validator::make($request->all(), $rules, $alert);

            if ($validator->fails()) {
                return $this->responseError(422, 'Dữ liệu không hợp lệ', $validator->errors());
            } else {
                $genresExist = Genre::whereIn('id', $request->genres)->count() == count($request->genres);
                if (!$genresExist) {
                    return $this->responseCommon(400, "Một hoặc nhiều thể loại không tồn tại.", []);
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

                    $path_image   = 'http://127.0.0.1:8000/' . ($imageDirectory . $imageName);
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
                ]);
                $movie['genres'] = $request->genres;
                //Xóa toàn bộ thể loại phim và thêm lại thể loại cho phim đó dựa theo update
                Movie_genre::where('movie_id', $id)->delete();
                foreach ($request->genres as $genre) {
                    Movie_genre::create([
                        'movie_id' => $id,
                        'genre_id' => $genre
                    ]);
                }

                return $this->responseCommon(200, "Cập nhật phim thành công.", $movie);
            }
        } catch (\Exception $e) {
            return $this->responseCommon(404, "Phim này không tồn tại hoặc đã bị xóa.", []);
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

            $movie->delete();

            return $this->responseCommon(200, "Xóa phim thành công.", []);
        } catch (\Exception $e) {
            return $this->responseCommon(404, "Phim này không tồn tại hoặc đã bị xóa.", []);
        }
    }

    //Validate


    public function validateUpdateMovie($id)
    {
        return [
            'title' => 'required|min:5|max:255|unique:movies,title,' . $id,
            'description' => 'required|min:5|max:500',
            'poster' => 'mimes:jpeg,jpg,png',
            'trailer' => 'required',
            'duration' => 'required',
            'rating' => 'required',
            'release_date' => 'required',
            'genres' => 'required|array ',
        ];
    }

    public function alertUpdateMovie()
    {
        return [
            'required' => 'Không được để trống thông tin :attribute.',
            'title.unique' => 'Tiêu đề phim không được trùng.',
            'title.min' => 'Tiêu đề phim phải ít nhất 5 kí tự.',
            'title.max' => 'Tiêu đề phim quá dài.',
            'description.min' => 'Mô tả phim phải ít nhất 5 kí tự.',
            'description.max' => 'Mô tả phim quá dài.',
            'mimes' => 'Bạn chỉ được nhập file ảnh có đuôi jpeg,jpg,png',
            'genres.array' => 'Thể loại phim phải là 1 mảng array',
        ];
    }
}
