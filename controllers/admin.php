<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2011, Oxwall Foundation
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.newsfeed.controllers
 * @since 1.0
 */
class NEWSFEED_CTRL_Admin extends ADMIN_CTRL_Abstract
{

    /**
     * Default action
     */
    public function index()
    {
        $language = OW::getLanguage();

        $this->setPageHeading($language->text('newsfeed', 'admin_page_heading'));
        $this->setPageTitle($language->text('newsfeed', 'admin_page_title'));
        $this->setPageHeadingIconClass('ow_ic_comment');

        $configs = OW::getConfig()->getValues('newsfeed');
        $this->assign('configs', $configs);

        $form = new NEWSFEED_ConfigSaveForm($configs);

        $this->addForm($form);

        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            if ( $form->process($_POST) )
            {
                OW::getFeedback()->info($language->text('newsfeed', 'settings_updated'));
                $this->redirect(OW::getRouter()->urlForRoute('newsfeed_admin_settings'));
            }
        }

        $this->addComponent('menu', $this->getMenu());
    }

    public function customization()
    {
        $language = OW::getLanguage();

        $this->setPageHeading($language->text('newsfeed', 'admin_page_heading'));
        $this->setPageTitle($language->text('newsfeed', 'admin_page_title'));
        $this->setPageHeadingIconClass('ow_ic_comment');

        $types = NEWSFEED_BOL_CustomizationService::getInstance()->getActionTypes();

        $form = new NEWSFEED_CustomizationForm();
        $this->addForm($form);

        $processTypes = array();

        foreach ( $types as $type )
        {
            $field = new CheckboxField($type['activity']);
            $field->setValue($type['active']);
            $form->addElement($field);

            $processTypes[] = $type['activity'];
        }

        if ( OW::getRequest()->isPost() )
        {
            $result = $form->process($_POST, $processTypes);
            if ( $result )
            {
                OW::getFeedback()->info($language->text('newsfeed', 'customization_changed'));
            }
            else
            {
                OW::getFeedback()->warning($language->text('newsfeed', 'customization_not_changed'));
            }

            $this->redirect();
        }

        $this->assign('types', $types);
        $this->addComponent('menu', $this->getMenu());
    }

    private function getMenu()
    {
        $language = OW::getLanguage();

        $menuItems = array();

        $item = new BASE_MenuItem();
        $item->setLabel($language->text('newsfeed', 'admin_menu_item_settings'));
        $item->setUrl(OW::getRouter()->urlForRoute('newsfeed_admin_settings'));
        $item->setKey('newsfeed_settings');
        $item->setIconClass('ow_ic_gear_wheel');
        $item->setOrder(0);

        $menuItems[] = $item;

        $item = new BASE_MenuItem();
        $item->setLabel($language->text('newsfeed', 'admin_menu_item_customization'));
        $item->setUrl(OW::getRouter()->urlForRoute('newsfeed_admin_customization'));
        $item->setKey('newsfeed_customization');
        $item->setIconClass('ow_ic_files');
        $item->setOrder(1);

        $menuItems[] = $item;

        return new BASE_CMP_ContentMenu($menuItems);
    }
}

/**
 * Save photo configuration form class
 */
class NEWSFEED_ConfigSaveForm extends Form
{

    /**
     * Class constructor
     *
     */
    public function __construct( $configs )
    {
        parent::__construct('NEWSFEED_ConfigSaveForm');

        $language = OW::getLanguage();

        $field = new CheckboxField('allow_comments');
        $field->setLabel($language->text('newsfeed', 'admin_allow_comments_label'));
        $field->setValue($configs['allow_comments']);
        $this->addElement($field);

        $field = new CheckboxField('features_expanded');
        $field->setLabel($language->text('newsfeed', 'admin_features_expanded_label'));
        $field->setValue($configs['features_expanded']);
        $this->addElement($field);

        $field = new CheckboxField('index_status_enabled');
        $field->setLabel($language->text('newsfeed', 'admin_index_status_label'));
        $field->setValue($configs['index_status_enabled']);
        $this->addElement($field);

        $field = new CheckboxField('allow_likes');
        $field->setLabel($language->text('newsfeed', 'admin_allow_likes_label'));
        $field->setValue($configs['allow_likes']);
        $this->addElement($field);

        $field = new TextField('comments_count');
        $field->setValue($configs['comments_count']);
        $field->setRequired(true);
        $validator = new IntValidator();
        $field->addValidator($validator);
        $field->setLabel($language->text('newsfeed', 'admin_comments_count_label'));
        $this->addElement($field);

        // submit
        $submit = new Submit('save');
        $submit->setValue($language->text('newsfeed', 'admin_save_btn'));
        $this->addElement($submit);
    }

    /**
     * Updates photo plugin configuration
     *
     * @return boolean
     */
    public function process( $data )
    {
        $config = OW::getConfig();

        $config->saveConfig('newsfeed', 'allow_likes', $data['allow_likes']);
        $config->saveConfig('newsfeed', 'allow_comments', $data['allow_comments']);
        $config->saveConfig('newsfeed', 'comments_count', $data['comments_count']);
        $config->saveConfig('newsfeed', 'features_expanded', $data['features_expanded']);
        $config->saveConfig('newsfeed', 'index_status_enabled', $data['index_status_enabled']);

        return true;
    }
}

class NEWSFEED_CustomizationForm extends Form
{

    public function __construct(  )
    {
        parent::__construct('NEWSFEED_CustomizationForm');

        $language = OW::getLanguage();

        $btn = new Submit('save');
        $btn->setValue($language->text('newsfeed', 'save_customization_btn_label'));
        $this->addElement($btn);
    }

    public function process( $data, $types )
    {
        $changed = false;
        $configValue = json_decode(OW::getConfig()->getValue('newsfeed', 'disabled_action_types'), true);
        $typesToSave = array();

        foreach ( $types as $type )
        {
            $typesToSave[$type] = isset($data[$type]);
            if ( !isset($configValue[$type]) || $configValue[$type] !== $typesToSave[$type] )
            {
                $changed = true;
            }
        }

        $jsonValue = json_encode($typesToSave);
        OW::getConfig()->saveConfig('newsfeed', 'disabled_action_types', $jsonValue);

        return $changed;
    }
}