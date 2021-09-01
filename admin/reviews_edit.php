<?php

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Opensource\Reviews\ReviewLogTable;
use Opensource\Reviews\ReviewTable;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");


Loader::includeModule('opensource.reviews');
IncludeModuleLangFile(__FILE__);

$APPLICATION->SetTitle(GetMessage("OPENSOURCE_REVIEWS_TITLE", ['#ID#' => $ID]));

$context = Application::getInstance()->getContext();
$request = $context->getRequest();
$flag = $request->isPost();
$errors = [];

$review = null;
if($ID){

    $res = ReviewTable::getList(['filter' => ['ID' => $ID], 'select' => ['*', 'U_' => 'USER.*', 'P_' => 'ELEMENT.*']]);
    $review = $res->fetch();
    if(!$review){
        $errors[] = GetMessage('OPENSOURCE_REVIEWS_NF_REVIEW');
    }
} else {
    $errors[] = GetMessage('OPENSOURCE_REVIEWS_NF_ID');
}

if($flag && check_bitrix_sessid()){
    $ID = $request->getPost('ID');
    $save = $request->getPost('save');
    $apply = $request->getPost('apply');

    $updateArr=$request->getPostList()->toArray();
    $keys = array();
    $rsData = ReviewTable::GetMap();

    /** @var \Bitrix\Main\ORM\Fields\Field $item */
    foreach($rsData as $item){
        if($item instanceof \Bitrix\Main\ORM\Fields\Field){
            $keys[] = $item->getName();
        }
    }

    $updateArrNonFree =  $updateArr;
    $updateArr = array_intersect_key($updateArr,array_flip($keys));
    if($updateArr && $ID){
        $rsData = ReviewTable::update($ID, $updateArr);

        if($review['PUBLISHED']==='N' && $updateArr['PUBLISHED']==='Y' && $updateArr['DELETED'] === 'N'){
            if(Option::get('opensource.reviews', "reward_type", 'M') === 'E'){
                $price = Option::get('opensource.reviews', 'price', 1);
                \Opensource\Reviews\Account::change($USER->GetID(), $price);

                ReviewLogTable::add([
                    'REVIEW_ID' => $ID,
                    'PRICE' => $price,
                ]);
            }
        }

        if(!$rsData->isSuccess()){
            $errors = $rsData->getErrorMessages();
        } else {
            if ($save) {
                LocalRedirect('/bitrix/admin/opensource_reviews.php');
            }
        }
    }

}


require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

if($errors){
    foreach ($errors as $error) {
        $message = new CAdminMessage($error);
        echo $message->Show();
    }
}

if($ID && $review):
?>

    <form method="POST" action="<?echo $sDocPath ?>?lang=<?echo LANG ?>" ENCTYPE="multipart/form-data" name="dataload" id="dataload">
        <input type="hidden" name="Update" value="Y">
        <input type="hidden" name="lang" value="<?echo LANGUAGE_ID ?>">
        <input type="hidden" name="ID" value="<?echo $ID ?>">
        <?=bitrix_sessid_post()?>

        <?
        $aTabs = array(
            array(
                "DIV" => "edit1",
                "TAB" => "Редактирование отзыва" ,
                "ICON" => "iblock",
                "TITLE" => "",
            )
        );
        $tabControl = new CAdminTabControl("tabControl", $aTabs, false, true);
        $tabControl->Begin();
        $tabControl->BeginNextTab();
        ?>
        <tr>
            <td width="40%">ID:</td>
            <td width="60%"><?echo $ID ?></td>
        </tr>
        <tr>
            <td width="40%"><?echo GetMessage("OPENSOURCE_REVIEWS_USER") ?>:</td>
            <td width="60%"><a href="/bitrix/admin/user_edit.php?lang=ru&ID=<?= $review['U_ID'] ?>">[<?= $review['U_ID'] ?>]</a> <?= $review['U_NAME'] ?></td>
        </tr>
        <tr>
            <td width="40%"><?echo GetMessage("OPENSOURCE_REVIEWS_ELEMENT") ?>:</td>
            <td width="60%">
                <a href="/bitrix/admin/cat_product_edit.php?IBLOCK_ID=<?=$review['P_IBLOCK_ID']?>&type=catalog&lang=<?=LANGUAGE_ID?>&ID=<?= $review['P_ID'] ?>&WF=Y">[<?=$review['P_ID']?>]</a>
                <?echo $review['P_NAME'] ?>
                <a href="/bitrix/admin/opensource_reviews_users.php?ID=<?=$review['ID']?>&USER_ID=<?=$review['U_ID']?>">Наградить</a>
            </td>
        </tr>
        <tr>
            <td width="40%"><?echo GetMessage("OPENSOURCE_REVIEWS_PUBLISHED") ?></td>
            <td width="60%">
                <input type="hidden" name="PUBLISHED" id="IS_PUBLISHED_N" value="N">
                <input type="checkbox" name="PUBLISHED" id="IS_PUBLISHED_Y" value="Y" <?if($review['PUBLISHED'] == "Y") echo "checked";?>>
            </td>
        </tr>
        <tr>
            <td width="40%"><?echo GetMessage("OPENSOURCE_REVIEWS_DELETED") ?></td>
            <td width="60%">
                <input type="hidden" name="DELETED" id="IS_DELETED_N" value="N">
                <input type="checkbox" name="DELETED" id="IS_DELETED_Y" value="Y" <?if($review['DELETED'] == "Y") echo "checked";?>>
            </td>
        </tr>
        <tr class="adm-detail-required-field">
            <td width="40%"><?echo GetMessage("OPENSOURCE_REVIEWS_COMMENT") ?></td>
            <td width="60%"><textarea rows="6" cols="50" type="text" name="COMMENT"><? echo htmlspecialcharsbx($review['COMMENT']); ?></textarea></td>
        </tr>
        <tr class="adm-detail-required-field">
            <td width="40%"><?echo GetMessage("OPENSOURCE_REVIEWS_DEFECTS") ?></td>
            <td width="60%"><textarea rows="6" cols="50" type="text" name="DEFECTS"><? echo htmlspecialcharsbx($review['DEFECTS']); ?></textarea></td>
        </tr>
        <tr>
            <td width="40%"><?echo GetMessage("OPENSOURCE_REVIEWS_TERM") ?></td>
            <td width="60%"><input type="text" name="TERM" value="<? echo $review['TERM']; ?>"></td>
        </tr>
        <tr>
            <td width="40%"><?echo GetMessage("OPENSOURCE_REVIEWS_RATING") ?></td>
            <td width="60%"><input type="number" max="5" min="1" name="RATING" value="<? echo (int)$review['RATING']; ?>"></td>
        </tr>


        <?
        $tabControl->EndTab();
        $tabControl->Buttons(array("disabled"=>false,"back_url"=>'opensource_reviews.php?lang='.LANGUAGE_ID));
        ?>
        <?$tabControl->End();?>


    </form>
<?
endif;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
