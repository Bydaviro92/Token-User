<?php

namespace Tests\Feature;

use App\Models\Token;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_the_first_ten_users(): void
    {
        User::factory()->count(11)->create();

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(10, 'data');
    }

    public function test_it_creates_a_user_with_a_hashed_password(): void
    {
        $this->postJson('/api/users', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'secret-password',
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'ada@example.com')
            ->assertJsonMissingPath('data.password');

        $this->assertDatabaseHas('users', ['email' => 'ada@example.com']);
        $this->assertTrue(Hash::check('secret-password', User::first()->password));
    }

    public function test_it_logs_in_and_uses_the_token_to_update_the_name(): void
    {
        $user = User::factory()->create([
            'email' => 'ada@example.com',
            'password' => 'secret-password',
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'ada@example.com',
            'password' => 'secret-password',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer');

        $plainTextToken = $loginResponse->json('data.token');
        $this->assertSame(hash('sha256', $plainTextToken), Token::first()->token);

        $this->patchJson('/api/user/name', ['name' => 'Ada Byron'], [
            'Authorization' => 'Bearer '.$plainTextToken,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Ada Byron');

        $this->assertSame('Ada Byron', $user->fresh()->name);
    }

    public function test_it_returns_the_unified_response_for_an_invalid_token(): void
    {
        $this->patchJson('/api/user/name', ['name' => 'Ada'], [
            'Authorization' => 'Bearer invalid-token',
        ])
            ->assertUnauthorized()
            ->assertJsonStructure(['success', 'message', 'data'])
            ->assertJsonPath('success', false);
    }
}
