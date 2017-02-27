<?php

namespace App\Presenters;

use Nette,
    App\Model,
    Nette\Application\Responses\TextResponse,
    Nette\Application\Responses\JsonResponse;


class ApiPresenter extends BasePresenter
{  
    /** @var Model\Folder @inject */
    public $folder;
    
    /** @var Model\Share @inject */
    public $share;
    
    /** @var Nette\Http\Request @inject */
    public $httpRequest;
    
    /** @var Nette\Http\Response @inject */
    public $httpResponse;
   
    public function renderAuthFtp() {
        if($this->httpRequest->getMethod() != "POST") {
            $this->error('Neplatná metoda.', 403);
        }
        
        $this->httpResponse->setContentType('text/plain', 'UTF-8');
        
        $username = $this->httpRequest->getPost('username', '');
        $password = $this->httpRequest->getPost('password', '');
        
        $s = $this->share->findOneBy(array(
            'var' => $username,
            'var2' => $password,
            'shareType_id' => \App\Presenters\FtpPresenter::shareType_id
        ));
        
        $out = array();
        if(!$s) {
            $out[] = 'auth_ok:0';
        } else {
            $out[] = 'auth_ok:1';
            $out[] = 'uid:' . Model\Share::shareuid;
            $out[] = 'gid:' . Model\Share::sharegid;
            $out[] = 'dir:'. Model\Share::dataBaseUrl . $s->folder->name.'/';
        }
        
        $out[] = 'end';
        $this->sendText($out);
    }
    
    public function renderAuthWebdav() {
        if($this->httpRequest->getMethod() != "POST") {
            $this->error('Neplatná metoda.', 403);
        }
        
        $username = $this->httpRequest->getPost('username', '');
        $password = $this->httpRequest->getPost('password', '');
        
        $s = $this->share->findOneBy(array(
            'var' => $username,
            'var2' => $password,
            'shareType_id' => \App\Presenters\WebdavPresenter::shareType_id
        ));
        
        $out = array("status" => 0);
        
        if($s) {
            $out["status"] = 1;
            $out["username"] = $username;
            $out["folder"] = Model\Share::dataBaseUrl . $s->folder->name;
        }
        
        $this->sendResponse(new JsonResponse($out));
    }
    
    public function renderFolderWebdav() {
        if($this->httpRequest->getMethod() != "POST") {
            $this->error('Neplatná metoda.', 403);
        }
        
        $username = $this->httpRequest->getPost('username', '');
        
        $s = $this->share->findOneBy(array(
            'var' => $username,
            'shareType_id' => \App\Presenters\WebdavPresenter::shareType_id
        ));
        
        $out = array("status" => 0);
        
        if($s) {
            $out["status"] = 1;
            $out["folder"] = Model\Share::dataBaseUrl . $s->folder->name;
        }
        
        $this->sendResponse(new JsonResponse($out));
    }
    
    /**
     * Serialize array and send it out
     * 
     * @param string[] $data
     */
    private function sendText($data) {
        $text = implode("\n", $data);
        $this->sendResponse(new TextResponse($text . "\n"));
    }
}
