<?php

namespace App\Presenters;

use Nette,
    App\Model,
    App\Model\ByteHelper,
    Nette\Application\UI\Form,
    Instante\Bootstrap3Renderer\BootstrapRenderer;


class StatsPresenter extends BasePresenter
{  
    /** @var Model\Folder @inject */
    public $folder;
    
    /** @var Model\ShareType @inject */
    public $shareType;
    
    /** @var Model\StorageConnector @inject */
    public $sc;
    
    /** @var Model\StorageManager @inject */
    public $sm;

    /** @var Model\Share @inject */
    public $share;
    
    public function renderDefault() {
        $this->template->userStats = $this->sm->getUserStats();
        
        $this->template->shareTypes = $this->shareType->findAll();
        $this->template->shareGroups = $this->share->findAll()->select('count(*) AS c, shareType_id')->group('shareType_id');
        /*$this->template->folders = $this->folder->findAll()->where('(
            LENGTH(name)
            - LENGTH( REPLACE ( name, "/", "") ) 
        ) = 1');*/
        //$this->template->shareTypes = $this->shareType->findBy(array('enabled' => 1));
        //$this->template->transformer = $this->foldersSharesTransformer;
        //$this->template->getFolder = $this->sc->getFolder;
        
        //\Tracy\Dumper::dump($this->sc->getFolders());
        //\Tracy\Dumper::dump($this->sc->getFolders());
    }
    
    public function foldersSharesTransformer($shares) {
        $sharesNames = array();
        foreach($shares as $share) {
            $sharesNames[] = $share->shareType->humanName;
        }
        return(implode(', ', $sharesNames));
    }
}
