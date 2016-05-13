<?php

namespace App\Presenters;

use Nette,
    App\Model;


class HomepagePresenter extends BasePresenter
{     
    /** @var Model\StorageConnector @inject **/
    public $sc;
    
    public function renderDefault() {
        $this->template->rootf = $this->sc->getFolder('/');
    }
    
    public function renderMaintenance() {
    }
}
