<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuizSession;
use App\Models\UserAnswer;
use App\Services\GeminiQuizService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    /**
     * Display the quiz home page with participant input & previous sessions.
     */
    public function index()
    {
        $recentSessions = QuizSession::where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        return view('quiz.index', compact('recentSessions'));
    }

    /**
     * Start a new quiz session: generate 10 unique fresh questions with Gemini AI.
     */
    public function start(Request $request, GeminiQuizService $geminiService)
    {
        $request->validate([
            'participant_name' => 'required|string|min:2|max:100',
        ], [
            'participant_name.required' => 'Nama peserta wajib diisi sebelum memulai kuis.',
            'participant_name.min' => 'Nama peserta minimal 2 karakter.',
        ]);

        $participantName = trim($request->input('participant_name'));

        // Retrieve previously answered question texts for this participant to avoid duplicates
        $previousQuestions = Question::whereHas('quizSession', function ($query) use ($participantName) {
            $query->where('participant_name', $participantName);
        })->pluck('question_text')->toArray();

        // Generate 10 fresh, unique questions using Gemini AI
        $generatedQuestions = $geminiService->generateQuestions($previousQuestions);

        return DB::transaction(function () use ($participantName, $generatedQuestions) {
            $session = QuizSession::create([
                'participant_name' => $participantName,
                'total_questions' => count($generatedQuestions),
                'correct_answers' => 0,
                'final_score' => 0,
                'status' => 'in_progress',
            ]);

            foreach ($generatedQuestions as $q) {
                Question::create([
                    'quiz_session_id' => $session->id,
                    'topic' => $q['topic'],
                    'question_text' => $q['question'],
                    'option_a' => $q['options']['A'],
                    'option_b' => $q['options']['B'],
                    'option_c' => $q['options']['C'],
                    'option_d' => $q['options']['D'],
                    'correct_answer' => $q['correct_answer'],
                    'explanation' => $q['explanation'],
                ]);
            }

            return redirect()->route('quiz.play', $session->id);
        });
    }

    /**
     * Display the 10-question quiz interface.
     */
    public function play(QuizSession $session)
    {
        if ($session->status === 'completed') {
            return redirect()->route('quiz.dashboard', $session->id);
        }

        $session->load('questions');

        return view('quiz.play', compact('session'));
    }

    /**
     * Process submitted answers, calculate final score, and save results.
     */
    public function submit(Request $request, QuizSession $session)
    {
        if ($session->status === 'completed') {
            return redirect()->route('quiz.dashboard', $session->id);
        }

        $submittedAnswers = $request->input('answers', []);
        $questions = $session->questions()->get();

        $correctCount = 0;

        DB::transaction(function () use ($session, $questions, $submittedAnswers, &$correctCount) {
            foreach ($questions as $question) {
                $selected = $submittedAnswers[$question->id] ?? null;
                $isCorrect = ($selected !== null && strtoupper($selected) === strtoupper($question->correct_answer));

                if ($isCorrect) {
                    $correctCount++;
                }

                UserAnswer::updateOrCreate(
                    [
                        'quiz_session_id' => $session->id,
                        'question_id' => $question->id,
                    ],
                    [
                        'selected_answer' => $selected ? strtoupper($selected) : null,
                        'is_correct' => $isCorrect,
                    ]
                );
            }

            $totalQuestions = $questions->count() > 0 ? $questions->count() : 10;
            $finalScore = round(($correctCount / $totalQuestions) * 100, 2);

            $session->update([
                'correct_answers' => $correctCount,
                'final_score' => $finalScore,
                'status' => 'completed',
            ]);
        });

        return redirect()->route('quiz.dashboard', $session->id)
            ->with('success', 'Selamat! Kuis telah berhasil diselesaikan.');
    }

    /**
     * Display the final scoring dashboard with question-by-question review & retake option.
     */
    public function dashboard(QuizSession $session)
    {
        $session->load(['questions.userAnswer']);

        return view('quiz.dashboard', compact('session'));
    }

    /**
     * Retake quiz: Start a fresh session with 10 completely new questions for the same participant.
     */
    public function retake(QuizSession $session, GeminiQuizService $geminiService)
    {
        $participantName = $session->participant_name;

        // Retrieve previously answered question texts for this participant to avoid duplicates
        $previousQuestions = Question::whereHas('quizSession', function ($query) use ($participantName) {
            $query->where('participant_name', $participantName);
        })->pluck('question_text')->toArray();

        // Generate 10 fresh, unique questions excluding previous ones
        $generatedQuestions = $geminiService->generateQuestions($previousQuestions);

        return DB::transaction(function () use ($participantName, $generatedQuestions) {
            $newSession = QuizSession::create([
                'participant_name' => $participantName,
                'total_questions' => count($generatedQuestions),
                'correct_answers' => 0,
                'final_score' => 0,
                'status' => 'in_progress',
            ]);

            foreach ($generatedQuestions as $q) {
                Question::create([
                    'quiz_session_id' => $newSession->id,
                    'topic' => $q['topic'],
                    'question_text' => $q['question'],
                    'option_a' => $q['options']['A'],
                    'option_b' => $q['options']['B'],
                    'option_c' => $q['options']['C'],
                    'option_d' => $q['options']['D'],
                    'correct_answer' => $q['correct_answer'],
                    'explanation' => $q['explanation'],
                ]);
            }

            return redirect()->route('quiz.play', $newSession->id)
                ->with('info', "Sesi kuis baru dimulai dengan 10 soal baru untuk {$participantName}!");
        });
    }
}
