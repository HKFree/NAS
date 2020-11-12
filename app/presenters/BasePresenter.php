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
    
    private $maintenance = FALSE;
    
    protected function startup() {
        parent::startup();
        
        $presenterName = $this->getPresenter()->name;
        $nonLoginPresenters = array('Sign', 'Homepage', 'Api', 'Navody');
        $maintenancePresenters = array('Api');
        
        $devIPs = array('10.107.91.237', '88.101.182.229', '10.107.253.2', '10.107.250.15');
        if(!in_array($this->request->getRemoteAddress(), $devIPs) && $this->maintenance && !in_array($presenterName, $maintenancePresenters)) {
            if($presenterName != "Homepage" || $this->getAction() != "maintenance") {
                $this->redirect("Homepage:maintenance");
            }
        }
        
        if(!in_array($presenterName, $nonLoginPresenters) && ($this->user->isLoggedIn() != true)) {
            $this->flashMessage("Pro vstup do systému se přihlašte, prosím.");
            $this->redirect("Sign:in");
        }
    }
    
    protected function beforeRender() {
        parent::beforeRender();
        $this->template->maint = $this->maintenance;
        $this->template->maintIP = $this->request->getRemoteAddress();
        
        /*
        $this->template->isLogged = $this->user->isLoggedIn();
        if($this->template->isLogged) {
            $this->template->uzivatel = $this->user->getIdentity()->jmeno;
        }
         * 
         */
    }
}
