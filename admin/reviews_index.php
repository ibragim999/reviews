<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Opensource\Reviews\ReviewLogTable;
use Opensource\Reviews\ReviewTable;

require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php';

Loader::includeModule('opensource.reviews');
IncludeModuleLangFile(__FILE__);

/** @global CAdminPage $adminPage */
global $adminPage;
/** @global CAdminSidePanelHelper $adminSidePanelHelper */
global $adminSidePanelHelper;

$selfFolderUrl = $adminPage->getSelfFolderUrl();

$sTableID = "report_reviews";
$oSort = new CAdminUiSorting($sTableID, "ID", "desc");
$lAdmin = new CAdminUiList($sTableID, $oSort);



$filterFields = array(
    array(
        "id" => "P_NAME",
        "name" => GetMessage("OPENSOURCE_REVIEWS_ELEMENT"),
        "filterable" => "",
        "quickSearch" => "?",
        "default" => true
    ),
    array(
        "id" => "U_NAME",
        "name" => GetMessage("OPENSOURCE_REVIEWS_USER"),
        "filterable" => "",
        "quickSearch" => "?",
        "default" => true
    ),
    array(
        "id" => "PUBLISHED",
        "name" => GetMessage("OPENSOURCE_REVIEWS_PUBLISHED"),
        "type" => "list",
        "items" => array(
            "Y" => GetMessage("OPENSOURCE_REVIEWS_YES"),
            "N" => GetMessage("OPENSOURCE_REVIEWS_NO")
        ),
        "filterable" => "="
    ),
    array(
        "id" => "DELETED",
        "name" => GetMessage("OPENSOURCE_REVIEWS_DELETED"),
        "type" => "list",
        "items" => array(
            "Y" => GetMessage("OPENSOURCE_REVIEWS_YES"),
            "N" => GetMessage("OPENSOURCE_REVIEWS_NO")
        ),
        "filterable" => "="
    ),
);

$arFilter = [];

$lAdmin->AddFilter($filterFields, $arFilter);

foreach($arFilter as $key => $value) {
    if (trim($value) == '') {
        unset($arFilter[$key]);
    }
}


if($lAdmin->EditAction())
{
    /**  @var array $FIELDS */
    foreach($FIELDS as $ID => $postFields)
    {
        $DB->StartTransaction();
        $ID = intval($ID);

        if(!$lAdmin->IsUpdated($ID))
            continue;

        $allowedFields = array(
            "RATING",
            "PUBLISHED",
            "DELETED",
        );

        $arFields = array();
        foreach ($allowedFields as $fieldId)
        {
            if (array_key_exists($fieldId, $postFields))
                $arFields[$fieldId] = $postFields[$fieldId];
        }

        /** @var ReviewTable $review */
        $review = new ReviewTable();
        /** @var Bitrix\Main\Entity\UpdateResult $updateResult */
        $updateResult = $review->update($ID, $arFields);
        if(!$updateResult->isSuccess()) {
            $lAdmin->AddUpdateError(GetMessage("OPENSOURCE_REVIEWS_SAVE_ERROR", array(
                "#ID#"=>$ID,
                "#ERROR_TEXT#" => implode(", ", $updateResult->getErrorMessages()))), $ID
            );
            $DB->Rollback();
        }
        $DB->Commit();
    }
}

if($arID = $lAdmin->GroupAction())
{
    if ($lAdmin->IsGroupActionToAll())
    {
        $propertyIterator = ReviewTable::getList(array(
            'select' => array('ID'),
            'filter' => $arFilter
        ));
        while ($property = $propertyIterator->fetch())
            $arID[] = $property['ID'];
        unset($property, $propertyIterator);
    }

    foreach($arID as $ID)
    {
        if($ID == '')
            continue;

        switch($_REQUEST['action'])
        {
            case "remove":
                if(!ReviewTable::delete($ID)->isSuccess())
                    $lAdmin->AddGroupError(GetMessage("OPENSOURCE_REVIEWS_DELETE_ERROR"), $ID);
                break;
            case "publish":
            case "unpublish":
                $review = new ReviewTable();
                $reviewObject = ReviewTable::getList(['filter' => ['ID' => $ID]])->fetch();
                $arFields = array(
                    "PUBLISHED" => ($_REQUEST['action']=="publish"? "Y": "N"),
                );

                $updateResult = $review->Update($ID, $arFields);
                if(!$updateResult->isSuccess()) {
                    $lAdmin->AddGroupError(
                        GetMessage("OPENSOURCE_REVIEWS_SAVE_ERROR",
                            array("#ID#" => $ID, "#ERROR_TEXT#" => implode(', ', $updateResult->getErrorMessages())), $ID)
                    );
                } else {
                    if($reviewObject['PUBLISHED'] === 'N' && $arFields['PUBLISHED'] === 'Y' && $reviewObject['DELETED'] === 'N'){
                        if(Option::get('opensource.reviews', "reward_type", 'M') === 'E'){
                            $price = Option::get('opensource.reviews', 'price', 1);
                            \Opensource\Reviews\Account::change($USER->GetID(), $price);

                            ReviewLogTable::add([
                                'REVIEW_ID' => $ID,
                                'PRICE' => $price,
                            ]);
                        }
                    }
                }

                break;
            case "delete":
            case "undelete":
                $review = new ReviewTable();
                $arFields = array(
                    "DELETED" => ($_REQUEST['action']=="delete"? "Y": "N"),
                );
                $updateResult = $review->Update($ID, $arFields);
                if(!$updateResult->isSuccess())
                    $lAdmin->AddGroupError(
                        GetMessage("OPENSOURCE_REVIEWS_SAVE_ERROR",
                        array("#ID#"=>$ID, "#ERROR_TEXT#"=> implode(', ', $updateResult->getErrorMessages())), $ID)
                    );
                break;
        }
    }

    if ($lAdmin->hasGroupErrors())
    {
        $adminSidePanelHelper->sendJsonErrorResponse($lAdmin->getGroupErrors());
    }
    else
    {
        $adminSidePanelHelper->sendSuccessResponse();
    }
}

$getListParams = [
    'filter' => $arFilter,
    'order' => [$by=>$order],
    'select' => [
        '*',
        'U_' => 'USER.*',
        'P_' => 'ELEMENT.*',
    ]
];

$propertyIterator = new CAdminUiResult(ReviewTable::getList($getListParams), $sTableID);
$propertyIterator->NavStart();

$lAdmin->SetNavigationParams($propertyIterator, array("BASE_LINK" => $selfFolderUrl."opensource_reviews.php"));

$lAdmin->AddHeaders(array(
    array("id"=>"ID", "content"=>GetMessage('OPENSOURCE_REVIEWS_ID'), "sort"=>"ID", "default"=>true),
    array("id"=>"USER", "content"=>GetMessage('OPENSOURCE_REVIEWS_USER'), "sort"=>"U_NAME", "default"=>true),
    array("id"=>"ELEMENT_NAME", "content"=>GetMessage('OPENSOURCE_REVIEWS_ELEMENT'), "sort"=>"P_NAME", "default"=>true),
    array("id"=>"RATING", "content"=>GetMessage('OPENSOURCE_REVIEWS_RATING'), "sort"=>"RATING", "default"=>true),
    array("id"=>"PUBLISHED", "content"=>GetMessage('OPENSOURCE_REVIEWS_PUBLISHED'), "sort"=>"PUBLISHED", "default"=>true),
    array("id"=>"DELETED", "content"=>GetMessage('OPENSOURCE_REVIEWS_DELETED'), "sort"=>"DELETED", "default"=>true),
    array("id"=>"CREATED_AT", "content"=>GetMessage('OPENSOURCE_REVIEWS_CREATED_AT'), "sort"=>"CREATED_AT", "default"=>true),
));

while ($property = $propertyIterator->Fetch()) {
    $row = &$lAdmin->AddRow($property['ID'], $property);

    $urlEdit = $selfFolderUrl.'opensource_reviews_edit.php?ID='.$property['ID'].'&lang='.LANGUAGE_ID.($_REQUEST['admin']=="Y"? "&admin=Y": "&admin=N");
    $urlEdit = $adminSidePanelHelper->editUrlToPublicPage($urlEdit);
    $pubAction = $property['PUBLISHED'] != 'Y' ? 'publish' : 'unpublish';
    $delAction = $property['DELETED'] != 'Y' ? 'delete' : 'undelete';
    $urlReward = $selfFolderUrl.'opensource_reviews_users.php?ID='.$property['ID'].'&USER_ID='.$property['USER_ID'].'&lang='.LANGUAGE_ID.($_REQUEST['admin']=="Y"? "&admin=Y": "&admin=N");
    $urlReward = $adminSidePanelHelper->editUrlToPublicPage($urlReward);

    $row->AddViewField('ID', $property['ID']);
    $row->AddViewField("USER", $property['U_NAME']." ".$property['U_LAST_NAME']." ".$property['U_EMAIL']);
    $row->AddViewField("ELEMENT_NAME", $property['P_NAME']);
    $row->AddInputField("RATING", $property['RATING']);
    $row->AddCheckField("PUBLISHED", $property['PUBLISHED'] == 'Y' ? 'Да' : 'Нет');
    $row->AddCheckField("DELETED", $property['DELETED'] == 'Y' ? 'Да' : 'Нет');
    $row->AddViewField("CREATED_AT", $property['CREATED_AT']);



    $arActions = array(
        array(
            'ICON' => 'edit',
            'TEXT' => $property['PUBLISHED'] != 'Y' ? GetMessage('OPENSOURCE_REVIEWS_PUBLISH') : GetMessage('OPENSOURCE_REVIEWS_UNPUBLISH'),
            'DEFAULT' => true,
            'ACTION' => $lAdmin->ActionDoGroup($property['ID'], $pubAction, "&lang=".LANGUAGE_ID),
        ),
        array(
            'ICON' => 'edit',
            'TEXT' => GetMessage('MAIN_ADMIN_MENU_EDIT'),
            'DEFAULT' => true,
            'ACTION' => $lAdmin->ActionRedirect($urlEdit),
        ),
        array(
            'ICON' => 'link',
            'TEXT' => GetMessage('OPENSOURCE_REVIEWS_REWARDS'),
            'DEFAULT' => true,
            'ACTION' => $lAdmin->ActionRedirect($urlReward),
        ),
        array(
            'ICON' => 'delete',
            'TEXT' => $property['DELETED'] != 'Y' ? GetMessage('MAIN_ADMIN_MENU_DELETE') : GetMessage('OPENSOURCE_REVIEWS_UNDELETE'),
            'ACTION' => $lAdmin->ActionDoGroup($property['ID'], $delAction, "&lang=".LANGUAGE_ID),
        ),
        array(
            'ICON' => 'delete',
            'TEXT' => GetMessage('OPENSOURCE_REVIEWS_REMOVE'),
            'ACTION' => "if(confirm('".GetMessageJS("OPENSOURCE_REVIEWS_DEL_MESSAGE")."')) ".$lAdmin->ActionDoGroup($property['ID'], "remove", "&IBLOCK_ID=".$arIBlock['ID']."&lang=".LANGUAGE_ID),
        ),
    );
    $row->AddActions($arActions);

    unset($row, $urlEdit);
    
}

$lAdmin->AddGroupActionTable(array(
    "edit" => GetMessage("MAIN_ADMIN_LIST_EDIT"),
    "delete"=>GetMessage("MAIN_ADMIN_LIST_DELETE"),
    "publish"=>GetMessage("OPENSOURCE_REVIEWS_PUBLISH"),
    "unpublish"=>GetMessage("OPENSOURCE_REVIEWS_UNPUBLISH"),
    "undelete"=>GetMessage("OPENSOURCE_REVIEWS_UNDELETE"),
    "remove"=>GetMessage("OPENSOURCE_REVIEWS_REMOVE"),
    "for_all" => true
));

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("OPENSOURCE_REVIEWS_TITLE"));

require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php';

$lAdmin->DisplayFilter($filterFields);
$lAdmin->DisplayList();


require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");

