<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Define a fake 'home' route if it doesn't exist to avoid RouteNotFound errors
        if (!Route::has('home')) {
            Route::get('/home', fn () => 'home')->name('home');
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test if the redirect to Google works.
     */
    public function test_google_redirect_works()
    {
        $response = $this->get(route('google.login'));

        $response->assertStatus(302);
        $this->assertStringContainsString('accounts.google.com', $response->getTargetUrl());
    }

    /**
     * Test if callback creates a new user.
     */
    public function test_google_callback_creates_new_user()
    {
        // Mock the User object returned by Socialite
        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
        $abstractUser->id = '123456789';
        $abstractUser->email = 'newuser@example.com';
        $abstractUser->name = 'John Doe';
        $abstractUser->avatar = 'https://google.com/avatar.png';

        // Mock Socialite driver
        Socialite::shouldReceive('driver->stateless->user')
            ->once()
            ->andReturn($abstractUser);

        $response = $this->get('/api/auth/google/callback');

        // Assert user exists in database
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'google_id' => '123456789',
        ]);

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');

        // Assert redirect to frontend url with token
        $response->assertStatus(302);
        $this->assertStringContainsString($frontendUrl . '/auth/callback?token=', $response->headers->get('Location'));
    }

    /**
     * Test if callback logs in an existing user.
     */
    public function test_google_callback_logs_in_existing_user()
    {
        // Create user beforehand
        $user = User::create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'google_id' => '987654321',
            'password' => bcrypt('password'),
        ]);

        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
        $abstractUser->id = '987654321';
        $abstractUser->email = 'existing@example.com';
        $abstractUser->name = 'Existing User';
        $abstractUser->avatar = 'https://google.com/avatar.png';

        Socialite::shouldReceive('driver->stateless->user')
            ->once()
            ->andReturn($abstractUser);

        $response = $this->get('/api/auth/google/callback');

        // Assert redirect works
        $response->assertStatus(302);
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        $this->assertStringContainsString($frontendUrl . '/auth/callback?token=', $response->headers->get('Location'));
    }
}
