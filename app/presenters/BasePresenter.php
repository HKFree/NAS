<?php

namespace App\Presenters;

use Nette;
use App\Model;


/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
    protected function startup() {
        parent::startup();
        
        $nonLoginPresenters = array('Sign', 'Homepage', 'Api');
        $presenterName = $this->getPresenter()->name;
        
        if(!in_array($presenterName, $nonLoginPresenters) && ($this->user->isLoggedIn() != true)) {
            $this->flashMessage("Pro vstup do systému se přihlašte, prosím.");
            $this->redirect("Sign:in");
        }
    }
    
    protected function beforeRender() {
        parent::beforeRender();
        /*
        $this->template->isLogged = $this->user->isLoggedIn();
        if($this->template->isLogged) {
            $this->template->uzivatel = $this->user->getIdentity()->jmeno;
        }
         * 
         */
    }
}
