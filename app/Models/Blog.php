<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',    // ID của người dùng tạo blog
        'title',      // Tiêu đề của blog
        'content',    // Nội dung của blog
        'description', // Mô tả (có thể null)
        'image',      // Đường dẫn ảnh (có thể null)
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
