<?php
namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesClinic;
use Tests\TestCase;

class TwoFactorFlowTest extends TestCase
{
    use MakesClinic, RefreshDatabase;

    private function enrolled(): array
    {
        $b = Branch::create(['name_ar' => 'ف', 'name_en' => 'B']);
        $u = $this->makeStaff('reception', $b);
        $tf = app(TwoFactorService::class);
        $secret = $tf->begin($u);
        $codes = $tf->confirm($u->fresh(), $tf->totp()->codeAt($secret, intdiv(time(), 30)));

        return [$u->fresh(), $secret, $codes, $tf];
    }

    private function login(): \Illuminate\Testing\TestResponse
    {
        return $this->post('/login', ['email' => 'reception@x.test', 'password' => 'Secret@12345']);
    }

    public function test_password_alone_does_not_log_in_when_2fa_enabled(): void
    {
        $this->enrolled();
        $this->login()->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
        $this->get('/')->assertRedirect('/login');
    }

    public function test_valid_totp_completes_login_and_cannot_be_replayed(): void
    {
        [, $secret, , $tf] = $this->enrolled();
        $code = $tf->totp()->codeAt($secret, intdiv(time(), 30) + 1);       // confirmation consumed the current step

        $this->login();
        $this->post('/two-factor-challenge', ['code' => $code])->assertRedirect('/');
        $this->assertAuthenticated();

        auth()->logout();
        $this->login();
        $this->post('/two-factor-challenge', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_wrong_code_is_rejected(): void
    {
        $this->enrolled();
        $this->login();
        $this->post('/two-factor-challenge', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_recovery_code_works_once(): void
    {
        [, , $codes] = $this->enrolled();

        $this->login();
        $this->post('/two-factor-challenge', ['recovery_code' => $codes[0]])->assertRedirect('/');
        $this->assertAuthenticated();

        auth()->logout();
        $this->login();
        $this->post('/two-factor-challenge', ['recovery_code' => $codes[0]])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_secret_and_recovery_hashes_are_encrypted_at_rest(): void
    {
        [$u, $secret] = $this->enrolled();
        $raw = \DB::table('users')->where('id', $u->id)->first();

        $this->assertStringNotContainsString($secret, $raw->two_factor_secret);
        $this->assertStringNotContainsString('[', (string) $raw->two_factor_recovery_codes);   // not plain JSON
    }

    public function test_required_role_is_forced_to_enrol(): void
    {
        config(['clinic.two_factor.required_roles' => ['reception']]);
        $b = Branch::create(['name_ar' => 'ف', 'name_en' => 'B']);
        $u = $this->makeStaff('reception', $b);

        $this->actingAs($u)->get('/')->assertRedirect(route('security'));
        $this->actingAs($u)->get(route('security'))->assertOk();
    }
}

/*
SYSTEM DESIGN:
End-to-end 2FA tests: no session before the second factor, replay rejection, single-use recovery codes, encryption at rest, forced enrolment.

DATA STRUCTURE:

*/
