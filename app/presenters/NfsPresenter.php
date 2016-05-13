<?php

namespace App\Presenters;

use Nette,
    App\Model,
    App\Model\ByteHelper,
    Nette\Application\UI\Form,
    Instante\Bootstrap3Renderer\BootstrapRenderer;


class NfsPresenter extends BasePresenter
{  
    /** @var Model\Folder @inject */
    public $folder;
    
    /** @var Model\Share @inject */
    public $share;
    
    const shareType_id = 3;
   
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
            $defaults["ips"] = $s->var;
            $defaults["export"] = true;
        }
        
        $this['nfsEditForm']->setDefaults($defaults);
        //$this->template->osoba = $u;
        $this->template->slozka = $f->name;
        $this->template->nfsurl = "nas.hkfree.org:" . Model\Share::dataBaseUrl . $f->name . "/";
    }

    protected function createComponentNfsEditForm() {
        $form = new Form;
        
        $form->addCheckbox('export', 'Exportovat tuto složku přes NFS?');
        
        $form->addText('ips', 'Adresy k exportu')
             ->addRule(Form::FILLED, 'Alespoň jedna adresa musí být vyplněna');

        $form->addHidden('folder_id');
        $form->addHidden('id');
        
        $form->addSubmit('send', 'Uložit');

        $form->onSuccess[] = array($this, 'NfsEditFormSucceeded');
        $form->onValidate[] = array($this, 'NfsEditFormValidate');
        
        $form->setRenderer(new BootstrapRenderer);
        return $form;
    }

    public function NfsEditFormValidate(Form $form, $values) {        
        if($values->export) {
            if(preg_match('/\s/', $values->ips)) {
                $form->addError('Pole adres obsahuje mezeru. Jako oddělovač používejte čárku bez mezer, prosím!');
            }

            $f = $this->folder->find($values->folder_id);
            if(!$this->share->checkShare($f->name, $values->ips)) {
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
