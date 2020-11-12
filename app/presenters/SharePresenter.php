<?php

namespace App\Presenters;

use Nette,
    App\Model,
    App\Model\ByteHelper,
    Nette\Application\UI\Form,
    Instante\Bootstrap3Renderer\BootstrapRenderer;


/**
 * Common presenter for all shares
 */
class SharePresenter extends BasePresenter
{  
    /** @var Model\Folder @inject */
    public $folder;
    
    /** @var Model\Share @inject */
    public $share;
    
    /** @var Model\StorageManager @inject */
    public $sm;
    
    /** @var \Instante\ExtendedFormMacros\IFormFactory @inject */
    public $formFactory;
    
    public function renderEdit($id) {
        $f = $this->folder->find($id);
        
        if(!$f) {
            $this->error("Složka s daným ID neexistuje.");
        }
        
        if(ByteHelper::getDegree($f->name) == 1) {
            $this->error("Základní složku uživatele nelze sdílet.");
        }
        
        if(!$this->sm->checkPermissions($id)){
            $this->error("Na editaci této složky nemáte právo.");
        }
        
        return($f);
    }
}
