<?php

namespace App\Presenters;

use Nette;
use App\Forms\SignFormFactory;
use App\Model;


class SignPresenter extends BasePresenter
{
	/** @var SignFormFactory @inject */
	public $factory;
    
    /** @var Model\StorageManager @inject */
    public $sm;

    /**
	 * Sign-in form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSignInForm()
	{
		$form = $this->factory->create();
		$form->onSuccess[] = function ($form) {
            $form->getPresenter()->sm->checkAndCreateRoot();
			$form->getPresenter()->redirect('Homepage:');
		};
		return $form;
	}


	public function actionOut()
	{
		$this->getUser()->logout();
		$this->flashMessage('Byl jste odhlášen.');
		$this->redirect('in');
	}

}
