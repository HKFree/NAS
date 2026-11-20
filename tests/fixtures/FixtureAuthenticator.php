<?php

declare(strict_types=1);

namespace NasTests;

use Nette\Security\AuthenticationException;
use Nette\Security\Authenticator;
use Nette\Security\IIdentity;
use Nette\Security\SimpleIdentity;

final class FixtureAuthenticator implements Authenticator
{
    public function authenticate(string $user, string $password): IIdentity
    {
        if ($user !== '1234' || $password !== 'fixture-password') {
            throw new AuthenticationException('Neplatné heslo.', self::InvalidCredential);
        }

        return new SimpleIdentity($user, [], ['username' => 'Fixture User']);
    }
}
