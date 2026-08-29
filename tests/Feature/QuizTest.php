<?php

namespace Tests\Feature;

use App\Models\QuizSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_home_page(): void
    {
        $response = $this->get(route('quiz.index'));
        $response->assertStatus(200);
        $response->assertSee('Past &amp; Perfect Tense', false);
        $response->assertSee('Simple Past Tense');
        $response->assertSee('Simple Perfect Tense');
    }

    public function test_can_start_quiz_session_with_10_questions(): void
    {
        $response = $this->post(route('quiz.start'), [
            'participant_name' => 'Dodi Agusri',
        ]);

        $this->assertDatabaseHas('quiz_sessions', [
            'participant_name' => 'Dodi Agusri',
            'total_questions' => 10,
            'status' => 'in_progress',
        ]);

        $session = QuizSession::first();
        $this->assertNotNull($session);
        $this->assertEquals(10, $session->questions()->count());

        $response->assertRedirect(route('quiz.play', $session->id));
    }

    public function test_can_play_and_submit_answers_to_view_dashboard(): void
    {
        $this->post(route('quiz.start'), [
            'participant_name' => 'Adhy',
        ]);

        $session = QuizSession::first();
        $questions = $session->questions()->get();

        $playResponse = $this->get(route('quiz.play', $session->id));
        $playResponse->assertStatus(200);
        $playResponse->assertSee('Adhy');

        // Submit 8 correct answers and 2 incorrect answers
        $answers = [];
        foreach ($questions as $index => $q) {
            if ($index < 8) {
                $answers[$q->id] = $q->correct_answer;
            } else {
                $answers[$q->id] = $q->correct_answer === 'A' ? 'B' : 'A';
            }
        }

        $submitResponse = $this->post(route('quiz.submit', $session->id), [
            'answers' => $answers,
        ]);

        $submitResponse->assertRedirect(route('quiz.dashboard', $session->id));

        $session->refresh();
        $this->assertEquals('completed', $session->status);
        $this->assertEquals(8, $session->correct_answers);
        $this->assertEquals(80.0, $session->final_score);
        $this->assertEquals(10, $session->userAnswers()->count());

        // Check Dashboard view
        $dashboardResponse = $this->get(route('quiz.dashboard', $session->id));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Dashboard Nilai Peserta');
        $dashboardResponse->assertSee('80');
        $dashboardResponse->assertSee('Ingin Mengulang Kuis Lagi?');
    }

    public function test_retake_generates_new_and_different_questions(): void
    {
        $this->post(route('quiz.start'), [
            'participant_name' => 'Budi Pratama',
        ]);

        $session1 = QuizSession::first();
        $session1Questions = $session1->questions()->pluck('question_text')->toArray();
        $this->assertCount(10, $session1Questions);

        // Retake quiz for the same participant
        $retakeResponse = $this->post(route('quiz.retake', $session1->id));

        $session2 = QuizSession::where('id', '!=', $session1->id)->first();
        $this->assertNotNull($session2);
        $this->assertEquals('Budi Pratama', $session2->participant_name);

        $session2Questions = $session2->questions()->pluck('question_text')->toArray();
        $this->assertCount(10, $session2Questions);

        // Verify that the new session's questions do not overlap with the previous session's questions
        $intersection = array_intersect($session1Questions, $session2Questions);
        $this->assertEmpty($intersection, 'Soal-soal di sesi baru tidak boleh sama dengan soal di sesi sebelumnya.');

        $retakeResponse->assertRedirect(route('quiz.play', $session2->id));
    }
}
