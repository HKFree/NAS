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
}
