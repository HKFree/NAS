<?php

namespace App\Presenters;

use Nette,
    App\Model,
    App\Model\ByteHelper,
    Nette\Application\UI\Form,
    Instante\Bootstrap3Renderer\RenderModeEnum;


class RsyncPresenter extends SharePresenter
{  
    const shareType_id = 5;
   
    public function renderEdit($id) {
        $f = parent::renderEdit($id);
                
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
        
        $this['rsyncEditForm']->setDefaults($defaults);
        $this->template->slozka = $f->name;
        $folderParsed = explode("/", $f->name);
        $moduleName = $f->user_id . "-" . $folderParsed[2];
        
        $this->template->moduleName = $moduleName;
        $this->template->rsyncurl = "rsync://<user>@nas.hkfree.org/" . $moduleName;
    }

    protected function createComponentRsyncEditForm() {
        $form = $this->formFactory->create();
        $form->getRenderer()->setRenderMode(RenderModeEnum::HORIZONTAL);
        
        $form->addCheckbox('export', 'Exportovat tuto složku přes Rsync?');
        
        $form->addText('username', 'Přihlašovací jméno')
             ->addConditionOn($form['export'], Form::EQUAL, TRUE)
             ->addRule(Form::FILLED, 'Přihlašovací jméno musí být zadáno');

        $form->addText('password', 'Heslo')
             ->addConditionOn($form['export'], Form::EQUAL, TRUE)
             ->addRule(Form::MIN_LENGTH, 'Heslo musí mít alespoň 8 znaků', 8)
             ->setRequired(TRUE);

        $form->addHidden('folder_id');
        $form->addHidden('id');
        
        $form->addSubmit('send', 'Uložit');

        $form->onSuccess[] = array($this, 'RsyncEditFormSucceeded');
        $form->onValidate[] = array($this, 'RsyncEditFormValidate');
        
        return $form;
    }

    public function RsyncEditFormValidate(Form $form, $values) {        
        if($values->export) {
            $eqUsername = $this->share->findAll()
                ->where('shareType_id = ?', self::shareType_id)
                ->where('NOT folder_id = ?', $values->folder_id)
                ->where('var = ?', $values->username)->fetchAll();
            if($eqUsername) {
                $form->addError('Toto přihlašovací jméno už existuje, zvolte prosím jiné.');
            }  
        }
    }
    
    public function RsyncEditFormSucceeded(Form $form, $values) {  
        if(empty($values->id) && $values->export) {
            $this->share->insert(array(
                'shareType_id' => self::shareType_id,
                'folder_id' => $values->folder_id,
                'var' => $values->username,
                'var2' => $values->password
            ));
            $this->flashMessage('Rsync share byl úspěšně vytvořen.', 'success');
            
        } elseif(!empty($values->id) && !$values->export) {
            $this->share->find($values->id)->delete();
            $this->flashMessage('Rsync share byl úspěšně smazán.', 'success');
            
        } elseif(!empty($values->id) && $values->export) {
            $this->share->find($values->id)->update(array(
                'var' => $values->username,
                'var2' => $values->password
            ));
            $this->flashMessage('Rsync share byl úspěšně upraven.', 'success');
        }
        
        $this->share->exportRsync();
        $this->redirect('Slozky:');
    }
}
