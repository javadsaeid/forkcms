<?php

namespace Backend\Modules\Faq\Actions;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Backend\Core\Engine\Base\ActionDelete as BackendBaseActionDelete;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Modules\Faq\Engine\Model as BackendFaqModel;
use Backend\Modules\Faq\Form\FaqCategoryDeleteType;

/**
 * This action will delete a category
 */
class DeleteCategory extends BackendBaseActionDelete
{
    public function execute(): void
    {
        $deleteForm = $this->createForm(FaqCategoryDeleteType::class);
        $deleteForm->handleRequest($this->getRequest());
        if (!$deleteForm->isSubmitted() || !$deleteForm->isValid()) {
            $this->redirect(BackendModel::createURLForAction('Categories') . '&error=something-went-wrong');
        }
        $deleteFormData = $deleteForm->getData();

        $this->id = $deleteFormData['id'];

        // does the item exist
        if ($this->id !== 0 && BackendFaqModel::existsCategory($this->id)) {
            $this->record = (array) BackendFaqModel::getCategory($this->id);

            if (BackendFaqModel::deleteCategoryAllowed($this->id)) {
                parent::execute();

                // delete item
                BackendFaqModel::deleteCategory($this->id);

                // category was deleted, so redirect
                $this->redirect(
                    BackendModel::createURLForAction('Categories') . '&report=deleted-category&var=' .
                    rawurlencode($this->record['title'])
                );
            } else {
                $this->redirect(
                    BackendModel::createURLForAction('Categories') . '&error=delete-category-not-allowed&var=' .
                    rawurlencode($this->record['title'])
                );
            }
        } else {
            $this->redirect(
                BackendModel::createURLForAction('Categories') . '&error=non-existing'
            );
        }
    }
}
