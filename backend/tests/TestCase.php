<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Auth\AuthManager;

abstract class TestCase extends BaseTestCase
{
    protected function asUser(User $user): static
    {
        //
        $this->app->make(AuthManager::class)->forgetGuards();

        return $this->withHeader('Authorization', 'Bearer '.$user->createToken('feature')->plainTextToken);
    }

    protected function asToken(string $token): static
    {
        $this->app->make(AuthManager::class)->forgetGuards();

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }
}

