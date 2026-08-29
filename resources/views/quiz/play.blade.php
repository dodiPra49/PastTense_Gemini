@extends('layouts.app')

@section('title', 'Pengerjaan Kuis - ' . $session->participant_name)

@section('content')
<div class="max-w-4xl mx-auto space-y-8">

    <!-- Header Session Info -->
    <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 backdrop-blur-md shadow-xl flex flex-col sm:flex-row items-center justify-between gap-4 sticky top-20 z-40">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-600 text-white flex items-center justify-center font-bold text-lg shadow-lg">
                {{ strtoupper(substr($session->participant_name, 0, 1)) }}
            </div>
            <div>
                <div class="text-xs font-semibold text-brand-400 uppercase tracking-wider">Sesi Ujian Aktif</div>
                <h1 class="text-xl font-bold text-white">{{ $session->participant_name }}</h1>
            </div>
        </div>

        <div class="flex items-center gap-4 text-sm">
            <div class="bg-slate-800/90 px-4 py-2 rounded-xl border border-slate-700/80 text-slate-300 flex items-center gap-2">
                <i class="fa-solid fa-list-check text-brand-400"></i>
                <span>Total: <strong>10 Soal Pilihan Ganda</strong></span>
            </div>
            <div class="bg-indigo-500/10 text-indigo-300 border border-indigo-500/20 px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-1.5">
                <span id="answered-counter" class="font-bold text-brand-400">0</span>/10 Terjawab
            </div>
        </div>
    </div>

    <!-- Quiz Form -->
    <form action="{{ route('quiz.submit', $session->id) }}" method="POST" id="quizForm" class="space-y-6">
        @csrf

        @foreach($session->questions as $index => $q)
            <div class="p-6 sm:p-8 rounded-2xl bg-slate-900/60 border border-slate-800 shadow-xl space-y-5 transition-all hover:border-slate-700/80 question-card" data-question-id="{{ $q->id }}">
                <!-- Question Top Bar -->
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-800/80 pb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-brand-500/20 text-brand-300 font-bold flex items-center justify-center text-sm border border-brand-500/30">
                            {{ $index + 1 }}
                        </span>
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Pertanyaan {{ $index + 1 }} dari 10
                        </span>
                    </div>

                    <!-- Topic Badge -->
                    <span class="px-3 py-1 rounded-full text-xs font-medium {{ str_contains($q->topic, 'Past') ? 'bg-amber-500/10 text-amber-300 border border-amber-500/20' : 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/20' }}">
                        <i class="fa-solid {{ str_contains($q->topic, 'Past') ? 'fa-history' : 'fa-clock-rotate-left' }} mr-1"></i>
                        {{ $q->topic }}
                    </span>
                </div>

                <!-- Question Text -->
                <div class="text-lg sm:text-xl font-semibold text-slate-100 leading-relaxed">
                    {{ $q->question_text }}
                </div>

                <!-- Multiple Choice Options -->
                <div class="grid grid-cols-1 gap-3 pt-2">
                    @foreach(['A' => $q->option_a, 'B' => $q->option_b, 'C' => $q->option_c, 'D' => $q->option_d] as $optKey => $optVal)
                        <label class="group flex items-start gap-4 p-4 rounded-xl bg-slate-950/50 border border-slate-800 hover:border-brand-500/50 hover:bg-slate-800/40 cursor-pointer transition-all option-label">
                            <div class="flex items-center h-6">
                                <input 
                                    type="radio" 
                                    name="answers[{{ $q->id }}]" 
                                    value="{{ $optKey }}" 
                                    class="w-4 h-4 text-brand-500 bg-slate-900 border-slate-700 focus:ring-brand-500 focus:ring-2 option-radio"
                                    onchange="updateProgress()"
                                >
                            </div>
                            <div class="flex items-start gap-3 flex-grow text-sm sm:text-base">
                                <span class="w-6 h-6 rounded-md bg-slate-800 text-slate-300 flex items-center justify-center font-bold text-xs group-hover:bg-brand-500 group-hover:text-white transition-colors flex-shrink-0">
                                    {{ $optKey }}
                                </span>
                                <span class="text-slate-300 group-hover:text-white transition-colors font-medium">
                                    {{ $optVal }}
                                </span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach

        <!-- Submit Action Card -->
        <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-2xl">
            <div>
                <h3 class="font-bold text-white text-base">Sudah yakin dengan seluruh jawaban?</h3>
                <p class="text-xs text-slate-400 mt-0.5">Sistem akan segera menghitung skor akhir dan menampilkan dashboard hasil kuis.</p>
            </div>
            <button 
                type="button" 
                onclick="confirmSubmit()" 
                class="w-full sm:w-auto px-8 py-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-bold flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/20 transition-all hover:scale-105 active:scale-95"
            >
                <i class="fa-solid fa-paper-plane"></i>
                <span>Selesaikan & Lihat Hasil</span>
            </button>
        </div>
    </form>
</div>

<script>
    function updateProgress() {
        const totalQuestions = 10;
        const radios = document.querySelectorAll('.option-radio:checked');
        const count = radios.length;
        document.getElementById('answered-counter').innerText = count;

        // Highlight selected options visually
        document.querySelectorAll('.option-label').forEach(label => {
            const radio = label.querySelector('.option-radio');
            if (radio && radio.checked) {
                label.classList.add('border-brand-500', 'bg-brand-500/10');
            } else {
                label.classList.remove('border-brand-500', 'bg-brand-500/10');
            }
        });
    }

    function confirmSubmit() {
        const answered = document.querySelectorAll('.option-radio:checked').length;
        if (answered < 10) {
            const unanswered = 10 - answered;
            if (!confirm(`Masih ada ${unanswered} soal yang belum dijawab. Apakah Anda yakin ingin mengirimkan kuis sekarang?`)) {
                return;
            }
        }
        document.getElementById('quizForm').submit();
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', updateProgress);
</script>
@endsection
