<?php

namespace App\Http\Controllers;

use GeniusPay\Laravel\Events\PaymentCompleted;
use GeniusPay\Laravel\Events\PaymentFailed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Le WebhookController fourni par geniuspay/laravel-geniuspay vérifie un
 * contrat obsolète (header X-GeniusPay-Signature, HMAC sans timestamp,
 * événement "payment.completed") qui ne correspond plus à l'API réellement
 * documentée sur https://pay.genius.ci/docs/api (header X-Webhook-Signature,
 * HMAC(timestamp + "." + payload, secret), événement "payment.success").
 * Ce contrôleur suit le contrat documenté et remplace celui du SDK — le
 * dashboard marchand GeniusPay doit pointer vers cette URL.
 */
class GeniusPayWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();
        $timestamp = $request->header('X-Webhook-Timestamp');

        if (! $this->hasValidSignature($payload, $request, $timestamp)) {
            Log::warning('GeniusPay: signature de webhook invalide');

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        if (! $this->hasFreshTimestamp($timestamp)) {
            Log::warning('GeniusPay: timestamp de webhook trop ancien (rejeu possible)');

            return response()->json(['error' => 'Timestamp too old'], 400);
        }

        $event = $payload['event'] ?? '';
        $data = $payload['data'] ?? [];

        Log::info('GeniusPay: webhook reçu', ['event' => $event]);

        match ($event) {
            'payment.success' => PaymentCompleted::dispatch($data),
            'payment.failed', 'payment.cancelled', 'payment.expired' => PaymentFailed::dispatch($data),
            default => Log::info('GeniusPay: événement de webhook non traité', ['event' => $event]),
        };

        return response()->json(['success' => true]);
    }

    /**
     * Format documenté : signature = HMAC-SHA256(timestamp + "." + payload_json, secret).
     *
     * @param  array<string, mixed>  $payload
     */
    private function hasValidSignature(array $payload, Request $request, ?string $timestamp): bool
    {
        $secret = config('geniuspay.webhook_secret');

        if (empty($secret)) {
            return true;
        }

        $signature = $request->header('X-Webhook-Signature');

        if (empty($signature) || empty($timestamp)) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.json_encode($payload), $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Protection anti-rejeu : au-delà de 5 minutes, un webhook capturé ne
     * doit plus pouvoir être rejoué.
     */
    private function hasFreshTimestamp(?string $timestamp): bool
    {
        if (empty($timestamp)) {
            return true;
        }

        return abs(time() - (int) $timestamp) <= 300;
    }
}
