<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    private const SERVICE = 'http://127.0.0.1:5000';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.accounts.url' => self::SERVICE,
            'services.accounts.key' => 'test-api-key',
        ]);
    }

    private function actingAsUser(): static
    {
        return $this->actingAs(User::factory()->create(), 'sanctum');
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'account_number' => 'ACC-0001',
            'balance' => '150.50',
        ], $overrides);
    }

    public function test_the_accounts_endpoints_require_authentication(): void
    {
        Http::fake();

        $this->getJson('/api/accounts')->assertUnauthorized();
        $this->postJson('/api/accounts', $this->validPayload())->assertUnauthorized();

        Http::assertNothingSent();
    }

    public function test_it_lists_the_accounts_from_the_microservice(): void
    {
        Http::fake([
            self::SERVICE.'/api/accounts' => Http::response([$this->validPayload(['id' => 1])], 200),
        ]);

        $this->actingAsUser()->getJson('/api/accounts')
            ->assertOk()
            ->assertJsonPath('0.account_number', 'ACC-0001');

        Http::assertSent(fn (Request $request) => $request->method() === 'GET'
            && $request->url() === self::SERVICE.'/api/accounts'
            && $request->header('X-API-Key') === ['test-api-key']);
    }

    public function test_it_forwards_a_create_request_and_returns_201(): void
    {
        Http::fake([
            self::SERVICE.'/api/accounts' => Http::response($this->validPayload(['id' => 7]), 201),
        ]);

        $this->actingAsUser()->postJson('/api/accounts', $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('id', 7);

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request['account_number'] === 'ACC-0001');
    }

    public function test_it_validates_before_calling_the_microservice(): void
    {
        Http::fake();

        $this->actingAsUser()
            ->postJson('/api/accounts', ['first_name' => 'Ada', 'email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['last_name', 'email', 'account_number']);

        Http::assertNothingSent();
    }

    public function test_it_rejects_a_negative_balance(): void
    {
        Http::fake();

        $this->actingAsUser()
            ->postJson('/api/accounts', $this->validPayload(['balance' => '-10']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('balance');

        Http::assertNothingSent();
    }

    public function test_it_forwards_the_microservice_error_untouched(): void
    {
        Http::fake([
            self::SERVICE.'/api/accounts' => Http::response(
                ['error' => 'An account with that account_number already exists'],
                409,
            ),
        ]);

        $this->actingAsUser()->postJson('/api/accounts', $this->validPayload())
            ->assertStatus(409)
            ->assertJsonPath('error', 'An account with that account_number already exists');
    }

    public function test_it_forwards_a_show_request(): void
    {
        Http::fake([
            self::SERVICE.'/api/accounts/7' => Http::response($this->validPayload(['id' => 7]), 200),
        ]);

        $this->actingAsUser()->getJson('/api/accounts/7')
            ->assertOk()
            ->assertJsonPath('id', 7);
    }

    public function test_it_forwards_a_missing_account_as_404(): void
    {
        Http::fake([
            self::SERVICE.'/api/accounts/999' => Http::response(['error' => 'Account not found'], 404),
        ]);

        $this->actingAsUser()->getJson('/api/accounts/999')
            ->assertNotFound()
            ->assertJsonPath('error', 'Account not found');
    }

    public function test_it_forwards_an_update_request(): void
    {
        Http::fake([
            self::SERVICE.'/api/accounts/7' => Http::response(
                $this->validPayload(['id' => 7, 'first_name' => 'Grace']),
                200,
            ),
        ]);

        $this->actingAsUser()->putJson('/api/accounts/7', ['first_name' => 'Grace'])
            ->assertOk()
            ->assertJsonPath('first_name', 'Grace');

        Http::assertSent(fn (Request $request) => $request->method() === 'PUT'
            && $request->data() === ['first_name' => 'Grace']);
    }

    public function test_an_empty_update_never_reaches_the_microservice(): void
    {
        Http::fake();

        $this->actingAsUser()->putJson('/api/accounts/7', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('account');

        Http::assertNothingSent();
    }

    public function test_it_forwards_a_delete_request_as_204(): void
    {
        Http::fake([
            self::SERVICE.'/api/accounts/7' => Http::response('', 204),
        ]);

        $this->actingAsUser()->deleteJson('/api/accounts/7')->assertNoContent();

        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE');
    }

    public function test_it_answers_503_when_the_microservice_is_down(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection refused'));

        $this->actingAsUser()->getJson('/api/accounts')
            ->assertStatus(503)
            ->assertJsonPath('error', 'The accounts service is unavailable.');
    }
}
