<?php

namespace App\Presenters;

use Nette;
use App\Model;


/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
    /** @var Nette\Http\Request @inject */
    public $request;
    
    private $maintenance = TRUE;
    
    protected function startup() {
        parent::startup();
        
        $devIPs = array('10.107.91.237');
        if(!in_array($this->request->getRemoteAddress(), $devIPs) && $this->maintenance) {
            if($this->getPresenter()->name != "Homepage" || $this->getAction() != "maintenance") {
                $this->redirect("Homepage:maintenance");
            }
        }
        
        $nonLoginPresenters = array('Sign', 'Homepage', 'Api');
        $presenterName = $this->getPresenter()->name;
        
        if(!in_array($presenterName, $nonLoginPresenters) && ($this->user->isLoggedIn() != true)) {
            $this->flashMessage("Pro vstup do systému se přihlašte, prosím.");
            $this->redirect("Sign:in");
        }
    }
    
    protected function beforeRender() {
        parent::beforeRender();
        $this->template->maint = $this->maintenance;
        
        /*
        $this->template->isLogged = $this->user->isLoggedIn();
        if($this->template->isLogged) {
            $this->template->uzivatel = $this->user->getIdentity()->jmeno;
        }
         * 
         */
    }
}
