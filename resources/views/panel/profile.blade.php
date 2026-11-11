@extends('layouts.panel')

@section('title', 'پروفایل من')

@section('content')

    <h3 class="mb-4">پروفایل من</h3>

    @if(auth()->user()->must_change_password)
        <div class="alert alert-danger">
            <strong>⚠️ توجه:</strong>
            شما هنوز رمز خود را تغییر نداده‌اید. برای دسترسی به بقیه بخش‌ها، لطفاً ابتدا رمز خود را تغییر دهید.
        </div>
    @endif

    <div class="row g-3">

        {{-- اطلاعات حساب --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><strong>اطلاعات حساب</strong></div>
                <div class="card-body">

                    @if($errors->has('email') && !$errors->has('current_password'))
                        <div class="alert alert-danger">
                            @foreach($errors->get('email') as $e)
                                <div>{{ $e }}</div>
                            @endforeach
                        </div>
                    @endif

                    <form action="{{ route('panel.profile.update') }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="mb-3">
                            <label class="form-label">نام و نام خانوادگی</label>
                            <input type="text" class="form-control" value="{{ $user->name }}" readonly disabled>
                            <small class="text-muted">نام فقط توسط معلم قابل تغییر است.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">ایمیل (نام کاربری)</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $user->email) }}" dir="ltr" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                با تغییر ایمیل، ورود بعدی شما با ایمیل جدید خواهد بود.
                            </small>
                        </div>

                        <button class="btn btn-primary">ذخیره ایمیل</button>
                    </form>

                </div>
            </div>
        </div>

        {{-- تغییر رمز --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><strong>تغییر رمز عبور</strong></div>
                <div class="card-body">

                    @if($errors->hasAny(['current_password', 'password']))
                        <div class="alert alert-danger">
                            @foreach($errors->get('current_password') as $e)
                                <div>{{ $e }}</div>
                            @endforeach
                            @foreach($errors->get('password') as $e)
                                <div>{{ $e }}</div>
                            @endforeach
                        </div>
                    @endif

                    <form action="{{ route('panel.profile.password') }}" method="POST">
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