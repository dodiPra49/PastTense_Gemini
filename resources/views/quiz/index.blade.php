@extends('layouts.app')

@section('title', 'Beranda - AI Past & Perfect Tense Quiz')

@section('content')
<div class="space-y-12">
    <!-- Hero Section -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950/60 to-slate-900 border border-slate-800 p-8 sm:p-12 shadow-2xl">
        <div class="absolute -right-12 -bottom-12 w-80 h-80 bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-12 -top-12 w-80 h-80 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            <div class="lg:col-span-7 space-y-5">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-500/10 border border-brand-500/20 text-brand-300 text-xs font-semibold">
                    <i class="fa-solid fa-sparkles text-brand-400"></i> AI-Powered Grammar Assessment
                </div>
                <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-white leading-tight">
                    Uji Kemampuan <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-400 via-indigo-300 to-purple-400">
                        Past & Perfect Tense
                    </span>
                </h1>
                <p class="text-slate-300 text-sm sm:text-base leading-relaxed">
                    Sistem kuis interaktif dengan <strong>10 soal</strong> yang digenerate oleh <strong>Google Gemini AI</strong>. Dapatkan evaluasi langsung, skor akurat, serta pembahasan komprehensif untuk setiap soal.
                </p>

                <!-- Start Form -->
                <form action="{{ route('quiz.start') }}" method="POST" class="pt-2">
                    @csrf
                    <div class="space-y-3">
                        <label for="participant_name" class="block text-sm font-medium text-slate-200">
                            Masukkan Nama Peserta untuk Memulai:
                        </label>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="relative flex-grow">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                                    <i class="fa-solid fa-user"></i>
                                </span>
                                <input 
                                    type="text" 
                                    name="participant_name" 
                                    id="participant_name" 
                                    required 
                                    placeholder="Contoh: Dodi Agusri" 
                                    class="w-full pl-11 pr-4 py-3.5 rounded-xl bg-slate-950/80 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all shadow-inner"
                                    value="{{ old('participant_name') }}"
                                >
                            </div>
                            <button 
                                type="submit" 
                                class="px-8 py-3.5 rounded-xl bg-gradient-to-r from-brand-500 to-indigo-600 hover:from-brand-600 hover:to-indigo-700 text-white font-semibold flex items-center justify-center gap-2 shadow-lg shadow-brand-500/25 transition-all hover:scale-[1.02] active:scale-[0.98]"
                            >
                                <span>Mulai Kuis (10 Soal)</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Feature Card Preview -->
            <div class="lg:col-span-5 grid grid-cols-1 gap-4">
                <div class="p-5 rounded-2xl bg-slate-800/60 border border-slate-700/60 backdrop-blur-sm shadow-lg space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center border border-amber-500/20">
                            <i class="fa-solid fa-history"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-white text-sm">Simple Past Tense (5 Soal)</h3>
                            <p class="text-xs text-slate-400">Verb 2, Did/Didn't, Was/Were, Waktu Lampau</p>
                        </div>
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-slate-800/60 border border-slate-700/60 backdrop-blur-sm shadow-lg space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center border border-emerald-500/20">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-white text-sm">Simple Perfect Tense (5 Soal)</h3>
                            <p class="text-xs text-slate-400">Have/Has + Verb 3, Since/For, Already/Yet</p>
                        </div>
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-slate-800/60 border border-slate-700/60 backdrop-blur-sm shadow-lg space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-purple-500/10 text-purple-400 flex items-center justify-center border border-purple-500/20">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-white text-sm">Dashboard Skoring Real-Time</h3>
                            <p class="text-xs text-slate-400">Skor 0-100, Kunci Jawaban & Penjelasan AI</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Participants / Sesi Kuis Sebelumnya -->
    <div class="rounded-2xl bg-slate-900/60 border border-slate-800 p-6 sm:p-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-trophy text-amber-400"></i> Riwayat Skor Peserta Terbaru
                </h2>
                <p class="text-xs text-slate-400 mt-1">Data tersimpan di database MySQL <code class="text-brand-400">past_tense</code></p>
            </div>
        </div>

        @if($recentSessions->isEmpty())
            <div class="text-center py-10 text-slate-500 border border-dashed border-slate-800 rounded-xl">
                <i class="fa-solid fa-inbox text-3xl mb-2 text-slate-600"></i>
                <p class="text-sm">Belum ada peserta yang menyelesaikan kuis. Jadilah yang pertama!</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="text-xs uppercase bg-slate-800/80 text-slate-400 border-b border-slate-700">
                        <tr>
                            <th class="py-3 px-4 rounded-l-lg">Peserta</th>
                            <th class="py-3 px-4 text-center">Jawaban Benar</th>
                            <th class="py-3 px-4 text-center">Skor Akhir</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right rounded-r-lg">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80">
                        @foreach($recentSessions as $session)
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="py-3 px-4 font-semibold text-white">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-brand-500/20 text-brand-300 flex items-center justify-center text-xs font-bold">
                                            {{ strtoupper(substr($session->participant_name, 0, 1)) }}
                                        </div>
                                        <span>{{ $session->participant_name }}</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="text-emerald-400 font-bold">{{ $session->correct_answers }}</span> / {{ $session->total_questions }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-block px-2.5 py-1 rounded-md text-xs font-bold {{ $session->final_score >= 70 ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30' }}">
                                        {{ $session->final_score }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($session->final_score >= 80)
                                        <span class="text-xs text-emerald-400 font-medium"><i class="fa-solid fa-star text-amber-400"></i> Sangat Baik</span>
                                    @elseif($session->final_score >= 60)
                                        <span class="text-xs text-brand-300 font-medium">Lulus</span>
                                    @else
                                        <span class="text-xs text-rose-400 font-medium">Perlu Latihan</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a href="{{ route('quiz.dashboard', $session->id) }}" class="inline-flex items-center gap-1 text-xs font-medium text-brand-400 hover:text-brand-300 transition-colors">
                                        <span>Lihat Dashboard</span>
                                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
