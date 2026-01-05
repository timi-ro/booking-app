<?php

namespace Tests\Feature\Auth;

use App\Constants\UserRoles;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Traits\ResponseHelpers;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase, ResponseHelpers;

    // ===== LOGIN Tests =====

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $this->assertStandardResponse($response);
        $responseData = $response->json('data');
        $this->assertArrayHasKey('token', $responseData);
        $this->assertIsString($responseData['token']);
        $this->assertNotEmpty($responseData['token']);
    }

    public function test_customer_receives_token_on_login(): void
    {
        User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('password123'),
            'role' => UserRoles::CUSTOMER,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'customer@example.com',
            'password' => 'password123',
        ]);

        $this->assertStandardResponse($response);
        $token = $response->json('data.token');
        $this->assertStringContainsString('|', $token);
    }

    public function test_agency_receives_token_on_login(): void
    {
        User::factory()->create([
            'email' => 'agency@example.com',
            'password' => Hash::make('password123'),
            'role' => UserRoles::AGENCY,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'agency@example.com',
            'password' => 'password123',
        ]);

        $this->assertStandardResponse($response);
        $token = $response->json('data.token');
        $this->assertStringContainsString('|', $token);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    // ===== LOGIN VALIDATION Tests =====

    #[DataProvider('invalidLoginDataProvider')]
    public function test_login_validation(array $invalidData, array $expectedErrors): void
    {
        $response = $this->postJson('/api/auth/login', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors($expectedErrors);
    }

    public static function invalidLoginDataProvider(): array
    {
        return [
            'missing email' => [
                ['password' => 'password123'],
                ['email']
            ],
            'invalid email format' => [
                ['email' => 'not-an-email', 'password' => 'password123'],
                ['email']
            ],
            'missing password' => [
                ['email' => 'user@example.com'],
                ['password']
            ],
        ];
    }

    // ===== TOKEN Tests =====

    public function test_generated_token_can_be_used_for_authentication(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'role' => UserRoles::CUSTOMER,
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('data.token');

        // Use the token to access a protected route
        $response = $this->withToken($token)->getJson('/api/customer/offerings');

        $response->assertStatus(200);
    }

    public function test_token_includes_user_role_in_token_name(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('password123'),
            'role' => UserRoles::CUSTOMER,
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'customer@example.com',
            'password' => 'password123',
        ]);

        // Check that token was created with role in name
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $customer->id,
            'name' => 'api_' . UserRoles::CUSTOMER,
        ]);
    }
}
