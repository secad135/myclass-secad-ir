@extends('layouts.admin')

@section('title', 'گزارش ورود کاربران')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">لاگ ورود کاربران</h4>
        <small class="text-muted">این گزارش فقط برای ادمین قابل مشاهده است.</small>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-bordered align-middle bg-white mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>کاربر</th>
                        <th>نقش</th>
                        <th>تاریخ و ساعت</th>
                        <th>آی‌پی (IP)</th>
                        <th>دستگاه / مرورگر</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td data-label="#">{{ $log->id }}</td>
                            <td data-label="کاربر">
                                <strong>{{ $log->user?->name ?? 'کاربر حذف شده' }}</strong>
                                <br><small class="text-muted">{{ $log->user?->email }}</small>
                            </td>
                            <td data-label="نقش">
                                @if($log->user?->is_admin)
                                    <span class="badge bg-danger">مدیرکل</span>
                                @elseif($log->user?->is_school_manager)
                                    <span class="badge bg-warning text-dark">مسئول مدرسه</span>
                                @elseif($log->user?->is_student)
                                    <span class="badge bg-info text-dark">دانش‌آموز</span>
                                @elseif($log->user?->is_teacher)
                                    <span class="badge bg-primary">معلم</span>
                                @else
                                    <span class="badge bg-secondary">نامشخص</span>
                                @endif
                            </td>
                            <td data-label="تاریخ و ساعت">
                                {{ jdate($log->created_at, 'EEEE yyyy/MM/dd HH:mm') }}
                            </td>
                            <td data-label="آی‌پی" dir="ltr">
                                <code>{{ $log->ip_address ?? '—' }}</code>
                            </td>
                            <td data-label="دستگاه / مرورگر">
                                <div style="max-width: 250px; font-size: 0.75rem; white-space: normal; word-break: break-word; line-height: 1.5;">
                                    {{ $log->user_agent ?? '—' }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">هیچ لاگی ثبت نشده است.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $logs->links() }}
        </div>
    </div>

@endsection
