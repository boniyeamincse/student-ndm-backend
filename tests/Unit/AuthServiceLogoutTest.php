<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class AuthServiceLogoutTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_logout_deletes_current_access_token(): void
    {
        $token = Mockery::mock();
        $token->shouldReceive('delete')->once();

        $user = Mockery::mock(User::class);
        $user->shouldReceive('currentAccessToken')->once()->andReturn($token);

        (new AuthService)->logout($user);

        $this->assertTrue(true);
    }

    public function test_logout_throws_validation_exception_without_current_access_token(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('currentAccessToken')->once()->andReturn(null);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('No current API token is associated with this request.');

        (new AuthService)->logout($user);
    }
}
