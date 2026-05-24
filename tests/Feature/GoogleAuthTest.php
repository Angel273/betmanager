<?php

namespace Tests\Feature;

use App\Models\AllowedEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that a whitelisted email can successfully authenticate.
     */
    public function test_whitelisted_email_can_authenticate()
    {
        $email = 'mauri@example.com';

        // Add email to allowed list
        AllowedEmail::create([
            'email' => $email,
            'created_by' => 'Admin'
        ]);

        // Mock Google Socialite callback
        $googleUser = Mockery::mock('Laravel\Socialite\Two\User');
        $googleUser->shouldReceive('getId')->andReturn('123456');
        $googleUser->shouldReceive('getEmail')->andReturn($email);
        $googleUser->shouldReceive('getName')->andReturn('Mauri Bet');
        $googleUser->shouldReceive('getAvatar')->andReturn('https://google.com/avatar.jpg');

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        // Access callback URL
        $response = $this->get(route('auth.google.callback'));

        // Assert redirect to dashboard and user logged in
        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', [
            'email' => $email,
            'google_id' => '123456',
        ]);
        $this->assertAuthenticated();
    }

    /**
     * Test that a non-whitelisted email is blocked from authentication.
     */
    public function test_non_whitelisted_email_is_blocked()
    {
        $email = 'stranger@example.com';

        // Mock Google Socialite callback
        $googleUser = Mockery::mock('Laravel\Socialite\Two\User');
        $googleUser->shouldReceive('getId')->andReturn('654321');
        $googleUser->shouldReceive('getEmail')->andReturn($email);
        $googleUser->shouldReceive('getName')->andReturn('Stranger');
        $googleUser->shouldReceive('getAvatar')->andReturn('https://google.com/avatar.jpg');

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        // Access callback URL
        $response = $this->get(route('auth.google.callback'));

        // Assert redirect back to login with error and user not logged in
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('users', [
            'email' => $email,
        ]);
        $this->assertGuest();
    }
}
