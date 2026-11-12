<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ShopAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_open_the_authentication_forms(): void
    {
        $this->get(route('shop'))->assertSee('Войти')->assertSee('Регистрация');
        $this->get(route('login'))->assertSee('Вход');
        $this->get(route('register'))->assertSee('Зарегистрироваться');
    }

    public function test_registration_creates_an_account_and_keeps_the_guest_cart(): void
    {
        $response = $this->withSession(['cart.items' => [42 => 2]])
            ->post(route('register.store'), [
                'name' => 'Покупатель',
                'email' => 'Customer@example.com',
                'password' => 'test-password',
                'password_confirmation' => 'test-password',
            ]);

        $response->assertRedirectToRoute('shop')->assertSessionHas('cart.items', [42 => 2]);

        $user = User::where('email', 'customer@example.com')->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('test-password', $user->password));
    }

    public function test_registration_rejects_a_duplicate_email_and_mismatched_passwords(): void
    {
        User::factory()->create(['email' => 'customer@example.com']);

        $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Покупатель',
            'email' => 'Customer@example.com',
            'password' => 'test-password',
            'password_confirmation' => 'different-password',
        ])->assertRedirectToRoute('register')->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_existing_users_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create(['name' => 'Покупатель']);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirectToRoute('shop');

        $this->assertAuthenticatedAs($user);
        $this->get(route('shop'))->assertSee('Покупатель')->assertSee('Выйти');
        $this->get(route('register'))->assertRedirectToRoute('shop');

        $this->post(route('logout'))->assertRedirectToRoute('shop');
        $this->assertGuest();
    }

    public function test_login_rejects_incorrect_credentials_and_limits_repeated_attempts(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->from(route('login'))->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'incorrect-password',
            ])->assertRedirectToRoute('login')->assertSessionHasErrors('email');
        }

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertTooManyRequests();

        $this->assertGuest();
    }

    public function test_a_guest_cannot_log_out(): void
    {
        $this->post(route('logout'))->assertRedirectToRoute('login');
    }
}
