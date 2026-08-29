<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-900 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Past & Perfect Tense AI Quiz')</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 text-slate-100 antialiased selection:bg-brand-500 selection:text-white">

    <!-- Header / Navbar -->
    <header class="border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('quiz.index') }}" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-500 flex items-center justify-center text-white shadow-lg shadow-brand-500/20 group-hover:scale-105 transition-transform">
                    <i class="fa-solid fa-graduation-cap text-lg"></i>
                </div>
                <div>
                    <span class="font-bold text-lg text-white group-hover:text-brand-400 transition-colors tracking-tight">
                        Tense<span class="text-brand-400">Master</span> AI
                    </span>
                    <span class="hidden sm:inline-block ml-2 px-2 py-0.5 text-xs font-semibold rounded-full bg-brand-500/10 text-brand-400 border border-brand-500/20">
                        Past & Perfect Tense
                    </span>
                </div>
            </a>

            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 text-xs text-slate-400 bg-slate-800/80 px-3 py-1.5 rounded-lg border border-slate-700/60">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Powered by <strong>Gemini AI</strong></span>
                </div>
                <a href="{{ route('quiz.index') }}" class="text-sm font-medium text-slate-300 hover:text-white transition-colors">
                    <i class="fa-solid fa-house mr-1"></i> Beranda
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-grow max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Flash Messages -->
        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 flex items-center gap-3 shadow-lg">
                <i class="fa-solid fa-circle-check text-xl flex-shrink-0 text-emerald-400"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if(session('info'))
            <div class="mb-6 p-4 rounded-xl bg-brand-500/10 border border-brand-500/30 text-brand-300 flex items-center gap-3 shadow-lg">
                <i class="fa-solid fa-circle-info text-xl flex-shrink-0 text-brand-400"></i>
                <div>{{ session('info') }}</div>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 flex items-center gap-3 shadow-lg">
                <i class="fa-solid fa-triangle-exclamation text-xl flex-shrink-0 text-rose-400"></i>
                <div>
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 bg-slate-950/70 text-slate-500 py-6 text-center text-xs">
        <div class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p>© {{ date('Y') }} Past & Simple Perfect Tense Quiz. Database MySQL: <code class="text-slate-400">past_tense</code></p>
            <p class="flex items-center gap-2">
                <i class="fa-solid fa-brain text-brand-400"></i> Integrasi Google Gemini AI
            </p>
        </div>
    </footer>

</body>
</html>
