<?php

/**
 * Inheritance: no
 * Variants: no
 *
 * Fields Summary:
 * - cdpId [input]
 * - customerName [input]
 * - customerType [select]
 * - email [input]
 * - phone [input]
 * - primarySource [select]
 * - status [select]
 * - lifetimeValue [numeric]
 * - lastUpdated [input]
 */

return \Pimcore\Model\DataObject\ClassDefinition::__set_state(array(
   'dao' => NULL,
   'id' => '1',
   'name' => 'Customer',
   'title' => '',
   'description' => '',
   'creationDate' => NULL,
   'modificationDate' => 1781440901,
   'userOwner' => 1,
   'userModification' => 1,
   'parentClass' => '',
   'implementsInterfaces' => '',
   'listingParentClass' => '',
   'useTraits' => '',
   'listingUseTraits' => '',
   'encryption' => false,
   'encryptedTables' => 
  array (
  ),
   'allowInherit' => false,
   'allowVariants' => false,
   'showVariants' => false,
   'layoutDefinitions' => 
  \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel::__set_state(array(
     'name' => 'pimcore_root',
     'type' => NULL,
     'region' => NULL,
     'title' => NULL,
     'width' => 0,
     'height' => 0,
     'collapsible' => false,
     'collapsed' => false,
     'bodyStyle' => NULL,
     'datatype' => 'layout',
     'children' => 
    array (
      0 => 
      \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel::__set_state(array(
         'name' => 'Layout',
         'type' => NULL,
         'region' => '',
         'title' => '',
         'width' => 0,
         'height' => 0,
         'collapsible' => false,
         'collapsed' => false,
         'bodyStyle' => NULL,
         'datatype' => 'layout',
         'children' => 
        array (
          0 => 
          \Pimcore\Model\DataObject\ClassDefinition\Data\Input::__set_state(array(
             'name' => 'cdpId',
             'title' => 'Cdp Id',
             'tooltip' => '',
             'mandatory' => false,
             'noteditable' => false,
             'index' => false,
             'locked' => false,
             'style' => NULL,
             'permissions' => NULL,
             'fieldtype' => '',
             'relationType' => false,
             'invisible' => false,
             'visibleGridView' => true,
             'visibleSearch' => true,
             'blockedVarsForExport' => 
            array (
            ),
             'defaultValue' => NULL,
             'columnLength' => 190,
             'regex' => '',
             'regexFlags' => 
            array (
            ),
             'unique' => false,
             'showCharCount' => false,
             'width' => NULL,
             'defaultValueGenerator' => '',
          )),
          1 => 
          \Pimcore\Model\DataObject\ClassDefinition\Data\Input::__set_state(array(
             'name' => 'customerName',
             'title' => 'CustomerName',
             'tooltip' => '',
             'mandatory' => false,
             'noteditable' => false,
             'index' => false,
             'locked' => false,
             'style' => NULL,
             'permissions' => NULL,
             'fieldtype' => '',
             'relationType' => false,
             'invisible' => false,
             'visibleGridView' => true,
             'visibleSearch' => true,
             'blockedVarsForExport' => 
            array (
            ),
             'defaultValue' => NULL,
             'columnLength' => 190,
             'regex' => '',
             'regexFlags' => 
            array (
            ),
             'unique' => false,
             'showCharCount' => false,
             'width' => NULL,
             'defaultValueGenerator' => '',
          )),
          2 => 
          \Pimcore\Model\DataObject\ClassDefinition\Data\Select::__set_state(array(
             'name' => 'customerType',
             'title' => 'Customer Type',
             'tooltip' => '',
             'mandatory' => false,
             'noteditable' => false,
             'index' => false,
             'locked' => false,
             'style' => NULL,
             'permissions' => NULL,
             'fieldtype' => '',
             'relationType' => false,
             'invisible' => false,
             'visibleGridView' => true,
             'visibleSearch' => true,
             'blockedVarsForExport' => 
            array (
            ),
             'options' => 
            array (
              0 => 
              array (
                'key' => 'B2C Customer',
                'value' => 'B2C Customer',
              ),
              1 => 
              array (
                'key' => 'B2B Customer',
                'value' => 'B2B Customer',
              ),
              2 => 
              array (
                'key' => 'Dealer',
                'value' => 'Dealer',
              ),
              3 => 
              array (
                'key' => 'Employee',
                'value' => 'Employee',
              ),
            ),
             'defaultValue' => NULL,
             'columnLength' => 190,
             'dynamicOptions' => false,
             'enforceValidation' => false,
             'defaultValueGenerator' => '',
             'width' => NULL,
             'optionsProviderType' => 'configure',
             'optionsProviderClass' => NULL,
             'optionsProviderData' => NULL,
          )),
          3 => 
          \Pimcore\Model\DataObject\ClassDefinition\Data\Input::__set_state(array(
             'name' => 'email',
             'title' => 'Email',
             'tooltip' => '',
             'mandatory' => false,
             'noteditable' => false,
             'index' => false,
             'locked' => false,
             'style' => NULL,
             'permissions' => NULL,
             'fieldtype' => '',
             'relationType' => false,
             'invisible' => false,
             'visibleGridView' => true,
             'visibleSearch' => true,
             'blockedVarsForExport' => 
            array (
            ),
             'defaultValue' => NULL,
             'columnLength' => 190,
             'regex' => '',
             'regexFlags' => 
            array (
            ),
             'unique' => false,
             'showCharCount' => false,
             'width' => NULL,
             'defaultValueGenerator' => '',
          )),
          4 => 
          \Pimcore\Model\DataObject\ClassDefinition\Data\Input::__set_state(array(
             'name' => 'phone',
             'title' => 'Phone',
             'tooltip' => '',
             'mandatory' => false,
             'noteditable' => false,
             'index' => false,
             'locked' => false,
             'style' => NULL,
             'permissions' => NULL,
             'fieldtype' => '',
             'relationType' => false,
             'invisible' => false,
             'visibleGridView' => true,
             'visibleSearch' => true,
             'blockedVarsForExport' => 
            array (
            ),
             'defaultValue' => NULL,
             'columnLength' => 190,
             'regex' => '',
             'regexFlags' => 
            array (
            ),
             'unique' => false,
             'showCharCount' => false,
             'width' => NULL,
             'defaultValueGenerator' => '',
          )),
          5 => 
          \Pimcore\Model\DataObject\ClassDefinition\Data\Select::__set_state(array(
             'name' => 'primarySource',
             'title' => 'Primary Source',
             'tooltip' => '',
             'mandatory' => false,
             'noteditable' => false,
             'index' => false,
             'locked' => false,
             'style' => NULL,
             'permissions' => NULL,
             'fieldtype' => '',
             'relationType' => false,
             'invisible' => false,
             'visibleGridView' => true,
             'visibleSearch' => true,
             'blockedVarsForExport' => 
            array (
            ),
             'options' => 
            array (
              0 => 
              array (
                'key' => 'Commerce Cloud',
                'value' => 'Commerce Cloud',
              ),
              1 => 
              array (
                'key' => 'CRM',
                'value' => 'CRM',
              ),
              2 => 
              array (
                'key' => 'Manual',
                'value' => 'Manual',
              ),
            ),
             'defaultValue' => NULL,
             'columnLength' => 190,
             'dynamicOptions' => false,
             'enforceValidation' => false,
             'defaultValueGenerator' => '',
             'width' => NULL,
             'optionsProviderType' => 'configure',
             'optionsProviderClass' => NULL,
             'optionsProviderData' => NULL,
          )),
          6 => 
          \Pimcore\Model\DataObject\ClassDefinition\Data\Select::__set_state(array(
             'name' => 'status',
             'title' => 'Status',
             'tooltip' => '',
             'mandatory' => false,
             'noteditable' => false,
             'index' => false,
             'locked' => false,
             'style' => NULL,
             'permissions' => NULL,
             'fieldtype' => '',
             'relationType' => false,
             'invisible' => false,
             'visibleGridView' => true,
             'visibleSearch' => true,
             'blockedVarsForExport' => 
            array (
            ),
             'options' => 
            array (
              0 => 
              array (
                'key' => 'Active',
                'value' => 'Active',
              ),
              1 => 
              array (
                'key' => 'Inactive',
                'value' => 'Inactive',
              ),
              2 => 
              array (
                'key' => 'Pending',
                'value' => '',
              ),
            ),
             'defaultValue' => NULL,
             'columnLength' => 190,
             'dynamicOptions' => false,
             'enforceValidation' => false,
             'defaultValueGenerator' => '',
             'width' => NULL,
             'optionsProviderType' => 'configure',
             'optionsProviderClass' => NULL,
             'optionsProviderData' => NULL,
          )),
          7 => 
          \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric::__set_state(array(
             'name' => 'lifetimeValue',
             'title' => 'Lifetime Value',
             'tooltip' => '',
             'mandatory' => false,
             'noteditable' => false,
             'index' => false,
             'locked' => false,
             'style' => NULL,
             'permissions' => NULL,
             'fieldtype' => '',
             'relationType' => false,
             'invisible' => false,
             'visibleGridView' => true,
             'visibleSearch' => true,
             'blockedVarsForExport' => 
            array (
            ),
             'defaultValue' => NULL,
             'integer' => false,
             'unsigned' => false,
             'minValue' => NULL,
             'maxValue' => NULL,
             'unique' => false,
             'decimalSize' => NULL,
             'decimalPrecision' => NULL,
             'width' => NULL,
             'defaultValueGenerator' => '',
          )),
          8 => 
          \Pimcore\Model\DataObject\ClassDefinition\Data\Input::__set_state(array(
             'name' => 'lastUpdated',
             'title' => 'Last Updated',
             'tooltip' => '',
             'mandatory' => false,
             'noteditable' => false,
             'index' => false,
             'locked' => false,
             'style' => NULL,
             'permissions' => NULL,
             'fieldtype' => '',
             'relationType' => false,
             'invisible' => false,
             'visibleGridView' => true,
             'visibleSearch' => true,
             'blockedVarsForExport' => 
            array (
            ),
             'defaultValue' => NULL,
             'columnLength' => 190,
             'regex' => '',
             'regexFlags' => 
            array (
            ),
             'unique' => false,
             'showCharCount' => false,
             'width' => NULL,
             'defaultValueGenerator' => '',
          )),
        ),
         'locked' => false,
         'blockedVarsForExport' => 
        array (
        ),
         'fieldtype' => 'panel',
         'layout' => NULL,
         'border' => false,
         'icon' => NULL,
         'labelWidth' => 100,
         'labelAlign' => 'left',
      )),
    ),
     'locked' => false,
     'blockedVarsForExport' => 
    array (
    ),
     'fieldtype' => 'panel',
     'layout' => NULL,
     'border' => false,
     'icon' => NULL,
     'labelWidth' => 100,
     'labelAlign' => 'left',
  )),
   'icon' => 'class',
   'group' => NULL,
   'showAppLoggerTab' => false,
   'linkGeneratorReference' => '',
   'previewGeneratorReference' => '',
   'compositeIndices' => 
  array (
  ),
   'showFieldLookup' => false,
   'propertyVisibility' => 
  array (
    'grid' => 
    array (
      'id' => true,
      'path' => true,
      'published' => true,
      'modificationDate' => true,
      'creationDate' => true,
    ),
    'search' => 
    array (
      'id' => true,
      'path' => true,
      'published' => true,
      'modificationDate' => true,
      'creationDate' => true,
    ),
  ),
   'enableGridLocking' => false,
   'deletedDataComponents' => 
  array (
  ),
   'blockedVarsForExport' => 
  array (
  ),
   'fieldDefinitionsCache' => 
  array (
  ),
   'activeDispatchingEvents' => 
  array (
  ),
));
