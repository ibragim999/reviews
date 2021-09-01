<?php

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Opensource\Reviews\ReviewLogTable;
use Opensource\Reviews\ReviewTable;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");


Loader::includeModule('sale');
Loader::includeModule('opensource.reviews');
IncludeModuleLangFile(__FILE__);

$APPLICATION->SetTitle(GetMessage("OPENSOURCE_REVIEWS_TITLE", ['#ID#' => $ID]));

$context = Application::getInstance()->getContext();
$request = $context->getRequest();
$flag = $request->isPost();
$errors = [];
$review = $user = $userReviewsQuery = $reviewLogs = $account = null;

if($ID){
    $res = ReviewTable::getList(['filter' => ['ID' => $ID], 'select' => ['*', 'U_' => 'USER.*', 'P_' => 'ELEMENT.*']]);
    $review = $res->fetch();
    if(!$review){
        $errors[] = GetMessage('OPENSOURCE_REVIEWS_NF_REVIEW');
    }

    $reviewLogs = ReviewLogTable::getForReview($ID)->fetchAll();
} else {
    $errors[] = GetMessage('OPENSOURCE_REVIEWS_NF_ID');
}

if($USER_ID) {
    $res = \Bitrix\Main\UserTable::getList(['filter' => ['ID' => $USER_ID]]);
    $user = $res->fetch();
    if(!$user){
        $errors[] = GetMessage('OPENSOURCE_REVIEWS_NF_USER');
    }
    $account = \Opensource\Reviews\Account::getAccount($USER_ID);

    $userReviewsQuery = ReviewTable::getList(['filter' => ['USER_ID' => $USER_ID], 'select' => ['*', 'P_' => 'ELEMENT.*']]);
}
else {
    $errors[] = GetMessage('OPENSOURCE_REVIEWS_NF_USER_ID');
}

if($flag && check_bitrix_sessid() && !$errors){
    $ID = $request->getPost('ID');
    $USER_ID = $request->getPost('USER_ID');
    $save = $request->getPost('save');
    $price = $request->getPost('PRICE');
    $apply = $request->getPost('apply');


    if($ID && $USER_ID && $account){
        \Opensource\Reviews\Account::change($USER_ID, $price);

        $rsData = ReviewLogTable::add([
            'REVIEW_ID' => $ID,
            'PRICE' => (int)$price,
        ]);

        if(!$rsData->isSuccess()){
            $errors = $rsData->getErrorMessages();
        } else {
            if ($save) {
                LocalRedirect('/bitrix/admin/opensource_reviews.php');
            } else {
                LocalRedirect('/bitrix/admin/opensource_reviews_users.php?ID='.$ID.'&USER_ID='.$USER_ID);
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

if($ID && $review && $USER_ID):
    ?>

    <form method="POST" action="<?echo $sDocPath ?>?lang=<?echo LANG ?>" ENCTYPE="multipart/form-data" name="dataload" id="dataload">
        <input type="hidden" name="Update" value="Y">
        <input type="hidden" name="lang" value="<?echo LANGUAGE_ID ?>">
        <input type="hidden" name="ID" value="<?echo $ID ?>">
        <input type="hidden" name="USER_ID" value="<?echo $USER_ID ?>">
        <?=bitrix_sessid_post()?>

        <?
        $aTabs = array(
            array(
                "DIV" => "edit1",
                "TAB" => GetMessage('OPENSOURCE_REVIEWS_TAB'),
                "ICON" => "iblock",
                "TITLE" => "",
            ),
            array(
                "DIV" => "edit2",
                "TAB" => GetMessage('OPENSOURCE_REVIEWS_TAB_2'),
                "ICON" => "iblock",
                "TITLE" => "",
            ),
        );
        $tabControl = new CAdminTabControl("tabControl", $aTabs, false, true);
        $tabControl->Begin();
        $tabControl->BeginNextTab();
        ?>
        <?foreach ($reviewLogs as $log){?>
            <tr>
                <td>
                    Переведено <?=$log['CREATED_AT']?>
                </td>
                <td>
                    <strong><?=$log['PRICE']?></strong>
                    <?=\Opensource\Reviews\Util::plural($log['PRICE'], 'балл', 'баллов', 'балла')?>
                </td>
            </tr>
        <?} ?>
        <tr>
            <td width="40%">Отзыв №:</td>
            <td width="60%"><a href="/bitrix/admin/opensource_reviews_edit.php?lang=<?=LANGUAGE_ID?>&ID=<?= $review['ID'] ?>">[<?echo $ID ?>]</a></td>
        </tr>
        <tr>
            <td width="40%"><?echo GetMessage("OPENSOURCE_REVIEWS_USER") ?>:</td>
            <td width="60%"><a href="/bitrix/admin/user_edit.php?lang=<?=LANGUAGE_ID?>&ID=<?= $review['U_ID'] ?>">[<?= $review['U_ID'] ?>]</a>
                <?= $review['U_NAME'] ?>
                <?= $review['U_LAST_NAME'] ?>
            </td>
        </tr>
        <tr>
            <td width="40%"><?echo GetMessage("OPENSOURCE_REVIEWS_USER_ACCOUNT") ?>:</td>
            <td width="60%">
                <a href="/bitrix/admin/sale_account_edit.php?lang=<?=LANGUAGE_ID?>&ID=<?= $account['ID'] ?>">[<?= $account['ID'] ?>]</a>
                <?= (int)$account['CURRENT_BUDGET'] ?> <?= $account['CURRENCY'] ?>
            </td>
        </tr>
        <tr>
            <td width="40%"><?echo GetMessage("OPENSOURCE_REVIEWS_ELEMENT") ?>:</td>
            <td width="60%">
                <a href="/bitrix/admin/cat_product_edit.php?IBLOCK_ID=<?=$review['P_IBLOCK_ID']?>&type=catalog&lang=<?=LANGUAGE_ID?>&ID=<?= $review['P_ID'] ?>&WF=Y">[<?=$review['P_ID']?>]</a>
                <?echo $review['P_NAME'] ?>
            </td>
        </tr>
        <tr>
            <td width="40%"><?echo GetMessage("OPENSOURCE_REVIEWS_PRICE") ?>:</td>
            <td width="60%"><input type="text" name="PRICE" value=""/></td>
        </tr>


        <?
        $tabControl->EndTab();

        $tabControl->BeginNextTab();

        if($userReviewsQuery) {
            while ($userReview = $userReviewsQuery->fetch()) { ?>
                <tr>
                    <td style="padding-bottom: 40px">
                        <?foreach (['COMMENT', 'DEFECTS', 'TERM', 'RATING', 'P_NAME', 'CREATED_AT'] as $field) { ?>
                            <div><?=GetMessage("OPENSOURCE_REVIEWS_".$field) ?>: <?=htmlspecialcharsEx($userReview[$field])?></div>
                        <? } ?>
                        <?foreach (['PUBLISHED', 'DELETED'] as $field) { ?>
                            <div><?=GetMessage("OPENSOURCE_REVIEWS_".$field) ?>: <?=$userReview[$field] == 'Y' ? 'Да':'Нет'?></div>
                        <? } ?>
                    </td>
                </tr>
            <? }
        }
        $tabControl->EndTab();

        $tabControl->Buttons(array("disabled"=>false,"back_url"=>'opensource_reviews.php?lang='.LANGUAGE_ID));
        ?>
        <?$tabControl->End();?>


    </form>
<?
endif;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
