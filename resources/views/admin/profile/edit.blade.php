@extends('layouts.admin')

@section('title', 'پروفایل من')

@section('content')

    <h4 class="mb-4">پروفایل من</h4>

    <div class="row g-3">

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><strong>اطلاعات حساب</strong></div>
                <div class="card-body">
                    @if($errors->has('name') || $errors->has('email'))
                        <div class="alert alert-danger">
                            @foreach($errors->only('name', 'email') as $errs)
                                @foreach($errs as $e)<div>{{ $e }}</div>@endforeach
                            @endforeach
                        </div>
                    @endif

                    <form action="{{ route('admin.profile.update') }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="mb-3">
                            <label class="form-label">نام و نام خانوادگی</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">ایمیل (نام کاربری)</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" dir="ltr" required>
                            <small class="text-muted">اگر ایمیل را عوض کنید، ورود بعدی با ایمیل جدید خواهد بود.</small>
                        </div>

                        <button class="btn btn-primary">ذخیره تغییرات</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><strong>تغییر رمز عبور</strong></div>
                <div class="card-body">
                    @if($errors->has('current_password') || $errors->has('password'))
                        <div class="alert alert-danger">
                            @foreach($errors->only('current_password', 'password') as $errs)
                                @foreach($errs as $e)<div>{{ $e }}</div>@endforeach
                            @endforeach
                        </div>
                    @endif

                    <form action="{{ route('admin.profile.password') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">رمز فعلی</label>
                            <input type="password" name="current_password" class="form-control" dir="ltr" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">رمز جدید (حداقل ۸ کاراکتر)</label>
                            <input type="password" name="password" class="form-control" dir="ltr" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">تکرار رمز جدید</label>
                            <input type="password" name="password_confirmation" class="form-control" dir="ltr" required>
                        </div>

                        <button class="btn btn-success">تغییر رمز</button>
                    </form>
                </div>
            </div>
        </div>

    </div>

@endsection
