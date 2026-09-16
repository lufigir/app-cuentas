<?php

namespace App\Services;

use App\Exceptions\AccountsServiceUnavailable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin HTTP client for the Flask accounts microservice.
 *
 * The gateway never reshapes the microservice payloads: whatever the service
 * answers (status code and body) is what the caller gets back.
 */
class AccountsServiceClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly int $timeout,
    ) {}

    public function list(): Response
    {
        return $this->send(fn (PendingRequest $request) => $request->get('/api/accounts'));
    }

    public function show(int $id): Response
    {
        return $this->send(fn (PendingRequest $request) => $request->get("/api/accounts/{$id}"));
    }

    public function create(array $payload): Response
    {
        return $this->send(fn (PendingRequest $request) => $request->post('/api/accounts', $payload));
    }

    public function update(int $id, array $payload): Response
    {
        return $this->send(fn (PendingRequest $request) => $request->put("/api/accounts/{$id}", $payload));
    }

    public function delete(int $id): Response
    {
        return $this->send(fn (PendingRequest $request) => $request->delete("/api/accounts/{$id}"));
    }

    private function send(callable $callback): Response
    {
        $request = Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->acceptJson()
            ->withHeaders(['X-API-Key' => $this->apiKey]);

        try {
            return $callback($request);
        } catch (ConnectionException $exception) {
            throw new AccountsServiceUnavailable(previous: $exception);
        }
    }
}
