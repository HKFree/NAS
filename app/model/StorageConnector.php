<?php

namespace App\Model;

use Nette;

/**
 * Description of StorageConnector
 *
 * @author bkralik
 */
class StorageConnector extends Nette\Object {
    /** @var string */
    private $storageServerURL;
    
    private $folders;
    
    public function __construct($storageServerURL) {
        $this->storageServerURL = $storageServerURL;
    }
    
    public function getFolders() {
        $data = $this->httpGet('list');
        $folders = json_decode($data);
        $this->folders = $folders;
        \Tracy\Debugger::barDump($folders);
        return($folders);
    }
    
    public function getFolder($name) {
        if(empty($this->folders)) {
            $this->getFolders();
        }
        
        foreach($this->folders as $f) {
            if($f->name == $name) {
                return($f);
            } 
        }
        return(FALSE);
    }
    
    public function createFolder($name, $quota = NULL) {
        $params['name'] = $name;
        if(!empty($quota)) {
            $params['quota'] = intval($quota);
        }
        
        $data = $this->httpGet('create', $params);
        $ret = json_decode($data);
        return($ret->ok && TRUE);
    }
    
    public function fixPermissions($name) {
        $params['name'] = $name;
        
        $data = $this->httpGet('perms', $params);
        $ret = json_decode($data);
        return($ret->ok && TRUE);
    }
    
    public function deleteFolder($name) {
        $params['name'] = $name;
        
        $data = $this->httpGet('delete', $params);
        $ret = json_decode($data);
        return($ret->ok && TRUE);
    }
    
    public function setQuota($name, $quota = NULL) {
        $params['name'] = $name;
        if(empty($quota)) {
            $params['quota'] = 'none';
        } else {
            $params['quota'] = intval($quota);
        }
        
        $data = $this->httpGet('quota', $params);
        $ret = json_decode($data);
        return($ret->ok && TRUE);
    }
    
    private function httpGet($action, $parameters = array()) {
        \Tracy\Debugger::barDump('SC - '.$action.' '.implode(', ', $parameters));
        
        $url = $this->storageServerURL . '/' . $action;
        if(!empty($parameters)) {
            $url = $url . '?' . http_build_query($parameters);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $data = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return($data);
    }
}
