<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecaptchaService
{
    protected string $secretKey;
    protected float $minimumScore;
    protected bool $enabled;

    public function __construct()
    {
        $this->secretKey = config('recaptcha.secret_key', '');
        $this->minimumScore = config('recaptcha.minimum_score', 0.5);
        $this->enabled = config('recaptcha.enabled', true);
    }

    /**
     * Verify a reCAPTCHA v3 token.
     *
     * @param string|null $token The reCAPTCHA response token from the client
     * @param string $expectedAction The expected action name (e.g., 'login', 'register')
     * @return array{success: bool, score: float|null, error: string|null}
     */
    public function verify(?string $token, string $expectedAction = 'submit'): array
    {
        // Skip validation if disabled
        if (!$this->enabled) {
            return ['success' => true, 'score' => null, 'error' => null];
        }

        // Missing token
        if (empty($token)) {
            return [
                'success' => false,
                'score' => null,
                'error' => 'reCAPTCHA verification failed. Please try again.',
            ];
        }

        // Missing secret key
        if (empty($this->secretKey)) {
            Log::warning('reCAPTCHA secret key is not configured');
            return ['success' => true, 'score' => null, 'error' => null];
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $this->secretKey,
                'response' => $token,
                'remoteip' => request()->ip(),
            ]);

            $result = $response->json();

            if (!$result['success']) {
                Log::warning('reCAPTCHA verification failed', [
                    'error-codes' => $result['error-codes'] ?? [],
                ]);
                return [
                    'success' => false,
                    'score' => null,
                    'error' => 'reCAPTCHA verification failed. Please try again.',
                ];
            }

            $score = $result['score'] ?? 0;
            $action = $result['action'] ?? '';

            // Verify action matches (optional but recommended)
            if ($expectedAction && $action !== $expectedAction) {
                Log::warning('reCAPTCHA action mismatch', [
                    'expected' => $expectedAction,
                    'received' => $action,
                ]);
            }

            // Check score threshold
            if ($score < $this->minimumScore) {
                Log::info('reCAPTCHA score below threshold', [
                    'score' => $score,
                    'threshold' => $this->minimumScore,
                ]);
                return [
                    'success' => false,
                    'score' => $score,
                    'error' => 'Verification failed. Please try again or contact support.',
                ];
            }

            return [
                'success' => true,
                'score' => $score,
                'error' => null,
            ];

        } catch (\Exception $e) {
            Log::error('reCAPTCHA verification error', [
                'message' => $e->getMessage(),
            ]);
            // Fail open on network errors to avoid blocking legitimate users
            return ['success' => true, 'score' => null, 'error' => null];
        }
    }
}
