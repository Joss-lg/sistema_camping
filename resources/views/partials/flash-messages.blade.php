@if ($errors->any())
    <div class="lc-alert lc-alert-danger">
        <ul class="list-disc list-inside space-y-1 text-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (session('error'))
    <div class="lc-alert lc-alert-danger text-sm">
        {{ session('error') }}
    </div>
@endif

@if (session('warning'))
    <div class="lc-alert lc-alert-warning text-sm">
        {{ session('warning') }}
    </div>
@endif

@if (session('ok') || session('success'))
    <div class="lc-alert lc-alert-success text-sm">
        {{ session('ok') ?? session('success') }}
    </div>
@endif

@if (session('info'))
    <div class="lc-alert lc-alert-info text-sm">
        {{ session('info') }}
    </div>
@endif
