<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zugriff verweigert - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .festival-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .bounce-in {
            animation: bounceIn 0.8s ease-out;
        }
        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); opacity: 1; }
        }
        .shake {
            animation: shake 0.5s ease-in-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
    </style>
</head>
<body class="festival-bg min-h-screen flex items-center justify-center p-4">
    <div class="max-w-lg w-full">
        <!-- Error Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 text-center bounce-in">
            <!-- Icon -->
            <div class="mb-6">
                <div class="mx-auto w-24 h-24 bg-red-100 rounded-full flex items-center justify-center shake">
                    <svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 15v2m0 0v2m0-2h2m-2 0H10m9-7V8a3 3 0 00-3-3H8a3 3 0 00-3 3v1M5 10h14a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1v-7a1 1 0 011-1z"/>
                    </svg>
                </div>
            </div>

            <!-- Error Message -->
            <div class="mb-8">
                <h1 class="text-4xl font-bold text-gray-800 mb-4">403</h1>
                <h2 class="text-xl font-semibold text-gray-700 mb-4">Zugriff verweigert</h2>
                
                @if(isset($exception) && $exception->getMessage())
                    <p class="text-gray-600 mb-4">{{ $exception->getMessage() }}</p>
                @else
                    <p class="text-gray-600 mb-4">
                        {{ $message ?? 'Sie haben keine Berechtigung für diese Seite.' }}
                    </p>
                @endif

                <!-- Context-specific messages -->
                @auth
                    @if(!auth()->user()->is_admin)
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <div class="text-sm text-yellow-700">
                                    <strong>Hinweis:</strong> Diese Seite ist nur für Administratoren zugänglich.
                                </div>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <div class="text-sm text-blue-700">
                                <strong>Info:</strong> Melden Sie sich an, um fortzufahren.
                            </div>
                        </div>
                    </div>
                @endauth
            </div>

            <!-- Action Buttons -->
            <div class="space-y-4">
                @auth
                    <!-- If logged in -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ route('home') }}" 
                           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            Zur Hauptseite
                        </a>
                        
                        <button onclick="history.back()" 
                                class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-lg hover:bg-gray-700 transition duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Zurück
                        </button>
                    </div>

                    <!-- User Info -->
                    <div class="text-sm text-gray-500 pt-4 border-t border-gray-200">
                        Angemeldet als: <strong>{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</strong>
                        @if(!auth()->user()->is_admin)
                            <br>
                            <span class="text-orange-600">(Keine Administrator-Rechte)</span>
                        @endif
                    </div>
                @else
                    <!-- If not logged in -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ route('login') }}" 
                           class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition duration-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            Anmelden
                        </a>
                        
                        <button onclick="history.back()" 
                                class="inline-flex items-center px-6 py-3 bg-gray-600 text-white font-medium rounded-lg hover:bg-gray-700 transition duration-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Zurück
                        </button>
                    </div>
                @endauth
            </div>

            <!-- Footer -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <p class="text-xs text-gray-500">
                    {{ config('app.name') }} - Festival Backstage Control
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    Bei Problemen wenden Sie sich an Ihren Administrator
                </p>
            </div>
        </div>

        <!-- Additional Info Card for Admins -->
        @auth
            @if(auth()->user()->is_admin)
                <div class="mt-6 bg-white/10 backdrop-blur rounded-lg p-4 text-white text-sm">
                    <h3 class="font-medium mb-2">Administrator-Information:</h3>
                    <ul class="text-xs space-y-1 opacity-90">
                        <li>• Benutzer: {{ auth()->user()->username }}</li>
                        <li>• IP-Adresse: {{ request()->ip() }}</li>
                        <li>• Angeforderte URL: {{ request()->fullUrl() }}</li>
                        <li>• Zeitpunkt: {{ now()->format('d.m.Y H:i:s') }}</li>
                    </ul>
                </div>
            @endif
        @endauth
    </div>

    <script>
        // Auto-redirect after 30 seconds for non-admin users
        @if(!auth()->check() || !auth()->user()->is_admin)
            let countdown = 30;
            const redirectTimer = setInterval(() => {
                countdown--;
                if (countdown <= 0) {
                    window.location.href = '{{ auth()->check() ? route("home") : route("login") }}';
                }
            }, 1000);
            
            // Cancel redirect if user interacts with page
            document.addEventListener('click', () => clearInterval(redirectTimer));
            document.addEventListener('keydown', () => clearInterval(redirectTimer));
        @endif
    </script>
</body>
</html>