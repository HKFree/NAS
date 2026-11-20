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

        $dn = 'uid=' . ldap_escape($uid, '', LDAP_ESCAPE_DN) . ',' . self::PEOPLE_DN;
        if (!@ldap_bind($connection, $dn, $password)) {
            if (ldap_errno($connection) === self::LDAP_INVALID_CREDENTIALS) {
                throw new AuthenticationException('Neplatné heslo nebo HKFree UID.', self::InvalidCredential);
            }
            throw new AuthenticationException('Chyba přihlášení, kontaktujte správce.', self::Failure);
        }

        $result = @ldap_read($connection, $dn, '(objectClass=*)', ['displayName', 'memberOf']);
        if (!$result || ($entries = ldap_get_entries($connection, $result)) === false || $entries['count'] !== 1) {
            throw new AuthenticationException('Chyba přihlášení, kontaktujte správce.', self::Failure);
        }
        $entry = $entries[0];

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
