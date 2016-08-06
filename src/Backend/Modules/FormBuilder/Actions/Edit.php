<?php

namespace Backend\Modules\FormBuilder\Actions;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Backend\Core\Engine\Base\ActionEdit as BackendBaseActionEdit;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Core\Engine\Form as BackendForm;
use Backend\Core\Engine\Language as BL;
use Frontend\Core\Engine\Language as FL;
use Backend\Modules\FormBuilder\Engine\Model as BackendFormBuilderModel;
use Backend\Modules\FormBuilder\Engine\Helper as FormBuilderHelper;

/**
 * This is the edit-action, it will display a form to edit an existing item
 */
class Edit extends BackendBaseActionEdit
{
    /**
     * Execute the action
     */
    public function execute()
    {
        $this->id = $this->getParameter('id', 'int');

        // does the item exist
        if ($this->id !== null && BackendFormBuilderModel::exists($this->id)) {
            parent::execute();
            $this->getData();
            $this->loadForm();
            $this->validateForm();
            $this->parse();
            $this->display();
        } else {
            // no item found, throw an exceptions, because somebody is fucking with our url
            $this->redirect(BackendModel::createURLForAction('Index') . '&error=non-existing');
        }
    }

    /**
     * Get the data
     */
    private function getData()
    {
        $this->record = BackendFormBuilderModel::get($this->id);
    }

    /**
     * Load the form
     */
    private function loadForm()
    {
        $this->frm = new BackendForm('edit');
        $this->frm->addText('name', $this->record['name']);
        $this->frm->addDropdown(
            'method',
            array(
                'database' => BL::getLabel('MethodDatabase'),
                'database_email' => BL::getLabel('MethodDatabaseEmail'),
            ),
            $this->record['method']
        );
        $this->frm->addText('email', implode(',', (array) $this->record['email']));
        $this->frm->addText('identifier', $this->record['identifier']);
        $this->frm->addEditor('success_message', $this->record['success_message']);

        // textfield dialog
        $this->frm->addText('textbox_label');
        $this->frm->addText('textbox_value');
        $this->frm->addText('textbox_placeholder');
        $this->frm->addText('textbox_classname');
        $this->frm->addCheckbox('textbox_required');
        $this->frm->addCheckbox('textbox_reply_to');
        $this->frm->addText('textbox_required_error_message');
        $this->frm->addDropdown(
            'textbox_validation',
            array(
                '' => '',
                'email' => BL::getLabel('Email'),
                'numeric' => BL::getLabel('Numeric'),
            )
        );
        $this->frm->addText('textbox_validation_parameter');
        $this->frm->addText('textbox_error_message');

        // textarea dialog
        $this->frm->addText('textarea_label');
        $this->frm->addTextarea('textarea_value');
        $this->frm->getField('textarea_value')->setAttribute('cols', 30);
        $this->frm->addText('textarea_placeholder');
        $this->frm->addText('textarea_classname');
        $this->frm->addCheckbox('textarea_required');
        $this->frm->addText('textarea_required_error_message');
        $this->frm->addDropdown('textarea_validation', array('' => ''));
        $this->frm->addText('textarea_validation_parameter');
        $this->frm->addText('textarea_error_message');

        // datetime dialog
        $this->frm->addText('datetime_label');
        $this->frm->addDropdown(
            'datetime_value_amount',
            array(
                '' => '',
                '1' => '+1',
                '2' => '+2',
                '3' => '+3',
                '4' => '+4',
                '5' => '+5',
            )
        );
        $this->frm->addDropdown(
            'datetime_value_type',
            array(
                '' => '',
                'today' => BL::getLabel('Today'),
                'day' => BL::getLabel('Day'),
                'week' => BL::getLabel('Week'),
                'month' => BL::getLabel('Month'),
                'year' => BL::getLabel('Year'),
            )
        );
        $this->frm->addDropdown(
            'datetime_type',
            array(
                'date' => BL::getLabel('Date'),
                'time' => BL::getLabel('Time'),
            )
        );
        $this->frm->addCheckbox('datetime_required');
        $this->frm->addText('datetime_required_error_message');
        $this->frm->addDropdown(
            'datetime_type',
            array(
                'date' => BL::getLabel('Date'),
                'time' => BL::getLabel('Time'),
            )
        );
        $this->frm->addDropdown(
            'datetime_validation',
            array(
                '' => '',
                'time' => BL::getLabel('Time'),
            )
        );
        $this->frm->addText('datetime_classname');
        $this->frm->addText('datetime_error_message');

        // dropdown dialog
        $this->frm->addText('dropdown_label');
        $this->frm->addText('dropdown_values');
        $this->frm->addDropdown('dropdown_default_value', array('' => ''))->setAttribute('rel', 'dropDownValues');
        $this->frm->addCheckbox('dropdown_required');
        $this->frm->addText('dropdown_required_error_message');
        $this->frm->addText('dropdown_classname');

        // radiobutton dialog
        $this->frm->addText('radiobutton_label');
        $this->frm->addText('radiobutton_values');
        $this->frm->addDropdown('radiobutton_default_value', array('' => ''))->setAttribute('rel', 'radioButtonValues');
        $this->frm->addCheckbox('radiobutton_required');
        $this->frm->addText('radiobutton_required_error_message');
        $this->frm->addText('radiobutton_classname');

        // checkbox dialog
        $this->frm->addText('checkbox_label');
        $this->frm->addText('checkbox_values');
        $this->frm->addDropdown('checkbox_default_value', array('' => ''))->setAttribute('rel', 'checkBoxValues');
        $this->frm->addCheckbox('checkbox_required');
        $this->frm->addText('checkbox_required_error_message');
        $this->frm->addText('checkbox_classname');

        // heading dialog
        $this->frm->addText('heading');

        // paragraph dialog
        $this->frm->addEditor('paragraph');
        $this->frm->getField('paragraph')->setAttribute('cols', 30);

        // submit dialog
        $this->frm->addText('submit');
    }

    /**
     * Parse the form
     */
    protected function parse()
    {
        $this->parseFields();

        parent::parse();

        $this->tpl->assign('id', $this->record['id']);
        $this->tpl->assign('name', $this->record['name']);

        // parse error messages
        $this->parseErrorMessages();
    }

    /**
     * Parse the default error messages
     */
    private function parseErrorMessages()
    {
        // set frontend locale
        FL::setLocale(BL::getWorkingLanguage(), true);

        // assign error messages
        $this->tpl->assign('errors', BackendFormBuilderModel::getErrors());
    }

    /**
     * Parse the fields
     */
    private function parseFields()
    {
        $fieldsHTML = array();

        // get fields
        $fields = BackendFormBuilderModel::getFields($this->id);

        // loop fields
        foreach ($fields as $field) {
            // submit button
            if ($field['type'] == 'submit') {
                // assign
                $this->tpl->assign('submitId', $field['id']);

                // add field
                $btn = $this->frm->addButton(
                    'submit_field',
                    \SpoonFilter::htmlspecialcharsDecode($field['settings']['values']),
                    'button',
                    'btn btn-default'
                );
                $btn->setAttribute('disabled', 'disabled');

                // skip
                continue;
            }

            // parse field to html
            $fieldsHTML[]['field'] = FormBuilderHelper::parseField($field);
        }

        // assign iteration
        $this->tpl->assign('fields', $fieldsHTML);
    }

    /**
     * Validate the form
     */
    private function validateForm()
    {
        if ($this->frm->isSubmitted()) {
            $this->frm->cleanupFields();

            // shorten the fields
            $txtName = $this->frm->getField('name');
            $txtEmail = $this->frm->getField('email');
            $ddmMethod = $this->frm->getField('method');
            $txtSuccessMessage = $this->frm->getField('success_message');
            $txtIdentifier = $this->frm->getField('identifier');

            $emailAddresses = (array) explode(',', $txtEmail->getValue());

            // validate fields
            $txtName->isFilled(BL::getError('NameIsRequired'));
            $txtSuccessMessage->isFilled(BL::getError('SuccessMessageIsRequired'));
            if ($ddmMethod->isFilled(BL::getError('NameIsRequired')) && $ddmMethod->getValue() == 'database_email') {
                $error = false;

                // check the addresses
                foreach ($emailAddresses as $address) {
                    $address = trim($address);

                    if (!\SpoonFilter::isEmail($address)) {
                        $error = true;
                        break;
                    }
                }

                // add error
                if ($error) {
                    $txtEmail->addError(BL::getError('EmailIsInvalid'));
                }
            }

            // identifier
            if ($txtIdentifier->isFilled()) {
                // invalid characters
                if (!\SpoonFilter::isValidAgainstRegexp('/^[a-zA-Z0-9\.\_\-]+$/', $txtIdentifier->getValue())) {
                    $txtIdentifier->setError(BL::getError('InvalidIdentifier'));
                } elseif (BackendFormBuilderModel::existsIdentifier($txtIdentifier->getValue(), $this->id)) {
                    $txtIdentifier->setError(BL::getError('UniqueIdentifier'));
                }
            }

            if ($this->frm->isCorrect()) {
                // build array
                $values['name'] = $txtName->getValue();
                $values['method'] = $ddmMethod->getValue();
                $values['email'] = ($ddmMethod->getValue() == 'database_email') ? serialize($emailAddresses) : null;
                $values['success_message'] = $txtSuccessMessage->getValue(true);
                $values['identifier'] = ($txtIdentifier->isFilled() ?
                    $txtIdentifier->getValue() :
                    BackendFormBuilderModel::createIdentifier()
                );
                $values['edited_on'] = BackendModel::getUTCDate();

                // insert the item
                $id = (int) BackendFormBuilderModel::update($this->id, $values);

                // trigger event
                BackendModel::triggerEvent($this->getModule(), 'after_edit', array('item' => $values));

                // everything is saved, so redirect to the overview
                $this->redirect(
                    BackendModel::createURLForAction('Index') . '&report=edited&var=' .
                    rawurlencode($values['name']) . '&highlight=row-' . $id
                );
            }
        }
    }
}
