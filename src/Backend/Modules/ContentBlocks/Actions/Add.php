<?php

namespace Backend\Modules\ContentBlocks\Actions;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Backend\Core\Engine\Base\ActionAdd as BackendBaseActionAdd;
use Backend\Core\Engine\Authentication as BackendAuthentication;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Core\Engine\Form as BackendForm;
use Backend\Core\Language\Language as BL;
use Backend\Modules\ContentBlocks\Engine\Model as BackendContentBlocksModel;
use Backend\Modules\ContentBlocks\Entity\ContentBlock;

/**
 * This is the add-action, it will display a form to create a new item
 */
class Add extends BackendBaseActionAdd
{
    /**
     * The available templates
     *
     * @var array
     */
    private $templates = array();

    /**
     * Execute the action
     */
    public function execute()
    {
        parent::execute();
        $this->templates = BackendContentBlocksModel::getTemplates();
        $this->loadForm();
        $this->validateForm();
        $this->parse();
        $this->display();
    }

    /**
     * Load the form
     */
    private function loadForm()
    {
        $this->frm = new BackendForm('add');
        $this->frm->addText('title', null, null, 'form-control title', 'form-control danger title');
        $this->frm->addEditor('text');
        $this->frm->addCheckbox('hidden', true);

        // if we have multiple templates, add a dropdown to select them
        if (count($this->templates) > 1) {
            $this->frm->addDropdown('template', array_combine($this->templates, $this->templates));
        }
    }

    /**
     * Validate the form
     */
    private function validateForm()
    {
        if ($this->frm->isSubmitted()) {
            $this->frm->cleanupFields();
            $fields = $this->frm->getFields();

            // validate fields
            $fields['title']->isFilled(BL::err('TitleIsRequired'));
            $fields['text']->isFilled(BL::err('FieldIsRequired'));

            if ($this->frm->isCorrect()) {
                // build item
                $item['id'] = BackendContentBlocksModel::getMaximumId() + 1;
                $item['user_id'] = BackendAuthentication::getUser()->getUserId();
                $item['template'] = count($this->templates) > 1 ? $fields['template']->getValue() : $this->templates[0];
                $item['language'] = BL::getWorkingLanguage();
                $item['title'] = $fields['title']->getValue();
                $item['text'] = $fields['text']->getValue();
                $item['hidden'] = $fields['hidden']->getValue() ? 'N' : 'Y';
                $item['status'] = 'active';
                $item['created_on'] = BackendModel::getUTCDate();
                $item['edited_on'] = BackendModel::getUTCDate();

                // insert the item
                $item['revision_id'] = BackendContentBlocksModel::insert($item);

                // trigger event
                BackendModel::triggerEvent($this->getModule(), 'after_add', array('item' => $item));

                // everything is saved, so redirect to the overview
                $this->redirect(
                    BackendModel::createURLForAction('Index') . '&report=added&var=' .
                    rawurlencode($item['title']) . '&highlight=row-' . $item['id']
                );
            }
        }
    }
}
