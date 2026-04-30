<?php

namespace MyAds\Plugins\AiAdCopywriter;

use Illuminate\Support\Facades\Http;
use App\Models\Option;

class AiAdCopywriterService
{
    protected $apiKey;
    protected $model = 'gemini-3-flash-preview';

    public function __construct()
    {
        $config = $this->getConfig();
        $this->apiKey = $config['api_key'] ?? null;
    }

    public function getConfig()
    {
        $option = Option::where('name', 'ai_ad_copywriter_config')->first();
        if ($option) {
            return json_decode($option->o_valuer, true);
        }
        return [
            'api_key' => '',
        ];
    }

    public function saveConfig($data)
    {
        Option::updateOrCreate(
            ['name' => 'ai_ad_copywriter_config'],
            [
                'o_valuer' => json_encode($data),
                'o_type' => 'ai_ad_copywriter'
            ]
        );
    }

    public function generateSuggestions($prompt, $type = 'title')
    {
        if (!$this->apiKey) {
            throw new \Exception('Gemini API Key is not configured.');
        }

        $systemPrompt = $type === 'title' 
            ? "You are an expert ad copywriter. Generate 5 short, catchy, and high-converting ad titles (max 60 characters each) for the following topic. Return only a JSON array of strings. Language: Arabic/English based on input."
            : "You are an expert ad copywriter. Generate 5 short, catchy, and high-converting ad descriptions (max 160 characters each) for the following topic. Return only a JSON array of strings. Language: Arabic/English based on input.";

        $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}", [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $systemPrompt . "\n\nTopic: " . $prompt]
                    ]
                ]
            ],
        ]);

        if ($response->failed()) {
            throw new \Exception('AI Generation failed: ' . ($response->json()['error']['message'] ?? 'Unknown error'));
        }

        $result = $response->json();
        $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '[]';
        
        // Clean markdown code blocks if present
        $text = preg_replace('/^```json\s*|\s*```$/i', '', trim($text));
        
        return json_decode($text, true) ?: [];
    }
}
