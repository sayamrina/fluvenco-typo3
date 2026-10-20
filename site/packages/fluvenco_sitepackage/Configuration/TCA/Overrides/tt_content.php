<?php

defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

// Hero: large intro with headline, text, one image and a call to action
ExtensionManagementUtility::addRecordType(
    [
        'label' => 'Hero (Bühne)',
        'description' => 'Große Einstiegsfläche mit Überschrift, Text, Bild und Button',
        'value' => 'fluvenco_hero',
        'icon' => 'content-header',
        'group' => 'default',
    ],
    '--palette--;;general, header, subheader, bodytext, header_link,
    --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.images, image,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language, --palette--;;language,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, --palette--;;hidden',
    ['columnsOverrides' => ['bodytext' => ['config' => ['enableRichtext' => false, 'rows' => 3]]]]
);

// Teaser cards: one card per subpage of the selected page (title, abstract, page image)
ExtensionManagementUtility::addRecordType(
    [
        'label' => 'Teaser-Karten (Unterseiten)',
        'description' => 'Zeigt die Unterseiten einer Seite als Karten mit Bild und Kurztext',
        'value' => 'fluvenco_cards',
        'icon' => 'content-menu-pages',
        'group' => 'default',
    ],
    '--palette--;;general, header, subheader, pages,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language, --palette--;;language,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, --palette--;;hidden',
    ['columnsOverrides' => ['pages' => ['config' => ['maxitems' => 1, 'minitems' => 1]]]]
);
