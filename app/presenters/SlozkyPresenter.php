<?php

namespace App\Presenters;

use Nette,
    App\Model,
    App\Model\ByteHelper,
    Nette\Application\UI\Form,
    Instante\Bootstrap3Renderer\BootstrapRenderer;


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
    
    public function renderDefault() {
        $this->template->folders = $this->folder->findBy(array('user_id' => $this->user->id));
        $this->template->shareTypes = $this->shareType->findBy(array('enabled' => 1));
        //$this->template->transformer = $this->foldersSharesTransformer;
        $this->template->getFolder = $this->sc->getFolder;
        
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
        $form = new Form;

        $form->addText('name', 'Jméno složky')
             ->addRule(Form::FILLED, 'Jméno složky musí být vyplněno');
        
        $form->addText('size', 'Velikost');
        
        $form->addText('comment', 'Poznámka');

        $form->addHidden('id');
        
        $form->addSubmit('send', 'Uložit');

        $form->onSuccess[] = array($this, 'slozkaEditFormSucceeded');
        $form->onValidate[] = array($this, 'slozkaEditFormValidate');
        
        $form->setRenderer(new BootstrapRenderer);
        return $form;
    }

    public function slozkaEditFormValidate(Form $form, $values) {        
        if(empty($values->name)) {
            $form->addError('Jméno složky musí být vyplněno');
        }
        
        if($this->sm->getFolder($values->name, true)) {
            $form->addError('Tato složka již existuje.');
        }
        
        if(!preg_match('/^[a-zA-Z0-9\-_]+$/', $values->name)) {
            $form->addError('Jméno složky musí obsahovat pouze písmena, čísla a znaky -_. Neuvádějte lomítka a nepoužívejte diakritiku.');
        }
        
        $size = ByteHelper::humanToBytes($values->size);
        if(!empty($values->size) && ($size == 0)) {
            $form->addError('Velikost složky není validní.');
        }
        
        if(!(empty($values->size) || $size == 0) && ($size < 10*pow(1024, 2))) {
            $form->addError('Složka musí být velká minimálně 10 MB');
        }
    }
    
    public function slozkaEditFormSucceeded(Form $form, $values) {  
        $size = ByteHelper::humanToBytes($values->size);
        if(empty($values->size) || $size == 0) {
            $size = NULL;
        }
        
        if(empty($values->id)) {
            $this->sm->createUserFolder($values->name, $size, $values->comment);
        } else {
            //$id = $values->id;
            //$this->uzivatel->find($id)->update($values);
            //$this->log->l('uzivatel.edit', $id);
        }
        
        $this->flashMessage('Složka byla úspěšně uložena.', 'success');
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
}
