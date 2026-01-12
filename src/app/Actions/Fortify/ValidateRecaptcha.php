<?php

namespace App\Actions\Fortify;

use App\Services\RecaptchaService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class ValidateRecaptcha
{
    /**
     * Handle the incoming request and validate reCAPTCHA.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  callable  $next
     * @return mixed
     */
    public function handle(Request $request, callable $next)
    {
        $recaptcha = new RecaptchaService();
        $result = $recaptcha->verify($request->input('recaptcha_token'), 'login');

        if (!$result['success']) {
            throw ValidationException::withMessages([
                Fortify::username() => [$result['error'] ?? __('reCAPTCHA verification failed. Please try again.')],
            ]);
        }

        return $next($request);
    }
}
