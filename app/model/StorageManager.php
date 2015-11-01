<?php

namespace App\Model;

use Nette,
    App\Model,
    App\Model\ByteHelper;

/**
 * Description of StorageManager
 *
 * Složky mají lomítko na začátku, NE na konci (validní je / /3301 /3301/neco)
 * 
 * @author bkralik
 */
class StorageManager extends Nette\Object {
    
    /** @var Model\StorageConnector */
    private $sc;
    
    /** @var Model\Folder */
    private $folder;
            
    /** @var Nette\Security\User */   
    private $user;
    
    public function __construct(Model\StorageConnector $sc, Model\Folder $folder, Nette\Security\User $user) {
        $this->sc = $sc;
        $this->folder = $folder;
        $this->user = $user;        
    }
    
    public function getDefaultQuota(Nette\Security\User $user) {
        $quota = '100G';
        if($user->isInRole('SO') || $user->isInRole('ZSO') || $user->isInRole('VV')) {
            $quota = '3T';
        }
        $quotaNumeric = ByteHelper::humanToBytes($quota);
        return($quotaNumeric);
    }
    
    public function checkAndCreateRoot() {
        $uid = $this->user->id;
        $rootFolderName = '/'.$uid;
        
        $rootFolder = $this->folder->findOneBy(array(
            'user_id' => $uid,
            'name' => $rootFolderName
        ));
        
        if(!$rootFolder) {
            $defaultQuota = $this->getDefaultQuota($this->user);
            \Tracy\Debugger::barDump('Creating default folder '.$rootFolderName.' with quota '.$defaultQuota);
            $this->createFolder($rootFolderName, $defaultQuota, 'Základní uživatelská složka.');
        }
    }
    
    public function createUserFolder($name, $quota = NULL, $comment = '') {
        $uid = $this->user->id;
        return($this->createFolder('/'.$uid.'/'.$name, $quota, $comment));
    }
    
    public function getFolders() {
        
    }
    
    public function getFolder($name, $addPrefix = false) {
        if($addPrefix) {
            $name = '/'.$this->user->id.'/'.$name;
        }
        
        $dbFolder = $this->folder->findOneBy(array(
            'name' => $name
        ));
        $scFolder = $this->sc->getFolder($name);
        
        if($dbFolder && $scFolder) {
            $r['db'] = $dbFolder;
            $r['sc'] = $scFolder;
            return($r);
        } else {
            return(false);
        }
    }
    
    private function createFolder($name, $quota = NULL, $comment = '') {
        $state = $this->sc->createFolder($name, $quota);        
        if(!$state) {
            return(false);
        }
        
        $id = $this->folder->insert(array(
            'name' => $name,
            'user_id' => $this->user->id,
            'dateCreated' => time(),
            'comment' => $comment
        ));
        
        return($id);
    }
}
