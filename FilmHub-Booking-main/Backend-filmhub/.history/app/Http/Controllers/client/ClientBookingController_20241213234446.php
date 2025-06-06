<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Showtime;
use Illuminate\Support\Facades\Auth;
use App\Models\Seat;
use App\Models\Voucher;
use App\Models\VourcherEvent;
use App\Models\VourcherRedeem;

class ClientBookingController extends Controller
{
    public function index($id, Request $request)
    {
        // Lấy thời gian hiện tại
        $currentDateTime = Carbon::now('Asia/Ho_Chi_Minh');

        $showtimes = DB::table('showtimes')
            ->join('rooms', 'showtimes.room_id', '=', 'rooms.room_id')
            ->join('shifts', 'showtimes.shift_id', '=', 'shifts.shift_id')
            ->join('theaters', 'showtimes.theater_id', '=', 'theaters.theater_id')
            ->where('showtimes.movie_id', $id)
            ->where(function ($query) use ($currentDateTime) {
                $query->whereRaw('DATE(showtimes.datetime) > ?', [$currentDateTime->toDateString()])
                    ->orWhere(function ($subQuery) use ($currentDateTime) {
                        $subQuery->whereRaw('DATE(showtimes.datetime) = ?', [$currentDateTime->toDateString()])
                            ->whereRaw('shifts.start_time >= ?', [$currentDateTime->format('H:i:s')]);
                    });
            })
            ->select(
                'showtimes.showtime_id',
                DB::raw('DATE(showtimes.datetime) AS show_date'),
                'shifts.start_time',
                'showtimes.normal_price',
                'showtimes.vip_price',
                'rooms.room_name as room_name',
                'theaters.name as theater_name',
                'theaters.theater_id'
            )
            ->orderBy('show_date', 'asc')
            ->orderBy('shifts.start_time', 'asc')
            ->get();

        // Group các showtime theo ngày
        $showtimesGroupedByDate = $showtimes->groupBy('show_date');

        // Xử lý ngày mặc định nếu không có ngày nào được chọn
        $selectedDate = $request->input('selected_date', null);

        // Nếu không có ngày chọn, hiển thị tất cả showtimes
        if ($selectedDate) {
            $selectedShowtimes = $showtimesGroupedByDate->get($selectedDate, collect());
        } else {
            $selectedShowtimes = $showtimes;
        }

        // Lấy danh sách các rạp có chứa showtime của phim
        $theaters = DB::table('theaters')
            ->join('showtimes', 'theaters.theater_id', '=', 'showtimes.theater_id')
            ->where('showtimes.movie_id', $id)
            ->select('theaters.theater_id', 'theaters.name')
            ->distinct()
            ->get();

        return view('frontend.layouts.booking.index', [
            'showtimesGroupedByDate' => $showtimesGroupedByDate,
            'selectedShowtimes' => $selectedShowtimes,
            'selectedDate' => $selectedDate,
            'movieId' => $id,
            'theaters' => $theaters,
        ]);
    }


    public function getSeatBooking($showtime_id)
    {
        $showtime = Showtime::with([
            'movies',
            'rooms.rows.seats.types',
            'rooms.theaters',
            'shifts'
        ])
            ->where('showtime_id', $showtime_id)
            ->firstOrFail();

        $currentDateTime = Carbon::now();
        $showtimeStartDate = Carbon::parse($showtime->datetime);
        $showtimeStartTime = Carbon::createFromFormat('H:i:s', $showtime->shifts->start_time);
        $showtimeStart = $showtimeStartDate->setTimeFromTimeString($showtimeStartTime->toTimeString());

        // Kiểm tra nếu ca chiếu đã qua
        if ($showtimeStart->isPast()) {
            return redirect()->route('movies.index')->with('error', 'Ca chiếu đã qua, vui lòng chọn ca chiếu khác.');
        }

        // Tính thời gian còn lại
        $timeUntilShowtime = $currentDateTime->diffInMinutes($showtimeStart, false);

        // Nếu còn đúng 9 phút, không cho phép đặt vé
        if ($timeUntilShowtime === 9) {
            return redirect()->route('movies.index')->with('error', 'Hiện tại không thể đặt vé, vui lòng thử lại sau.');
        }

        $user_id = session('user_id');
        $bookedSeats = DB::table('tickets_seats')
            ->where('showtime_id', $showtime_id)
            ->pluck('seat_id')
            ->toArray();

        return view('frontend.layouts.booking.getSeatBooking', compact('showtime', 'user_id', 'bookedSeats'));
    }
    public function detailBooking(Request $request, $showtime_id)
    {
        if (!Auth::check()) {

            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập để tiếp tục.');
        }

        $showtime = Showtime::findOrFail($showtime_id); // Lấy thông tin showtime

        $user_id = session('user_id');

        // Nhận ghế đã chọn từ request
        $selectedSeats = $request->input('selected_seats');
        $totalPrice = $request->input('total_price'); // Tổng giá tiền
        $seats = Seat::whereIn('seat_id', $selectedSeats)->get(); // Truy vấn các ghế từ cơ sở dữ liệu
        $seatNumbers = $seats->pluck('seat_number');
        // dd(   $selectedSeats);

        // Tải danh sách combos
        $combos = Combo::all();
        $currentDateTime = Carbon::now();
        $vouchers = VourcherRedeem::where('user_id', $user_id)->with('user')->get();
        $vourcherEvents = VourcherEvent::all(); // Lấy danh sách voucher event

        // Kiểm tra mã giảm giá đã sử dụng
        $usedVoucher = DB::table('vourcher_user')
            ->where('user_id', $user_id)
            ->pluck('vourcher_id')
            ->first(); // Lấy mã giảm giá đầu tiên mà người dùng đã sử dụng // Mặc định là null nếu không có mã

        // Lấy danh sách voucher sự kiện còn hạn
        $vourcherEvents = VourcherEvent::where('is_active', true)
            ->where('end_time', '>', $currentDateTime)
            ->get();


        // Truyền dữ liệu đến view
        return view('frontend.layouts.booking.detailBooking', compact(
            'showtime',
            'selectedSeats', // Truyền mảng ghế đã chọn
            'totalPrice',
            'user_id',
            'seatNumbers',
            'combos',
            'vouchers',
            'vourcherEvents',
            'usedVoucher' // Truyền combos vào view
        ));
    }

}
