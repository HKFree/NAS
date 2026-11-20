<?php

namespace App\Model;

use Nette\Security\AuthenticationException;
use Nette\Security\Authenticator;
use Nette\Security\IIdentity;
use Nette\Security\SimpleIdentity;

class UserManager implements Authenticator
{
    private const PEOPLE_DN = 'ou=People,dc=hkfree,dc=org';
    private const LDAP_INVALID_CREDENTIALS = 49;

    public function __construct(private string $ldapServerURL)
    {
    }

    public function authenticate(string $uid, string $password): IIdentity
    {
        if ($password === '') {
            throw new AuthenticationException('Neplatné heslo.', self::InvalidCredential);
        }

        $connection = ldap_connect($this->ldapServerURL);
        if (!$connection || !ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3)) {
            throw new AuthenticationException('Chyba přihlášení, kontaktujte správce.', self::Failure);
        }

        $filter = '(uid=' . ldap_escape($uid, '', LDAP_ESCAPE_FILTER) . ')';
        $result = @ldap_search($connection, self::PEOPLE_DN, $filter, ['displayName', 'memberOf']);
        if (!$result || ($entries = ldap_get_entries($connection, $result)) === false) {
            throw new AuthenticationException('Chyba přihlášení, kontaktujte správce.', self::Failure);
        }

        if ($entries['count'] !== 1) {
            throw new AuthenticationException('HKFree UID je neplatné.', self::IdentityNotFound);
        }

        $entry = $entries[0];
        if (!@ldap_bind($connection, $entry['dn'], $password)) {
            if (ldap_errno($connection) === self::LDAP_INVALID_CREDENTIALS) {
                throw new AuthenticationException('Neplatné heslo.', self::InvalidCredential);
            }
            throw new AuthenticationException('Chyba přihlášení, kontaktujte správce.', self::Failure);
        }

        $roles = [];
        foreach ($entry['memberof'] ?? [] as $key => $roleDn) {
            if (is_int($key) && preg_match('/^cn=([^,]+),ou=roles,dc=hkfree,dc=org$/i', $roleDn, $matches)) {
                $roles[] = $matches[1];
            }
        }

        return new SimpleIdentity($uid, $roles, [
            'id' => $uid,
            'username' => $entry['displayname'][0] ?? $uid,
            'roles' => $roles,
        ]);
    }
}

class DuplicateNameException extends \Exception
{
}
