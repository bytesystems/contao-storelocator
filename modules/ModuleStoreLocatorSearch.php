<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2019 Leo Feyer
 *
 * @package   StoreLocator
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL
 * @copyright 2019 numero2 - Agentur für digitales Marketing
 */


/**
 * Namespace
 */
namespace numero2\StoreLocator;


class ModuleStoreLocatorSearch extends \Module {


    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_storelocator_search';


    /**
     * Display a wildcard in the back end
     * @return string
     */
    public function generate() {

        if( TL_MODE == 'BE' ) {

            $objTemplate = new \BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### STORELOCATOR SEARCH ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }

        return parent::generate();
    }


    /**
     * Generate module
     */
    protected function compile() {

        global $objPage;

        $this->Template = new \FrontendTemplate($this->storelocator_search_tpl);

        $this->Template->formId = 'storelocator_search_'.$this->id;
        $this->Template->action = \Environment::get('request');

        if( !isset($_GET['search']) && \Config::get('useAutoItem') && isset($_GET['auto_item']) ) {
            \Input::setGet('search', \Input::get('auto_item'));
        }

        $sSearchVal = $this->Input->get('search') ? $this->Input->get('search') : NULL;

        $aSearchValues = StoreLocator::parseSearchValue($sSearchVal);

        // generate form elements
        $widgetSearch = NULL;
        $widgetSearch = new \FormTextField(\FormTextField::getAttributesFromDca(
                array(
                    'name'      => 'location'
                ,   'label'     => &$GLOBALS['TL_LANG']['tl_storelocator']['field']['postal']
                ,   'inputType' => 'text'
                ,    'eval'        => array( 'mandatory'=>true )
                )
            ,   'location'
            ,   $aSearchValues['term']
            )
        );

        $widgetCategories = NULL;
        $aAvailableCategories = deserialize($this->storelocator_search_categories);

        if( count($aAvailableCategories) > 1 ) {

            $aCategories = array(
                'all' => $GLOBALS['TL_LANG']['tl_storelocator']['field']['all_categories']
            );

            $oCategories = NULL;
            $oCategories = CategoriesModel::findMultipleByIds($aAvailableCategories);

            while( $oCategories->next() ) {
                $aCategories[ $oCategories->alias ] = $oCategories->title;
            }

            $widgetCategories = new \FormRadioButton(\FormRadioButton::getAttributesFromDca(
                    array(
                        'name'      => 'category'
                    ,   'inputType' => 'radio'
                    ,    'eval'        => array( 'mandatory'=>false )
                    ,   'options'   => $aCategories
                    )
                ,   'category'
                ,   ($aSearchValues['category']?$aSearchValues['category']:'all')
                )
            );
        }

        $widgetSubmit = NULL;
        $widgetSubmit = new \FormSubmit();
        $widgetSubmit->id = 'search';
        $widgetSubmit->label = $GLOBALS['TL_LANG']['tl_storelocator']['field']['search'];

        // redirect to listing page
        if( \Input::post('FORM_SUBMIT') == $this->Template->formId ) {

            $widgetSearch->validate();
            $term = $widgetSearch->value;

            if( !empty($term) ) {

                $aSearchValues['term'] = $term;

                if( $widgetCategories ) {

                    $widgetCategories->validate();

                    if( $widgetCategories->value && $widgetCategories->value != 'all' ) {
                        $aSearchValues['category'] = $widgetCategories->value;
                    }
                }

                $aSearchValues['longitude'] = \Input::post('longitude');
                $aSearchValues['latitude'] = \Input::post('latitude');

                $strData = StoreLocator::generateSearchvalue($aSearchValues);

                $objListPage = $this->jumpTo ? \PageModel::findWithDetails($this->jumpTo) : $objPage;
                $href = $objListPage->getFrontendUrl((\Config::get('useAutoItem') && !\Config::get('disableAlias')) ? '/'.$strData : '/search/'.$strData);

                $this->redirect( $href );
            }
        }

        // add autocomplete script
        if( $this->storelocator_enable_autocomplete ) {

            $oTemplateAutocomplete = new \FrontendTemplate('script_storelocator_autocomplete');
            $oTemplateAutocomplete->mapsKey = \Config::get('google_maps_browser_key');
            $oTemplateAutocomplete->country = $this->storelocator_autocomplete_country;
            $oTemplateAutocomplete->fieldId = 'ctrl_'.$widgetSearch->id;

            $this->Template->autoComplete = $oTemplateAutocomplete->parse();
            $objPage->loadedMapsApi = true;

        }

        $this->Template->searchField = $widgetSearch;
        $this->Template->categories = $widgetCategories;
        $this->Template->submitButton = $widgetSubmit;
    }
}
