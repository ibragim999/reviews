<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$menu = array(
    array(
        'parent_menu' => 'global_menu_services',
        'sort' => 400,
        'text' => Loc::getMessage('OPENSOURCE_REVIEWS_MENU_TITLE'),
        'title' => Loc::getMessage('OPENSOURCE_REVIEWS_MENU_TITLE'),
        'url' => 'opensource_reviews.php',
        'items_id' => 'menu_references',
        'items' => array(
            array(
                'text' => Loc::getMessage('OPENSOURCE_REVIEWS_SUBMENU_REVIEWS'),
                'url' => 'opensource_reviews.php?&lang=' . LANGUAGE_ID,
                'more_url' => array('opensource_reviews.php?lang=' . LANGUAGE_ID),
                'title' => Loc::getMessage('OPENSOURCE_REVIEWS_SUBMENU_REVIEWS'),
            ),
        ),
    ),
);

return $menu;
