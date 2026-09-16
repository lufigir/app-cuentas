<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Services\AccountsServiceClient;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\Response;

class AccountController extends Controller
{
    public function __construct(private readonly AccountsServiceClient $accounts) {}

    public function index(): Response
    {
        return $this->passThrough($this->accounts->list());
    }

    public function show(int $id): Response
    {
        return $this->passThrough($this->accounts->show($id));
    }

    public function store(StoreAccountRequest $request): Response
    {
        return $this->passThrough($this->accounts->create($request->validated()));
    }

    public function update(UpdateAccountRequest $request, int $id): Response
    {
        return $this->passThrough($this->accounts->update($id, $request->validated()));
    }

    public function destroy(int $id): Response
    {
        return $this->passThrough($this->accounts->delete($id));
    }

    /**
     * Return the microservice answer untouched: same status, same body.
     */
    private function passThrough(ClientResponse $response): Response
    {
        return response(
            $response->body(),
            $response->status(),
            ['Content-Type' => $response->header('Content-Type') ?: 'application/json'],
        );
    }
}
