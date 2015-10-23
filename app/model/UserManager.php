<?php

namespace App\Model;

use Nette;
use Nette\Security\AuthenticationException;


/**
 * Users management.
 */
class UserManager extends Nette\Object implements Nette\Security\IAuthenticator
{
    /** @var string */
    private $ldapServerURL;
    
	public function __construct($ldapServerURL)
	{
        $this->ldapServerURL = $ldapServerURL;
	}

	/**
	 * Performs an authentication.
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($uid, $password) = $credentials;

		$identity = $this->ldapGetIdentity($uid);
        $this->ldapAuthenticate($uid, $password);

		return($identity);
	}
    
    private function ldapAuthenticate($uid, $password) {
        $shuidstring = escapeshellarg("uid=" . $uid . ",ou=People,dc=hkfree,dc=org");
        $shpass = escapeshellarg($password);
        $shcmd = "ldapwhoami -x -D " . $shuidstring . " -w " . $shpass . " -H " . $this->ldapServerURL;
        
        $dspecs = array(
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w')
        );
        
        $proc = proc_open($shcmd, $dspecs, $pipes);
        $stdout = trim(stream_get_contents($pipes[1]));
        fclose($pipes[1]);
        $stderr = trim(stream_get_contents($pipes[2]));
        fclose($pipes[2]);
        proc_close($proc);        

        if(preg_match("/^dn:uid=$uid/", $stdout)) {
            return(true);
        } elseif (preg_match("/Invalid credentials/", $stderr)) {
            throw new AuthenticationException('Neplatné heslo.', self::INVALID_CREDENTIAL);
        } else {
            throw new AuthenticationException('Chyba přihlášení, kontaktujte správce.', self::FAILURE);
        }
    }
    
    private function ldapGetIdentity($uid) {
        $ident = array();
        $ident['id'] = $uid;

        $dn = "ou=People,dc=hkfree,dc=org" ;
        $filter = "uid=".$uid;
        
        $ldapCon = ldap_connect($this->ldapServerURL);
        if(!$ldapCon) {
            throw new AuthenticationException('Chyba přihlášení, kontaktujte správce.', self::FAILURE);
        }
        
        $rec1 = ldap_search($ldapCon, $dn, $filter, array("memberOf"));
        if(!$rec1) {
            throw new AuthenticationException('Chyba přihlášení, kontaktujte správce.', self::FAILURE);
        }
        $info1 = ldap_get_entries($ldapCon, $rec1);
        
        $rec2 = ldap_search($ldapCon, $dn, $filter);
        if(!$rec2) {
            throw new AuthenticationException('Chyba přihlášení, kontaktujte správce.', self::FAILURE);
        }
        $info2 = ldap_get_entries($ldapCon, $rec2);
        
        if($info2['count'] == 0) {
            throw new AuthenticationException('HKFree UID je neplatné.', self::IDENTITY_NOT_FOUND);
        }
        
        $ident['username'] = $info2[0]['displayname'][0];
        
        foreach($info1[0]['memberof'] as $roleString) {
            if(preg_match('/cn=(.*),ou=roles,dc=hkfree,dc=org/', $roleString, $matches)) {
                $ident['roles'][] = $matches[1];
            }
        }
        return new Nette\Security\Identity($uid, $ident['roles'], $ident);
    } 
}


class DuplicateNameException extends \Exception
{}
