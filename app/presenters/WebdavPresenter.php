<?php

namespace App\Presenters;

use Nette,
    App\Model,
    App\Model\ByteHelper,
    Nette\Application\UI\Form,
    Instante\Bootstrap3Renderer\BootstrapRenderer;


class WebdavPresenter extends BasePresenter
{  
    /** @var Model\Folder @inject */
    public $folder;
    
    /** @var Model\Share @inject */
    public $share;
    
    const shareType_id = 2;
   
    public function renderEdit($id) {
        $f = $this->folder->find($id);
        
        if(!$f) {
            $this->error("Složka s daným ID neexistuje.");
        }
        
        if(ByteHelper::getDegree($f->name) == 1) {
            $this->error("Základní složku uživatele nelze sdílet.");
        }
        
        $defaults["folder_id"] = $id;
        
        $s = $this->share->findOneBy(array(
            'folder_id' => $id,
            'shareType_id' => self::shareType_id
        ));
        
        if($s) {
            $defaults["id"] = $s->id;
            $defaults["username"] = $s->var;
            $defaults["password"] = $s->var2;
            $defaults["export"] = true;
        }
        
        $this['webdavEditForm']->setDefaults($defaults);
        $this->template->slozka = $f->name;
        //$this->template->osoba = $u;
        //$this->template->ftpurl = "ftp://nas.hkfree.org/";
    }

    protected function createComponentWebdavEditForm() {
        $form = new Form;
        
        $form->addCheckbox('export', 'Exportovat tuto složku na WebDAV?');
        
        $form->addText('username', 'Přihlašovací jméno')
             ->addConditionOn($form['export'], Form::EQUAL, TRUE)
             ->addRule(Form::FILLED, 'Přihlašovací jméno musí být zadáno');

        $form->addText('password', 'Heslo')
             ->addConditionOn($form['export'], Form::EQUAL, TRUE)
             ->addRule(Form::MIN_LENGTH, 'Heslo musí mít alespoň 8 znaků', 8);
        
        $form->addHidden('folder_id');
        $form->addHidden('id');
        
        $form->addSubmit('send', 'Uložit');

        $form->onSuccess[] = array($this, 'WebdavEditFormSucceeded');
        $form->onValidate[] = array($this, 'WebdavEditFormValidate');
        
        $form->setRenderer(new BootstrapRenderer);
        return $form;
    }

    public function WebdavEditFormValidate(Form $form, $values) {
        $eqUsername = $this->share->findAll()
            ->where('shareType_id = ?', self::shareType_id)
            ->where('NOT folder_id = ?', $values->folder_id)
            ->where('var = ?', $values->username)->fetchAll();
        if($eqUsername) {
            $form->addError('Toto přihlašovací jméno už existuje, zvolte prosím jiné.');
        }       
    }
    
    public function WebdavEditFormSucceeded(Form $form, $values) {  
        if(empty($values->id) && $values->export) {
            $this->share->insert(array(
                'shareType_id' => self::shareType_id,
                'folder_id' => $values->folder_id,
                'var' => $values->username,
                'var2' => $values->password
            ));
            $this->flashMessage('WebDAV share byl úspěšně vytvořen.', 'success');
        } elseif(!empty($values->id) && !$values->export) {
            $this->share->find($values->id)->delete();
            $this->flashMessage('WebDAV share byl úspěšně smazán.', 'success');
        } elseif(!empty($values->id) && $values->export) {
            $this->share->find($values->id)->update(array(
                'var' => $values->username,
                'var2' => $values->password
            ));
            $this->flashMessage('WebDAV share byl úspěšně upraven.', 'success');
        }
        
        $this->redirect('Slozky:');
    }
}
