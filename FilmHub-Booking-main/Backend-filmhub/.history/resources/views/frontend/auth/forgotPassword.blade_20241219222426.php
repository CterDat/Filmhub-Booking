@extends('frontend.layouts.master4')
@section('content')
<style>
    .btn-login{
        background-color: #e21b10;
        color: white;
        padding: 8px 20px;
        margin: 8px 0;
        border: none;
        cursor: pointer;
        width: 100%;
    }
    .btn-login:hover{
        background-color: #d14740;
    }
</style>
<form action="{{route('forgotPassword')}}" method="POST">
    @csrf
    @method("POST")
    <div class="st_pop_form_wrapper" id="myModa2" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                @if (isset($user) && $user != false)
                    <div class="st_pop_form_heading_wrapper st_pop_form_heading_wrapper_fpass float_left">
                        <h3>Quên mật khẩu </h3>
                        <p>Chúng tôi đã gửi thông tin vào email của bạn, vui lòng kiểm tra email.</p>
                        <div>
                            <a href="{{route("getLogin")}}" style="color: #fff">
                            <button type="button" class="btn-login">Login !</button>
                            </a>
                        </div>
                    </div>

                @else
                    @if (isset($user) && $user == false)
                        <div class="st_pop_form_heading_wrapper st_pop_form_heading_wrapper_fpass float_left">
                            <h3>Forgot Password</h3>
                            <p>Email not found !</p>
                            <button class="btn-login" type="button"><a href="{{route('getForgotPassword')}}" style="color: #fff">Try again!</a></button>
                        </div>
                    @else
                        <div class="st_pop_form_heading_wrapper st_pop_form_heading_wrapper_fpass float_left">
                            <h3>Forgot Password</h3>
                            <p>We can help! All you need to do is enter your email ID and follow the instructions!</p>
                        </div>

                        <div class="st_profile_input float_left">
                            <label>Email Address</label>
                            <input type="text" name="email">
                        </div>
                        <div class="">
                            <button type="submit" class="btn-login">Verify</button>
                        </div>


                    @endif

                @endif

            </div>
        </div>
    </div>
</form>
@endsection
