@if (session('status'))
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status" id="flash-status">
        {{ session('status') }}
    </div>
@endif
