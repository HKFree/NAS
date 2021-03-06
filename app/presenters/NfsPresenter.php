<?php

namespace App\Presenters;

use Nette,
    App\Model,
    App\Model\ByteHelper,
    Nette\Application\UI\Form,
    Instante\Bootstrap3Renderer\RenderModeEnum;


class NfsPresenter extends SharePresenter
{     
    const shareType_id = 3;
   
    public function renderEdit($id) {
        $f = parent::renderEdit($id);
        
        $defaults["folder_id"] = $id;
        
        $s = $this->share->findOneBy(array(
            'folder_id' => $id,
            'shareType_id' => self::shareType_id
        ));
        
        if($s) {
            $defaults["id"] = $s->id;
            $defaults["ips"] = $s->var;
            $defaults["export"] = true;
        }
        
        $this['nfsEditForm']->setDefaults($defaults);
        $this->template->slozka = $f->name;
        $this->template->nfsurl = "nas.hkfree.org:" . Model\Share::dataBaseUrl . $f->name . "/";
    }

    protected function createComponentNfsEditForm() {
        $form = $this->formFactory->create();
        $form->getRenderer()->setRenderMode(RenderModeEnum::HORIZONTAL);
        
        $form->addCheckbox('export', 'Exportovat tuto složku přes NFS?');
        
        $form->addText('ips', 'Adresy k exportu')
             ->addConditionOn($form['export'], Form::EQUAL, TRUE)
             ->addRule(Form::FILLED, 'Alespoň jedna adresa musí být vyplněna');

        $form->addHidden('folder_id');
        $form->addHidden('id');
        
        $form->addSubmit('send', 'Uložit');

        $form->onSuccess[] = array($this, 'NfsEditFormSucceeded');
        $form->onValidate[] = array($this, 'NfsEditFormValidate');
        
        return $form;
    }

    public function NfsEditFormValidate(Form $form, $values) {        
        if($values->export) {
            if(preg_match('/\s/', $values->ips)) {
                $form->addError('Pole adres obsahuje mezeru. Jako oddělovač používejte čárku bez mezer, prosím!');
            }

            $f = $this->folder->find($values->folder_id);
            if(!$this->share->checkNFSShare($f->name, $values->ips)) {
                $form->addError('Pole adres není validní. Prosím přečtěte si znovu instrukce a zadání opakujte.');
            }
        }
    }
    
    public function NfsEditFormSucceeded(Form $form, $values) {  
        if(empty($values->id) && $values->export) {
            $this->share->insert(array(
                'shareType_id' => self::shareType_id,
                'folder_id' => $values->folder_id,
                'var' => $values->ips
            ));
            $this->flashMessage('NFS share byl úspěšně vytvořen.', 'success');
            
        } elseif(!empty($values->id) && !$values->export) {
            $this->share->find($values->id)->delete();
            $this->flashMessage('NFS share byl úspěšně smazán.', 'success');
            
        } elseif(!empty($values->id) && $values->export) {
            $this->share->find($values->id)->update(array(
                'var' => $values->ips
            ));
            $this->flashMessage('NFS share byl úspěšně upraven.', 'success');
        }
        
        $this->share->exportNFS();
        $this->redirect('Slozky:');
    }
}
