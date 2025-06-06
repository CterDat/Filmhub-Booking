<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Movie;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use function Laravel\Prompts\alert;

class CommentController extends Controller
{
    public function show($movie_id)
    {
        $comments = Comment::with('user')
            ->where('movie_id', $movie_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('movies.comments', compact('comments', 'movie_id'));
    }

    // Lưu bình luận mới
    public function store(Request $request, $movie_id)
    {
        // Kiểm tra người dùng đăng nhập
        if (!Auth::check()) {
            session()->flash('error', 'Bạn cần đăng nhập để đánh giá và bình luận.');
            return redirect()->route('getLogin');
        }

        $user_id = Auth::id();

        // Kiểm tra xem người dùng đã mua vé cho phim này chưa
        $ticketExists = Ticket::where('user_id', $user_id)
            ->whereHas('showtime', function ($query) use ($movie_id) {
                $query->where('movie_id', $movie_id); // Kiểm tra xem showtime có liên kết với movie_id
            })
            ->exists();

        if (!$ticketExists) {
            return redirect()->back()->with('error', 'Bạn cần mua vé cho phim này để đánh giá và bình luận.');
        }

        // Lấy các bình luận của người dùng cho phim này
        $userComments = Comment::where('user_id', $user_id)
            ->where('movie_id', $movie_id)
            ->orderBy('created_at', 'desc')
            ->take(3) // Chỉ lấy 3 bình luận gần nhất
            ->get();

        if ($userComments->count() >= 3) {
            // Lấy thời gian của bình luận gần nhất trong 3 bình luận
            $lastCommentTime = $userComments->last()->created_at;

            // Kiểm tra thời gian của bình luận gần nhất
            if ($lastCommentTime->diffInHours(now()) < 1) {
                return redirect()->back()->with('error', 'Bạn đã đạt giới hạn 3 bình luận. Vui lòng chờ 1 tiếng để bình luận tiếp.');
            }
        }

        // Validate dữ liệu từ form
        $request->validate([
            'comment' => 'required|string|max:255',
            'rating' => 'required|integer|between:1,5',
        ], [
            'comment.required' => 'Nội dung bình luận không được để trống.',
            'rating.required' => 'Vui lòng chọn số sao đánh giá.',
            'rating.between' => 'Số sao đánh giá phải nằm trong khoảng từ 1 đến 5.',
        ]);

        // Lưu bình luận kèm đánh giá
        Comment::create([
            'user_id' => $user_id,
            'movie_id' => $movie_id,
            'comment' => $request->comment,
            'rating' => $request->rating,
        ]);

        // Cập nhật rating trung bình cho phim
        $movie = Movie::findOrFail($movie_id);
        $averageRating = Comment::where('movie_id', $movie_id)->avg('rating');
        $movie->update(['rating' => round($averageRating, 1)]);

        return redirect()->back()->with('success', 'Đánh giá và bình luận của bạn đã được đăng.');
    }

}
