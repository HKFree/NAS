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
            'var2' => $password
        ));
        
        $out = array();
        if(!$s) {
            $out[] = 'auth_ok:0';
        } else {
            $out[] = 'auth_ok:1';
            $out[] = 'uid:65000';
            $out[] = 'gid:65000';
            $out[] = 'dir:/mnt/nas'.$s->folder->name.'/';
        }
        
        $out[] = 'end';
        $this->send($out);
    }
    
    private function send($data) {
        $text = implode("\n", $data);
        $this->sendResponse(new TextResponse($text . "\n"));
    }
}
