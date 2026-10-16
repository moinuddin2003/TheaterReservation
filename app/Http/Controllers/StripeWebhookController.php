<?php

namespace App\Http\Controllers;

use App\Services\StripeEventProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeEventProcessor $processor): JsonResponse
    {
        $secret = (string) config('services.stripe.webhook_secret');
        $signature = (string) $request->header('Stripe-Signature');
        if ($secret === '' || ! $this->validSignature($request->getContent(), $signature, $secret)) {
            return response()->json(['message' => 'Invalid Stripe signature.'], Response::HTTP_BAD_REQUEST);
        }
        $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $processor->process($payload);

        return response()->json(['received' => true]);
    }

    private function validSignature(string $payload, string $header, string $secret): bool
    {
        $parts = [];
        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            $parts[$key][] = $value;
        }
        $timestamp = (int) ($parts['t'][0] ?? 0);
        if ($timestamp < 1 || abs(time() - $timestamp) > 300) {
            return false;
        }
        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        foreach ($parts['v1'] ?? [] as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }
}
