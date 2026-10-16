@if (session('status') || session('error') || $errors->any())
    <div class="mb-5 space-y-2" aria-live="polite">
        @if (session('status'))
            <p role="status" class="rounded-xl border border-cine-success/20 bg-cine-success/10 px-4 py-3 text-sm text-cine-success">{{ session('status') }}</p>
        @endif
        @if (session('error'))
            <p role="alert" class="rounded-xl border border-cine-danger/20 bg-cine-danger/10 px-4 py-3 text-sm text-cine-danger">{{ session('error') }}</p>
        @endif
        @if ($errors->any())
            <p role="alert" class="rounded-xl border border-cine-danger/20 bg-cine-danger/10 px-4 py-3 text-sm text-cine-danger">{{ $errors->first() }}</p>
        @endif
    </div>
@endif
