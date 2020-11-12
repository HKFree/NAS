<?php

namespace App\Model;

use Nette,
    App\Model,
    App\Model\ByteHelper;
use Nette\SmartObject;

/**
 * Description of StorageManager
 *
 * Složky mají lomítko na začátku, NE na konci (validní je / /3301 /3301/neco)
 * 
 * @author bkralik
 */
class StorageManager {
    
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
    
    public function checkPermissions($folder_id) {
        $f = $this->folder->find($folder_id);
        return($f->user_id == $this->user->id);
    }
    
    public function getDefaultQuota(Nette\Security\User $user) {
        $quota = '200G';  #Christmas gift 2019
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
    
    public function createUserFolder($name, $quota = NULL, $comment = '', $dedicatedShare = 0) {
        $uid = $this->user->id;
        return($this->createFolder('/'.$uid.'/'.$name, $quota, $comment, $dedicatedShare));
    }
    
    public function deleteUserFolder($id) {
        $dbFolder = $this->folder->find($id);
        
        
        $state = $this->sc->deleteFolder($dbFolder->name);        
        if(!$state) {
            return(FALSE);
        }
        
        $dbFolder->delete();
        return(TRUE);
    }
    
    public function changeQuota($id, $quota) {
        $dbFolder = $this->folder->find($id);
        
        $state = $this->sc->setQuota($dbFolder->name, $quota);        
        return($state);
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
    
    public function getUserStats() {
        $avg = 0;
        $max = 0;
        $count = 0;
        $count1g = 0;
        
        foreach($this->sc->getFolders() as $f) {
            if(ByteHelper::getDegree($f->name) != 1) {
                continue;
            }
            
            if($f->name == "/") {
                continue;
            }
            
            $avg += $f->space_used;
            $count++;
            
            if($max < $f->space_used) {
                $max = $f->space_used;
            }
            
            if($f->space_used >= 1e9) {
                $count1g++;
            }
        }
        $avg /= $count;
        
        $minHistVal = 1;
        $maxHistVal = ceil(log($max, 1024));
        
        $hist = array();
        for($i = $minHistVal; $i < $maxHistVal; $i++) {
            $hist[$i] = 0;            
        }
        
        foreach($this->sc->getFolders() as $f) {
            if(ByteHelper::getDegree($f->name) != 1) {
                continue;
            }
            
            if($f->name == "/") {
                continue;
            }
            
            $i = floor(log($f->space_used, 1024)); 
            if(($i >= $minHistVal) && ($i < $maxHistVal)) {
                $hist[$i]++;
            }
        }
        
        return(array(
            "count" => $count,
            "average" => $avg,
            "maximum" => $max,
            "histogram" => $hist,
            //"histogramStep" => $max / $histCount,
            "1gcount" => $count1g
        ));
    }
    
    private function createFolder($name, $quota = NULL, $comment = '', $dedicatedShare = 0) {
        $state = $this->sc->createFolder($name, $quota);        
        if(!$state) {
            return(false);
        }
        
        $this->sc->fixPermissions($name);
        
        $id = $this->folder->insert(array(
            'name' => $name,
            'user_id' => $this->user->id,
            'dateCreated' => time(),
            'comment' => $comment,
            'dedicatedShare' => $dedicatedShare
        ));
        
        return($id);
    }
}
