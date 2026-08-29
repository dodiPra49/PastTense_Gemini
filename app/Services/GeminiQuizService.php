<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiQuizService
{
    protected ?string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-1.5-flash');
    }

    /**
     * Generate 10 quiz questions (5 Simple Past Tense, 5 Simple Perfect Tense)
     * Ensuring questions are fresh and not in $excludedQuestionTexts.
     *
     * @param array $excludedQuestionTexts
     * @return array
     */
    public function generateQuestions(array $excludedQuestionTexts = []): array
    {
        if (empty($this->apiKey)) {
            Log::info('Gemini API key is not configured. Using diverse dynamic fallback question set.');
            return $this->getDynamicFallbackQuestions($excludedQuestionTexts);
        }

        try {
            $prompt = $this->buildPrompt($excludedQuestionTexts);

            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::timeout(30)->withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.95, // Higher temperature for maximum variety
                    'topK' => 50,
                    'topP' => 0.95,
                    'maxOutputTokens' => 2500,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                
                $parsedQuestions = $this->parseJsonText($text);
                if (!empty($parsedQuestions) && count($parsedQuestions) === 10) {
                    return $parsedQuestions;
                }
            }

            Log::warning('Gemini API response could not be parsed as valid 10-question JSON. Response: ' . $response->body());
        } catch (\Throwable $e) {
            Log::error('Error connecting to Gemini API: ' . $e->getMessage());
        }

        // Dynamic fallback if API fails or parsing fails
        return $this->getDynamicFallbackQuestions($excludedQuestionTexts);
    }

    /**
     * Build the structured prompt for Gemini AI with exclusion of previous questions
     */
    protected function buildPrompt(array $excludedQuestionTexts = []): string
    {
        $exclusionPrompt = '';
        if (!empty($excludedQuestionTexts)) {
            $recentExclusions = array_slice($excludedQuestionTexts, -25);
            $exclusionList = implode("\n- ", array_map('addslashes', $recentExclusions));
            $exclusionPrompt = <<<EXCLUDE

CRITICAL REQUIREMENT - DO NOT REPEAT:
The user has already answered the following questions in previous tests. You MUST generate 10 COMPLETELY NEW, UNIQUE questions with different verbs, subjects, scenarios, and contexts.
DO NOT use or duplicate any of these previous questions:
- {$exclusionList}
EXCLUDE;
        }

        $randomThemes = ['travel & holiday', 'science & discovery', 'cooking & food', 'school & university', 'technology & smartphones', 'music & art', 'sports & fitness', 'family & daily routines', 'nature & environment', 'history & monuments'];
        shuffle($randomThemes);
        $selectedThemes = implode(', ', array_slice($randomThemes, 0, 4));
        $randomSeed = rand(1000, 9999);

        return <<<PROMPT
You are an expert English grammar teacher and test maker. (Seed ID: {$randomSeed})
Generate exactly 10 FRESH, UNIQUE multiple-choice questions for an English grammar test:
- Exactly 5 questions on "Simple Past Tense" (Verb 2, did/didn't, was/were, irregular/regular verbs, time signals like yesterday, last night, in 2019, two days ago, when I was young).
- Exactly 5 questions on "Simple Perfect Tense" / "Present Perfect Tense" (have/has + Verb 3, since, for, already, yet, just, ever, never, so far, recently).

Themes to inspire sentence contexts: {$selectedThemes}.
Ensure varied sentence forms: affirmative, negative (didn't / hasn't / haven't), interrogative (Did you...? / Have you ever...?), and Wh-questions.
{$exclusionPrompt}

IMPORTANT: Return ONLY a valid JSON array of 10 objects, without any markdown code fence (no ```json or ```), and no introductory or concluding text.

JSON Structure:
[
  {
    "topic": "Simple Past Tense",
    "question": "Yesterday, Sarah _____ an interesting article about renewable energy.",
    "options": {
      "A": "read",
      "B": "reads",
      "C": "reading",
      "D": "has read"
    },
    "correct_answer": "A",
    "explanation": "'Yesterday' menandakan Simple Past Tense. Kata kerja bentuk kedua (V2) dari 'read' tetap ditulis 'read' (dilafalkan /red/)."
  }
]

Requirements:
1. "topic" must be either "Simple Past Tense" or "Simple Perfect Tense".
2. "options" must contain keys "A", "B", "C", and "D".
3. "correct_answer" must be one of "A", "B", "C", or "D".
4. "explanation" must be a clear, informative explanation written in Indonesian explaining why the answer is correct and why the tense rule applies.
PROMPT;
    }

    /**
     * Clean and parse JSON text from Gemini response
     */
    protected function parseJsonText(string $rawText): array
    {
        $cleaned = trim($rawText);
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $cleaned);
        $cleaned = preg_replace('/\s*```$/', '', $cleaned);
        $cleaned = trim($cleaned);

        $decoded = json_decode($cleaned, true);

        if (!is_array($decoded)) {
            return [];
        }

        $validQuestions = [];
        foreach ($decoded as $item) {
            if (
                isset($item['question'], $item['options'], $item['correct_answer']) &&
                isset($item['options']['A'], $item['options']['B'], $item['options']['C'], $item['options']['D']) &&
                in_array(strtoupper($item['correct_answer']), ['A', 'B', 'C', 'D'])
            ) {
                $validQuestions[] = [
                    'topic' => $item['topic'] ?? 'English Grammar',
                    'question' => $item['question'],
                    'options' => [
                        'A' => (string) $item['options']['A'],
                        'B' => (string) $item['options']['B'],
                        'C' => (string) $item['options']['C'],
                        'D' => (string) $item['options']['D'],
                    ],
                    'correct_answer' => strtoupper($item['correct_answer']),
                    'explanation' => $item['explanation'] ?? 'Jawaban yang tepat berdasarkan tata bahasa Inggris.',
                ];
            }
        }

        return $validQuestions;
    }

    /**
     * Dynamic fallback question selector that excludes previously answered questions
     */
    public function getDynamicFallbackQuestions(array $excludedQuestionTexts = []): array
    {
        $pastPool = $this->getSimplePastQuestionPool();
        $perfectPool = $this->getSimplePerfectQuestionPool();

        // Normalize excluded questions for comparison
        $normalizedExclusions = array_map(function ($text) {
            return strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $text)));
        }, $excludedQuestionTexts);

        // Filter unseen Past Tense questions
        $unseenPast = array_values(array_filter($pastPool, function ($item) use ($normalizedExclusions) {
            $norm = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $item['question'])));
            return !in_array($norm, $normalizedExclusions);
        }));

        // Filter unseen Perfect Tense questions
        $unseenPerfect = array_values(array_filter($perfectPool, function ($item) use ($normalizedExclusions) {
            $norm = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $item['question'])));
            return !in_array($norm, $normalizedExclusions);
        }));

        // If not enough unseen questions remain, reset pool and shuffle
        if (count($unseenPast) < 5) {
            $unseenPast = $pastPool;
        }
        if (count($unseenPerfect) < 5) {
            $unseenPerfect = $perfectPool;
        }

        shuffle($unseenPast);
        shuffle($unseenPerfect);

        $selectedPast = array_slice($unseenPast, 0, 5);
        $selectedPerfect = array_slice($unseenPerfect, 0, 5);

        // Combine and shuffle order of questions
        $combined = array_merge($selectedPast, $selectedPerfect);
        shuffle($combined);

        return $combined;
    }

    /**
     * Extensive pool of Simple Past Tense questions
     */
    protected function getSimplePastQuestionPool(): array
    {
        return [
            [
                'topic' => 'Simple Past Tense',
                'question' => 'My family and I _____ to Bali for vacation last summer.',
                'options' => ['A' => 'go', 'B' => 'went', 'C' => 'gone', 'D' => 'goes'],
                'correct_answer' => 'B',
                'explanation' => "Keterangan waktu 'last summer' menunjukkan aksi di masa lampau (Simple Past Tense), sehingga menggunakan Verb 2 dari 'go' yaitu 'went'.",
            ],
            [
                'topic' => 'Simple Past Tense',
                'question' => 'Why _____ you not attend the English class yesterday?',
                'options' => ['A' => 'do', 'B' => 'did', 'C' => 'have', 'D' => 'were'],
                'correct_answer' => 'B',
                'explanation' => "Untuk kalimat tanya lampau dengan kata kerja aksi (attend), auxiliary verb yang digunakan adalah 'did'.",
            ],
            [
                'topic' => 'Simple Past Tense',
                'question' => 'She _____ very tired after running five kilometers this morning.',
                'options' => ['A' => 'is', 'B' => 'was', 'C' => 'were', 'D' => 'been'],
                'correct_answer' => 'B',
                'explanation' => "Subjek tunggal 'She' pada Simple Past Tense menggunakan to be lampau 'was'.",
            ],
            [
                'topic' => 'Simple Past Tense',
                'question' => 'Alexander Fleming _____ penicillin in 1928.',
                'options' => ['A' => 'discovers', 'B' => 'has discovered', 'C' => 'discovered', 'D' => 'discover'],
                'correct_answer' => 'C',
                'explanation' => "Peristiwa dengan keterangan tahun spesifik di masa lalu ('in 1928') menggunakan Simple Past Tense dengan Verb 2 'discovered'.",
            ],
            [
                'topic' => 'Simple Past Tense',
                'question' => 'They _____ not see the accident happen on the highway.',
                'options' => ['A' => 'did', 'B' => 'do', 'C' => 'have', 'D' => 'are'],
                'correct_answer' => 'A',
                'explanation' => "Kalimat negatif pada Simple Past Tense menggunakan pola: Subject + did not + Verb 1 (see).",
            ],
            [
                'topic' => 'Simple Past Tense',
                'question' => 'The heavy storm _____ several old trees in our neighborhood last night.',
                'options' => ['A' => 'blows down', 'B' => 'blew down', 'C' => 'blown down', 'D' => 'blowing down'],
                'correct_answer' => 'B',
                'explanation' => "'Last night' adalah penanda waktu lampau. Bentuk V2 dari kata kerja 'blow' adalah 'blew'.",
            ],
            [
                'topic' => 'Simple Past Tense',
                'question' => 'Where _____ you find my lost keys two days ago?',
                'options' => ['A' => 'do', 'B' => 'did', 'C' => 'have', 'D' => 'are'],
                'correct_answer' => 'B',
                'explanation' => "Kalimat tanya lampau dengan penanda 'two days ago' menggunakan kata kerja bantu 'did'.",
            ],
            [
                'topic' => 'Simple Past Tense',
                'question' => 'The chef _____ a delicious chocolate cake for the birthday party last Saturday.',
                'options' => ['A' => 'baked', 'B' => 'bakes', 'C' => 'has baked', 'D' => 'baking'],
                'correct_answer' => 'A',
                'explanation' => "Aksi yang terjadi dan selesai di masa lalu ('last Saturday') menggunakan Verb 2 reguler 'baked'.",
            ],
            [
                'topic' => 'Simple Past Tense',
                'question' => 'We _____ at the concert hall until midnight yesterday.',
                'options' => ['A' => 'was', 'B' => 'were', 'C' => 'are', 'D' => 'have been'],
                'correct_answer' => 'B',
                'explanation' => "Subjek jamak 'We' menggunakan to be lampau 'were' untuk Simple Past Tense.",
            ],
            [
                'topic' => 'Simple Past Tense',
                'question' => 'Leonardo da Vinci _____ the famous Mona Lisa painting.',
                'options' => ['A' => 'paints', 'B' => 'painted', 'C' => 'has painted', 'D' => 'painting'],
                'correct_answer' => 'B',
                'explanation' => "Fakta sejarah yang dilakukan oleh tokoh masa lalu menggunakan bentuk Simple Past Tense ('painted').",
            ],
            [
                'topic' => 'Simple Past Tense',
                'question' => 'Rina _____ a new smartphone last week because her old one broke.',
                'options' => ['A' => 'buys', 'B' => 'bought', 'C' => 'has bought', 'D' => 'buy'],
                'correct_answer' => 'B',
                'explanation' => "Bentuk irregular verb 2 dari 'buy' adalah 'bought', digunakan karena ada penanda 'last week'.",
            ],
            [
                'topic' => 'Simple Past Tense',
                'question' => '_____ the students finish their final exams on time yesterday?',
                'options' => ['A' => 'Did', 'B' => 'Do', 'C' => 'Have', 'D' => 'Were'],
                'correct_answer' => 'A',
                'explanation' => "Pertanyaan Yes/No untuk aksi lampau menggunakan 'Did' + Subject + Verb 1 ('finish').",
            ],
            [
                'topic' => 'Simple Past Tense',
                'question' => 'I _____ an interesting documentary about deep sea creatures yesterday evening.',
                'options' => ['A' => 'watch', 'B' => 'watched', 'C' => 'have watched', 'D' => 'watching'],
                'correct_answer' => 'B',
                'explanation' => "Penanda waktu lampau 'yesterday evening' mengharuskan penggunaan Verb 2 ('watched').",
            ],
            [
                'topic' => 'Simple Past Tense',
                'question' => 'The train _____ the station promptly at 8:00 AM this morning.',
                'options' => ['A' => 'leaves', 'B' => 'left', 'C' => 'has left', 'D' => 'leaving'],
                'correct_answer' => 'B',
                'explanation' => "Bentuk Verb 2 dari 'leave' adalah 'left', menandakan keberangkatan kereta di waktu yang telah lewat pagi ini.",
            ],
            [
                'topic' => 'Simple Past Tense',
                'question' => 'They _____ not know the answer to the difficult question during the exam.',
                'options' => ['A' => 'did', 'B' => 'do', 'C' => 'were', 'D' => 'have'],
                'correct_answer' => 'A',
                'explanation' => "Bentuk kalimat negatif lampau adalah 'did not' diikuti Verb 1 dasar ('know').",
            ]
        ];
    }

    /**
     * Extensive pool of Simple Perfect Tense questions
     */
    protected function getSimplePerfectQuestionPool(): array
    {
        return [
            [
                'topic' => 'Simple Perfect Tense',
                'question' => 'Mr. David _____ in this international company since 2018.',
                'options' => ['A' => 'works', 'B' => 'has worked', 'C' => 'worked', 'D' => 'have worked'],
                'correct_answer' => 'B',
                'explanation' => "Keterangan 'since 2018' menunjukkan kegiatan yang dimulai di masa lampau dan masih berlangsung. Subjek tunggal 'Mr. David' menggunakan 'has worked'.",
            ],
            [
                'topic' => 'Simple Perfect Tense',
                'question' => 'I _____ already _____ the final project report to the teacher.',
                'options' => ['A' => 'have / submitted', 'B' => 'has / submitted', 'C' => 'did / submit', 'D' => 'am / submitting'],
                'correct_answer' => 'A',
                'explanation' => "Kata keterangan 'already' digunakan pada Present Perfect Tense. Subjek 'I' menggunakan 'have' + V3 'submitted'.",
            ],
            [
                'topic' => 'Simple Perfect Tense',
                'question' => 'Have you ever _____ to Japan during the cherry blossom season?',
                'options' => ['A' => 'be', 'B' => 'went', 'C' => 'been', 'D' => 'being'],
                'correct_answer' => 'C',
                'explanation' => "Pada kalimat tanya pengalaman 'Have you ever...?', bentuk kata kerja yang digunakan adalah past participle (V3) yaitu 'been'.",
            ],
            [
                'topic' => 'Simple Perfect Tense',
                'question' => 'She has not received the email confirmation _____.',
                'options' => ['A' => 'ago', 'B' => 'yesterday', 'C' => 'yet', 'D' => 'last night'],
                'correct_answer' => 'C',
                'explanation' => "Kata 'yet' digunakan di akhir kalimat negatif pada Present Perfect Tense untuk menyatakan bahwa sesuatu belum terjadi hingga saat ini.",
            ],
            [
                'topic' => 'Simple Perfect Tense',
                'question' => 'We _____ each other for more than ten years.',
                'options' => ['A' => 'have known', 'B' => 'has known', 'C' => 'knew', 'D' => 'know'],
                'correct_answer' => 'A',
                'explanation' => "Keterangan durasi 'for more than ten years' memerlukan Present Perfect Tense: 'We' + 'have' + V3 'known'.",
            ],
            [
                'topic' => 'Simple Perfect Tense',
                'question' => 'The scientists _____ a major breakthrough in cancer research recently.',
                'options' => ['A' => 'have made', 'B' => 'has made', 'C' => 'made', 'D' => 'making'],
                'correct_answer' => 'A',
                'explanation' => "Subjek jamak 'The scientists' + keterangan 'recently' menggunakan Present Perfect Tense: 'have made'.",
            ],
            [
                'topic' => 'Simple Perfect Tense',
                'question' => 'How many books _____ you _____ this semester?',
                'options' => ['A' => 'have / read', 'B' => 'did / read', 'C' => 'has / read', 'D' => 'do / read'],
                'correct_answer' => 'A',
                'explanation' => "Menanyakan pencapaian/kuantitas dalam kurun waktu yang masih berjalan ('this semester') menggunakan 'have' + subject 'you' + V3 'read'.",
            ],
            [
                'topic' => 'Simple Perfect Tense',
                'question' => 'Nadia _____ just _____ the clean laundry in her room.',
                'options' => ['A' => 'has / folded', 'B' => 'have / folded', 'C' => 'did / fold', 'D' => 'is / folding'],
                'correct_answer' => 'A',
                'explanation' => "Kata 'just' menandakan aksi yang baru saja selesai. Subjek tunggal 'Nadia' menggunakan 'has' + V3 'folded'.",
            ],
            [
                'topic' => 'Simple Perfect Tense',
                'question' => 'They _____ not visited their grandparents since last December.',
                'options' => ['A' => 'have', 'B' => 'has', 'C' => 'did', 'D' => 'were'],
                'correct_answer' => 'A',
                'explanation' => "Subjek jamak 'They' dengan keterangan 'since...' menggunakan auxiliary 'have' + not + V3 ('visited').",
            ],
            [
                'topic' => 'Simple Perfect Tense',
                'question' => 'The pilot _____ safely _____ the airplane despite bad weather conditions.',
                'options' => ['A' => 'has / landed', 'B' => 'have / landed', 'C' => 'did / land', 'D' => 'is / landing'],
                'correct_answer' => 'A',
                'explanation' => "Subjek tunggal 'The pilot' menggunakan 'has' + V3 'landed' untuk menunjukkan hasil aksi yang relevan dengan masa sekarang.",
            ],
            [
                'topic' => 'Simple Perfect Tense',
                'question' => 'I _____ never _____ such an inspiring speech before in my life.',
                'options' => ['A' => 'have / heard', 'B' => 'has / heard', 'C' => 'did / hear', 'D' => 'am / hearing'],
                'correct_answer' => 'A',
                'explanation' => "Pernyataan pengalaman seumur hidup ('never... in my life') menggunakan 'have' + never + V3 'heard'.",
            ],
            [
                'topic' => 'Simple Perfect Tense',
                'question' => '_____ the package arrived at your front door yet?',
                'options' => ['A' => 'Has', 'B' => 'Have', 'C' => 'Did', 'D' => 'Is'],
                'correct_answer' => 'A',
                'explanation' => "Subjek tunggal 'the package' dengan penanda 'yet' menggunakan auxiliary 'Has' + V3 'arrived'.",
            ],
            [
                'topic' => 'Simple Perfect Tense',
                'question' => 'Our team _____ five successful marketing campaigns so far this year.',
                'options' => ['A' => 'has completed', 'B' => 'have completed', 'C' => 'completed', 'D' => 'completing'],
                'correct_answer' => 'A',
                'explanation' => "'So far this year' menunjukkan capaian hingga saat ini. Collective noun 'Our team' (tunggal) menggunakan 'has completed'.",
            ],
            [
                'topic' => 'Simple Perfect Tense',
                'question' => 'He _____ already _____ all the necessary documents for the visa application.',
                'options' => ['A' => 'has / prepared', 'B' => 'have / prepared', 'C' => 'did / prepare', 'D' => 'is / preparing'],
                'correct_answer' => 'A',
                'explanation' => "Subjek tunggal 'He' + penanda 'already' menggunakan 'has' + V3 'prepared'.",
            ],
            [
                'topic' => 'Simple Perfect Tense',
                'question' => 'How long _____ they _____ married?',
                'options' => ['A' => 'have / been', 'B' => 'has / been', 'C' => 'were / being', 'D' => 'did / be'],
                'correct_answer' => 'A',
                'explanation' => "Menanyakan durasi keadaan yang masih berlangsung ('How long...') menggunakan 'have' + 'they' + V3 'been'.",
            ]
        ];
    }
}
