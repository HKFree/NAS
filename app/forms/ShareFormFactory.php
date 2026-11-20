<?php

namespace App\Forms;

use Nette\Application\UI\Form;
use Nette\Forms\Rendering\DefaultFormRenderer;

final class ShareFormFactory
{
    public function create(): Form
    {
        $form = new Form;
        $form->getElementPrototype()->class('form-horizontal');

        /** @var DefaultFormRenderer $renderer */
        $renderer = $form->getRenderer();
        $renderer->wrappers['pair']['container'] = 'div class=form-group';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-3 control-label"';
        $renderer->wrappers['control']['container'] = 'div class=col-sm-9';
        $renderer->wrappers['control']['.text'] = 'form-control';
        $renderer->wrappers['control']['.submit'] = 'btn btn-primary';

        return $form;
    }
}
