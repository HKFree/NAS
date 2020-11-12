<?php

namespace App\Presenters;

use Nette,
    App\Model,
    App\Model\ByteHelper,
    Nette\Application\UI\Form,
    Instante\Bootstrap3Renderer\RenderModeEnum;


class SlozkyPresenter extends BasePresenter
{  
    /** @var Model\Folder @inject */
    public $folder;
    
    /** @var Model\ShareType @inject */
    public $shareType;
    
    /** @var Model\StorageConnector @inject */
    public $sc;
    
    /** @var Model\StorageManager @inject */
    public $sm;
    
    /** @var \Instante\ExtendedFormMacros\IFormFactory @inject */
    public $formFactory;
    
    public function renderDefault() {
        $this->template->folders = $this->folder->findBy(array('user_id' => $this->user->id));
        $this->template->shareTypes = $this->shareType->findBy(array('enabled' => 1));
        //$this->template->transformer = $this->foldersSharesTransformer;
        $this->template->getFolder = [$this->sc, 'getFolder'];
        
        //\Tracy\Dumper::dump($this->sc->getFolders());
        //\Tracy\Dumper::dump($this->sc->getFolders());
    }
    
    public function foldersSharesTransformer($shares) {
        $sharesNames = array();
        foreach($shares as $share) {
            $sharesNames[] = $share->shareType->humanName;
        }
        return(implode(', ', $sharesNames));
    }
    
    protected function createComponentSlozkaEditForm() {
        $form = $this->formFactory->create();
        $form->getRenderer()->setRenderMode(RenderModeEnum::HORIZONTAL);

        $form->addText('name', 'Jméno složky')
             ->addRule(Form::FILLED, 'Jméno složky musí být vyplněno');
        
        $form->addText('size', 'Velikost');
        
        $form->addText('comment', 'Poznámka');
        
        $form->addCheckbox('dedicatedShare', 'Dedikovat pro NextCloud');

        $form->addHidden('id');
        
        $form->addSubmit('send', 'Uložit');

        $form->onSuccess[] = array($this, 'slozkaEditFormSucceeded');
        $form->onValidate[] = array($this, 'slozkaEditFormValidate');
        
        return $form;
    }

    public function slozkaEditFormValidate(Form $form, $values) {  
        if(empty($values->id)) {
            if(empty($values->name)) {
                $form->addError('Jméno složky musí být vyplněno');
            }

            if($this->sm->getFolder($values->name, true)) {
                $form->addError('Tato složka již existuje.');
            }

            if(!preg_match('/^[a-zA-Z0-9\-_]+$/', $values->name)) {
                $form->addError('Jméno složky musí obsahovat pouze písmena, čísla a znaky -_. Neuvádějte lomítka a nepoužívejte diakritiku.');
            }
        }
        
        $size = ByteHelper::humanToBytes($values->size);
        if(!empty($values->size) && ($size == 0)) {
            $form->addError('Velikost složky není validní.');
        }
        
        if(!(empty($values->size) || $size == 0) && ($size < 10*pow(1024, 2))) {
            $form->addError('Složka musí být velká minimálně 10 MB');
        }
        
        if(!empty($values->id)) {
            $f = $this->folder->find($values->id);
            if(!$f) {
                $this->error('Složka s tímto ID neexistuje.');
            }
            $scFolder = $this->sc->getFolder($f->name);
            if(!$scFolder) {
                $this->error('Složka s tímto ID neexistuje.');
            }
            
            if(($size > 0) && ($scFolder->space_used > $size)) {
                $form->addError('Zadaná velikost složky (' . ByteHelper::bytesToHuman($size, 2, TRUE) . ') je menší než aktuálně zabrané místo (' . ByteHelper::bytesToHuman($scFolder->space_used, 2, TRUE) . ')!');
            }
        }
    }
    
    public function slozkaEditFormSucceeded(Form $form, $values) {
        if(!empty($value->id) && !$this->sm->checkPermissions($values->id)) {
            $this->error('Na editaci této složky nemáte právo.');
        }
        
        $size = ByteHelper::humanToBytes($values->size);
        if(empty($values->size) || $size == 0) {
            $size = NULL;
        }
        
        $err = FALSE;
        
        if(empty($values->id)) {
            $this->sm->createUserFolder($values->name, $size, $values->comment, $values->dedicatedShare?\App\Presenters\NextcloudPresenter::shareType_id:0);
        } else {
            $this->folder->find($values->id)->update(array(
                'comment' => $values->comment,
            ));
            $err = ($this->sm->changeQuota($values->id, $size) != TRUE);
        }
        
        if($err) {
            $this->flashMessage('Změna velikosti složky selhala. Zkontrolujte zadanou velikost, prosím.', 'danger');
        } else {
            $this->flashMessage('Složka byla úspěšně uložena.', 'success');
        }
        $this->redirect('Slozky:');
    }
    
    public function renderDelete($id) {
        $f = $this->folder->find($id);
        if(!$f) {
            $this->error('Složka s tímto ID neexistuje.');
        }
        
        if(!$this->sm->checkPermissions($id)) {
            $this->error('Na smazání této složky nemáte právo.');
        }
        
        $this->template->f = $f;
    }
    
    public function actionDeleteFinal($id) {
        $f = $this->folder->find($id);
        if(!$f) {
            $this->error('Složka s tímto ID neexistuje.');
        }
        
        if(!$this->sm->checkPermissions($id)) {
            $this->error('Na smazání této složky nemáte právo.');
        }
        
        $fm = $this->sm->getFolder($f->name);
        $fc = $fm["sc"];
        $maxSpace = 2 * 1024 * 1024;
        if($fc->space_used > $maxSpace) {
            $this->flashMessage('Chyba, složka není prázdná. Obsahuje ' . \Latte\Runtime\Filters::bytes($fc->space_used) . ' dat.', 'danger');
            $this->redirect('Slozky:delete', $id);
        }
        
        $sharescount = $f->related('share.folder_id')->count();
        if($sharescount != 0) {
            $this->flashMessage('Chyba, složka má stále zaplá některá sdílení. Deaktivujte je před smazáním, prosím.', 'danger');
            $this->redirect('Slozky:delete', $id);
        }
        
        $state = $this->sm->deleteUserFolder($id);
        if($state == FALSE) {
            $this->flashMessage('Omlouváme se, složku se nepodařilo smazat kvůli chybě v systému. Prosím kontaktujte podporu.', 'danger');
            $this->redirect('Slozky:');
        }
        
        $this->flashMessage('Složka byla úspěšně smazána.', 'success');
        $this->redirect('Slozky:');
    }
    
    public function renderEdit($id) {
        $f = $this->folder->find($id);
        if(!$f) {
            $this->error('Složka s tímto ID neexistuje.');
        }
        
        if(!$this->sm->checkPermissions($id)) {
            $this->error('Na editaci této složky nemáte právo.');
        }

        $scFolder = $this->sc->getFolder($f->name);
        if(!$scFolder) {
            $this->error('Složka s tímto ID neexistuje.');
        }
        
        $defaults["id"] = $f->id;
        $defaults["name"] = $f->name;
        $defaults["size"] = ($scFolder->quota == 0 ? "" : ByteHelper::bytesToHuman($scFolder->quota, 2, TRUE));
        $defaults["comment"] = $f->comment;
        $defaults["dedicatedShare"] = ($f->dedicatedShare != 0);
        
        //$this['slozkaEditForm']['name']->setOmitted(FALSE);
        $this['slozkaEditForm']->setDefaults($defaults);
        $this['slozkaEditForm']['name']->setAttribute('readonly', 'readonly');
        $this['slozkaEditForm']['name']->setValue($f->name);
        $this['slozkaEditForm']['dedicatedShare']->setDisabled();
        $this['slozkaEditForm']['dedicatedShare']->setValue($f->dedicatedShare != 0);
    }
}
