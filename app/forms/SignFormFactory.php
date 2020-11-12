<?php

namespace App\Forms;

use Nette;
use Nette\Application\UI\Form;
use Nette\Security\User;
use Nette\SmartObject;

class SignFormFactory
{
	/** @var User */
	private $user;


	public function __construct(User $user)
	{
		$this->user = $user;
	}


	/**
	 * @return Form
	 */
	public function create()
	{
		$form = new Form;
		$form->addText('username', 'User ID:')
			->setRequired('Zadejte svoje HKFree User ID, prosím.');

		$form->addPassword('password', 'Heslo:')
			->setRequired('Zadejte svoje heslo, prosím.');

		$form->addCheckbox('remember', 'Zapamatovat si přihlášení')
             ->setDefaultValue(true);

		$form->addSubmit('send', 'Přihlásit se');

		$form->onSuccess[] = array($this, 'formSucceeded');
		return $form;
	}


	public function formSucceeded(Form $form, $values)
	{
		if ($values->remember) {
			$this->user->setExpiration('14 days', FALSE);
		} else {
			$this->user->setExpiration('20 minutes', TRUE);
		}

		try {
			$this->user->login($values->username, $values->password);
		} catch (Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}

}
