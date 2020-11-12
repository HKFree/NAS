<?php

namespace App\Presenters;

use Nette,
    App\Model,
    App\Model\ByteHelper,
    Nette\Application\UI\Form,
    Instante\Bootstrap3Renderer\RenderModeEnum;

class NextcloudPresenter extends SharePresenter
{  
    /** @var Model\Folder @inject */
    public $folder;
    
    /** @var Model\Share @inject */
    public $share;
    
    const shareType_id = 6;
    
    public function renderEdit($id) {
        $f = parent::renderEdit($id);
        
        $s = $this->share->findOneBy(array(
            'folder_id' => $id,
            'shareType_id' => self::shareType_id
        ));
        
        if(!$s) {
            $this->redirect('Nextcloud:create', array('id' => $id));
        }

        $defaults["id"] = $s->id;
        
        $this['nextcloudEditForm']->setDefaults($defaults);
        $this->template->slozka = $f->name;
        $this->template->username = $s->var;
    }
    
    protected function createComponentNextcloudEditForm() {
        $form = $this->formFactory->create();
        $form->getRenderer()->setRenderMode(RenderModeEnum::HORIZONTAL);

        $form->addText('password', 'Heslo')
             ->addRule(Form::MIN_LENGTH, 'Heslo musí mít alespoň 7 znaků', 7)
             ->setRequired(TRUE);
        
        $form->addHidden('id');
        
        $form->addSubmit('send', 'Změnit heslo');

        $form->onSuccess[] = array($this, 'NextcloudEditFormSucceeded');
        
        return $form;
    }
   
    public function NextcloudEditFormSucceeded(Form $form, $values) {  
        if(!empty($values->id)) {
            $this->share->find($values->id)->update(array(
                'var2' => password_hash($values->password, PASSWORD_DEFAULT)
            ));
            $this->flashMessage('Heslo pro NextCloud účet bylo úspěšně změněno.', 'success');
        }
        
        $this->redirect('Slozky:');
    }
    
    public function renderCreate($id) {
        $f = parent::renderEdit($id);
        
        $defaults["folder_id"] = $id;
        
        $s = $this->share->findOneBy(array(
            'folder_id' => $id,
            'shareType_id' => self::shareType_id
        ));
        
        if($s) {
            $this->flashMessage('Tato složka má NextCloud účet již vytvořen!', 'error');
            $this->redirect('Slozky:');
        }
        
        $this['nextcloudCreateForm']->setDefaults($defaults);
        $this->template->slozka = $f->name;
    }

    protected function createComponentNextcloudCreateForm() {
        $form = $this->formFactory->create();
        $form->getRenderer()->setRenderMode(RenderModeEnum::HORIZONTAL);

        $form->addText('username', 'Přihlašovací jméno')
             ->addRule(Form::MIN_LENGTH, 'Přihlašovací jméno musí mít alespoň 5 znaků', 5)
             ->addRule(Form::PATTERN, 'Přihlašovací jméno musí obsahovat pouze malé znaky anglické abecedy', '[a-z]*')
             ->setRequired(TRUE);

        $form->addText('password', 'Heslo')
             ->addRule(Form::MIN_LENGTH, 'Heslo musí mít alespoň 7 znaků', 7)
             ->setRequired(TRUE);
        
        $form->addHidden('folder_id');
        $form->addHidden('id');
        
        $form->addSubmit('send', 'Vytvořit');

        $form->onSuccess[] = array($this, 'NextcloudCreateFormSucceeded');
        $form->onValidate[] = array($this, 'NextcloudCreateFormValidate');
        
        return $form;
    }

    public function NextcloudCreateFormValidate(Form $form, $values) {
        $eqUsername = $this->share->findAll()
            ->where('shareType_id = ?', self::shareType_id)
            ->where('NOT folder_id = ?', $values->folder_id)
            ->where('var = ?', $values->username)->fetchAll();
        if($eqUsername) {
            $form->addError('Toto přihlašovací jméno už existuje, zvolte prosím jiné.');
        }       
    }
    
    public function NextcloudCreateFormSucceeded(Form $form, $values) {  
        if(empty($values->id)) {
            $this->share->insert(array(
                'shareType_id' => self::shareType_id,
                'folder_id' => $values->folder_id,
                'var' => $values->username,
                'var2' => password_hash($values->password, PASSWORD_DEFAULT)
            ));
            $this->flashMessage('NextCloud účet byl úspěšně vytvořen.', 'success');
        }
        
        $this->redirect('Slozky:');
    }
}
