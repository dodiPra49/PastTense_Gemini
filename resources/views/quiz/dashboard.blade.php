@extends('layouts.app')

@section('title', 'Dashboard Hasil Kuis - ' . $session->participant_name)

@section('content')
<div class="max-w-5xl mx-auto space-y-10">

    <!-- Hero Score Summary Card -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950 border border-slate-700/80 p-8 sm:p-12 shadow-2xl">
        <div class="absolute -right-20 -top-20 w-72 h-72 bg-brand-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-20 -bottom-20 w-72 h-72 bg-purple-500/15 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative flex flex-col md:flex-row items-center justify-between gap-8">
            <!-- Left Info -->
            <div class="text-center md:text-left space-y-3">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-800/80 border border-slate-700 text-slate-300 text-xs font-semibold">
                    <i class="fa-solid fa-square-poll-vertical text-brand-400"></i> Hasil Evaluasi Akhir
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-white">
                    Dashboard Nilai Peserta
                </h1>
                <p class="text-slate-300 text-base">
                    Nama Peserta: <strong class="text-white text-lg">{{ $session->participant_name }}</strong>
                </p>
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-4 text-xs text-slate-400 pt-1">
                    <span><i class="fa-regular fa-clock mr-1 text-brand-400"></i> {{ $session->updated_at->format('d M Y, H:i') }}</span>
                    <span>•</span>
                    <span><i class="fa-solid fa-database mr-1 text-brand-400"></i> MySQL: <code class="text-slate-300">past_tense</code></span>
                </div>
            </div>

            <!-- Right Score Badge Circle -->
            <div class="flex flex-col items-center">
                <div class="relative w-40 h-40 rounded-full flex flex-col items-center justify-center border-4 {{ $session->final_score >= 70 ? 'border-emerald-500 bg-emerald-500/10 shadow-emerald-500/20' : 'border-amber-500 bg-amber-500/10 shadow-amber-500/20' }} shadow-2xl backdrop-blur-md">
                    <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Skor Akhir</span>
                    <span class="text-5xl font-black text-white tracking-tight my-1">{{ number_format($session->final_score, 0) }}</span>
                    <span class="text-xs font-semibold text-slate-400">dari 100</span>
                </div>
                <div class="mt-3">
                    @if($session->final_score >= 80)
                        <span class="px-4 py-1 rounded-full bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 text-xs font-bold flex items-center gap-1.5 shadow-sm">
                            <i class="fa-solid fa-crown text-amber-400"></i> Sangat Memuaskan
                        </span>
                    @elseif($session->final_score >= 60)
                        <span class="px-4 py-1 rounded-full bg-brand-500/20 border border-brand-500/40 text-brand-300 text-xs font-bold flex items-center gap-1.5 shadow-sm">
                            <i class="fa-solid fa-circle-check text-brand-400"></i> Lulus Kuis
                        </span>
                    @else
                        <span class="px-4 py-1 rounded-full bg-rose-500/20 border border-rose-500/40 text-rose-300 text-xs font-bold flex items-center gap-1.5 shadow-sm">
                            <i class="fa-solid fa-rotate text-rose-400"></i> Perlu Latihan Lagi
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- 4 Key Metrics Bar -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-8 pt-8 border-t border-slate-700/60">
            <div class="p-4 rounded-xl bg-slate-900/70 border border-slate-800 text-center">
                <div class="text-xs text-slate-400 font-medium">Total Soal</div>
                <div class="text-2xl font-extrabold text-white mt-1">10</div>
            </div>
            <div class="p-4 rounded-xl bg-slate-900/70 border border-slate-800 text-center">
                <div class="text-xs text-emerald-400 font-medium">Jawaban Benar</div>
                <div class="text-2xl font-extrabold text-emerald-400 mt-1">{{ $session->correct_answers }}</div>
            </div>
            <div class="p-4 rounded-xl bg-slate-900/70 border border-slate-800 text-center">
                <div class="text-xs text-rose-400 font-medium">Jawaban Salah</div>
                <div class="text-2xl font-extrabold text-rose-400 mt-1">{{ $session->total_questions - $session->correct_answers }}</div>
            </div>
            <div class="p-4 rounded-xl bg-slate-900/70 border border-slate-800 text-center">
                <div class="text-xs text-brand-400 font-medium">Akurasi</div>
                <div class="text-2xl font-extrabold text-brand-300 mt-1">{{ number_format(($session->correct_answers / $session->total_questions) * 100, 0) }}%</div>
            </div>
        </div>
    </div>

    <!-- RETAKE QUIZ ACTION / Pertanyaan Ulangi Kuis -->
    <div class="p-6 sm:p-8 rounded-2xl bg-gradient-to-r from-brand-950/80 via-slate-900 to-indigo-950/80 border-2 border-brand-500/40 shadow-xl flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="space-y-1 text-center md:text-left">
            <h2 class="text-xl font-bold text-white flex items-center justify-center md:justify-start gap-2">
                <i class="fa-solid fa-arrows-rotate text-brand-400 animate-spin-slow"></i>
                Ingin Mengulang Kuis Lagi?
            </h2>
            <p class="text-sm text-slate-300">
                Uji kembali kemampuan Anda dengan <strong>10 soal baru</strong> yang digenerate langsung oleh Google Gemini AI.
            </p>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-3">
            <form action="{{ route('quiz.retake', $session->id) }}" method="POST">
                @csrf
                <button 
                    type="submit" 
                    class="px-6 py-3 rounded-xl bg-gradient-to-r from-brand-500 to-indigo-600 hover:from-brand-600 hover:to-indigo-700 text-white font-bold text-sm flex items-center gap-2 shadow-lg shadow-brand-500/25 transition-all hover:scale-105 active:scale-95"
                >
                    <i class="fa-solid fa-rotate-right"></i>
                    <span>Ya, Ulangi Tes Baru (10 Soal Baru)</span>
                </button>
            </form>

            <a 
                href="{{ route('quiz.index') }}" 
                class="px-5 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-sm border border-slate-700 transition-all hover:text-white"
            >
                <i class="fa-solid fa-house mr-1"></i> Kembali ke Beranda
            </a>
        </div>
    </div>

    <!-- Question by Question Breakdown -->
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-list-check text-brand-400"></i> Pembahasan & Evaluasi 10 Soal
            </h2>
            <span class="text-xs text-slate-400">Detail Jawaban Anda vs Kunci Jawaban</span>
        </div>

        <div class="space-y-6">
            @foreach($session->questions as $index => $q)
                @php
                    $userAns = $q->userAnswer;
                    $selected = $userAns?->selected_answer;
                    $isCorrect = $userAns?->is_correct;
                @endphp

                <div class="rounded-2xl bg-slate-900/60 border {{ $isCorrect ? 'border-emerald-500/40 bg-emerald-950/10' : 'border-rose-500/40 bg-rose-950/10' }} p-6 sm:p-7 shadow-lg space-y-4">
                    <!-- Question Top Header -->
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg {{ $isCorrect ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' : 'bg-rose-500/20 text-rose-300 border-rose-500/30' }} font-bold flex items-center justify-center text-xs border">
                                {{ $index + 1 }}
                            </span>
                            <span class="text-xs font-semibold text-slate-300">
                                Topik: <strong>{{ $q->topic }}</strong>
                            </span>
                        </div>

                        <!-- Status Badge -->
                        @if($isCorrect)
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 flex items-center gap-1.5">
                                <i class="fa-solid fa-check"></i> Jawaban Benar (+10 Poin)
                            </span>
                        @else
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30 flex items-center gap-1.5">
                                <i class="fa-solid fa-xmark"></i> Jawaban Salah (0 Poin)
                            </span>
                        @endif
                    </div>

                    <!-- Question Text -->
                    <div class="text-base sm:text-lg font-semibold text-white">
                        {{ $q->question_text }}
                    </div>

                    <!-- Options Grid with highlights -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        @foreach(['A' => $q->option_a, 'B' => $q->option_b, 'C' => $q->option_c, 'D' => $q->option_d] as $optKey => $optVal)
                            @php
                                $isThisCorrect = ($optKey === $q->correct_answer);
                                $isThisSelected = ($optKey === $selected);
                            @endphp

                            <div class="p-3.5 rounded-xl text-sm border flex items-start gap-3 transition-all
                                @if($isThisCorrect)
                                    bg-emerald-500/15 border-emerald-500/50 text-emerald-200 font-semibold
                                @elseif($isThisSelected && !$isCorrect)
                                    bg-rose-500/15 border-rose-500/50 text-rose-200 font-semibold
                                @else
                                    bg-slate-950/40 border-slate-800 text-slate-400
                                @endif
                            ">
                                <span class="w-6 h-6 rounded-md flex items-center justify-center font-bold text-xs flex-shrink-0
                                    @if($isThisCorrect)
                                        bg-emerald-500 text-white
                                    @elseif($isThisSelected && !$isCorrect)
                                        bg-rose-500 text-white
                                    @else
                                        bg-slate-800 text-slate-400
                                    @endif
                                ">
                                    {{ $optKey }}
                                </span>
                                <div class="flex-grow">
                                    <span>{{ $optVal }}</span>
                                    @if($isThisSelected)
                                        <span class="ml-2 text-xs px-2 py-0.5 rounded {{ $isCorrect ? 'bg-emerald-500/30 text-emerald-300' : 'bg-rose-500/30 text-rose-300' }} font-bold">
                                            (Pilihan Anda)
                                        </span>
                                    @endif
                                    @if($isThisCorrect && !$isThisSelected)
                                        <span class="ml-2 text-xs px-2 py-0.5 rounded bg-emerald-500/30 text-emerald-300 font-bold">
                                            (Kunci Jawaban)
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- AI Explanation Box -->
                    @if($q->explanation)
                        <div class="mt-3 p-4 rounded-xl bg-slate-950/70 border border-slate-800/90 text-slate-300 text-xs sm:text-sm leading-relaxed flex items-start gap-3">
                            <div class="w-7 h-7 rounded-lg bg-indigo-500/20 text-indigo-400 flex items-center justify-center flex-shrink-0 border border-indigo-500/30">
                                <i class="fa-solid fa-lightbulb"></i>
                            </div>
                            <div>
                                <strong class="text-indigo-300 block mb-0.5">Penjelasan & Tata Bahasa (AI Tutor):</strong>
                                <span>{{ $q->explanation }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Bottom Retake Floating Banner -->
    <div class="text-center py-6">
        <form action="{{ route('quiz.retake', $session->id) }}" method="POST" class="inline-block">
            @csrf
            <button 
                type="submit" 
                class="px-8 py-4 rounded-2xl bg-gradient-to-r from-brand-500 via-indigo-600 to-purple-600 hover:from-brand-600 hover:to-purple-700 text-white font-extrabold text-base flex items-center gap-3 shadow-xl shadow-brand-500/30 transition-all hover:scale-105 active:scale-95"
            >
                <i class="fa-solid fa-rotate-right text-lg"></i>
                <span>Mulai Sesi Ujian Baru (10 Soal Baru)</span>
            </button>
        </form>
    </div>
</div>
@endsection
