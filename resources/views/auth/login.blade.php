<!-- resources/views/auth/login.blade.php -->
<x-app-layout>
    <div
        class="flex min-h-screen items-center justify-center bg-gradient-to-br from-blue-500 to-purple-600 px-4 py-12 sm:px-6 lg:px-8">
        <div class="w-full max-w-md">
            <div class="rounded-lg bg-white p-8 shadow-2xl">
                <!-- Logo/Header -->
                <div class="mb-8 text-center">
                    <h1 class="mb-2 text-3xl font-bold text-gray-800">
                        {{ config('app.name') }}
                    </h1>
                </div>

                <!-- Error Messages -->
                @if ($errors->any())
                    <div class="mb-6 rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700">
                        @foreach ($errors->all() as $error)
                            <p class="text-sm">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <!-- Success Messages -->
                @if (session('message'))
                    <div class="mb-6 rounded border border-green-400 bg-green-100 px-4 py-3 text-green-700">
                        <p class="text-sm">{{ session('message') }}</p>
                    </div>
                @endif

                <!-- Login Form -->
                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <!-- Username -->
                    <div>
                        <label for="username" class="mb-2 block text-sm font-medium text-gray-700">
                            Benutzername
                        </label>
                        <input id="username" type="text" name="username" value="{{ old('username') }}"
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 transition duration-200 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required autofocus autocomplete="username">
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="mb-2 block text-sm font-medium text-gray-700">
                            Passwort
                        </label>
                        <input id="password" type="password" name="password"
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 transition duration-200 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required autocomplete="current-password">
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input id="remember" type="checkbox" name="remember"
                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label for="remember" class="ml-2 block text-sm text-gray-700">
                            Angemeldet bleiben
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                        class="focus:shadow-outline w-full transform rounded-lg bg-blue-600 px-4 py-3 font-bold text-white transition duration-200 hover:scale-105 hover:bg-blue-700 focus:outline-none">
                        Anmelden
                    </button>
                </form>

                <!-- Footer Info -->
                <div class="mt-6 text-center text-xs text-gray-500">
                    <p>{{ config('app.name') }}</p>
                    <p>© {{ date('Y') }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
