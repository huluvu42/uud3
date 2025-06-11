{{-- resources/views/componentsflash-messages.blade.php --}}

@if (session()->has('success'))
    <div class="mb-4 rounded border border-green-400 bg-green-100 px-4 py-3 text-green-700 shadow-sm">
        <div class="flex items-center">
            <span class="mr-2">✅</span>
            {{ session('success') }}
        </div>
    </div>
@endif

@if (session()->has('error'))
    <div class="mb-4 rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700 shadow-sm">
        <div class="flex items-center">
            <span class="mr-2">❌</span>
            {{ session('error') }}
        </div>
    </div>
@endif

@if (session()->has('warning'))
    <div class="mb-4 rounded border border-yellow-400 bg-yellow-100 px-4 py-3 text-yellow-700 shadow-sm">
        <div class="flex items-center">
            <span class="mr-2">⚠️</span>
            {{ session('warning') }}
        </div>
    </div>
@endif

@if (session()->has('info'))
    <div class="mb-4 rounded border border-blue-400 bg-blue-100 px-4 py-3 text-blue-700 shadow-sm">
        <div class="flex items-center">
            <span class="mr-2">ℹ️</span>
            <pre class="text-xs">{{ session('info') }}</pre>
        </div>
    </div>
@endif
