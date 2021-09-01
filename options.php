<?php

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;

defined('ADMIN_MODULE_NAME') or define('ADMIN_MODULE_NAME', 'opensource.reviews');

if (!$USER->isAdmin()) {
    $APPLICATION->authForm('Nope');
}

$app = Application::getInstance();
$context = $app->getContext();
$request = $context->getRequest();

Loc::loadMessages($context->getServer()->getDocumentRoot()."/bitrix/modules/main/options.php");
Loc::loadMessages(__FILE__);

$tabControl = new CAdminTabControl("tabControl", array(
    array(
        "DIV" => "edit1",
        "TAB" => Loc::getMessage("MAIN_TAB_SET"),
        "TITLE" => Loc::getMessage("MAIN_TAB_TITLE_SET"),
    ),
));

if ((!empty($save) || !empty($restore)) && $request->isPost() && check_bitrix_sessid()) {
    if (!empty($restore)) {
        Option::delete(ADMIN_MODULE_NAME);
        CAdminMessage::showMessage(array(
            "MESSAGE" => Loc::getMessage("REFERENCES_OPTIONS_RESTORED"),
            "TYPE" => "OK",
        ));
    } elseif ($request->getPost('max_budget') && ($request->getPost('max_budget') > 0) && ($request->getPost('max_budget') < 100000)) {
        Option::set(
            ADMIN_MODULE_NAME,
            "max_budget",
            $request->getPost('max_budget')
        );
        Option::set(
            ADMIN_MODULE_NAME,
            "reward_type",
            $request->getPost('reward_type')
        );
        Option::set(
            ADMIN_MODULE_NAME,
            "price",
            $request->getPost('price')
        );
        CAdminMessage::showMessage(array(
            "MESSAGE" => Loc::getMessage("REFERENCES_OPTIONS_SAVED"),
            "TYPE" => "OK",
        ));
    } else {
        CAdminMessage::showMessage(Loc::getMessage("REFERENCES_INVALID_VALUE"));
    }
}

$tabControl->begin();
?>

<form method="post" action="<?=sprintf('%s?mid=%s&lang=%s', $request->getRequestedPage(), urlencode($mid), LANGUAGE_ID)?>">
    <?php
    echo bitrix_sessid_post();
    $tabControl->beginNextTab();
    ?>
    <tr>
        <td width="40%">
            <label for="max_budget"><?=Loc::getMessage("REFERENCES_MAX_BUDGET") ?>:</label>
        <td width="60%">
            <input type="text"
                   size="50"
                   id="max_budget"
                   maxlength="5"
                   name="max_budget"
                   value="<?=HtmlFilter::encode(Option::get(ADMIN_MODULE_NAME, "max_budget", 1000));?>"
                   />
        </td>
    </tr>
    <tr>
        <td width="40%">
            <label for="reward_type"><?=Loc::getMessage("REFERENCES_REWARD_TYPE") ?>:</label>
        <td width="60%">
            <? $value = Option::get(ADMIN_MODULE_NAME, "reward_type", 'M');?>
            <select id="reward_type" name="reward_type">
                <option <?=$value=='M'?'selected':''?> value="M">Вручную</option>
                <option <?=$value=='N'?'selected':''?> value="N">При добавлении</option>
                <option <?=$value=='E'?'selected':''?> value="E">При утверждении</option>
            </select>
        </td>
    </tr>
    <tr>
        <td width="40%">
            <label for="price"><?=Loc::getMessage("REFERENCES_PRICE") ?>:</label>
        <td width="60%">
            <input type="text"
                   size="50"
                   id="price"
                   maxlength="5"
                   name="price"
                   value="<?=HtmlFilter::encode(Option::get(ADMIN_MODULE_NAME, "price", 1));?>"
            />
        </td>
    </tr>

    <?php
    $tabControl->buttons();
    ?>
    <input type="submit"
           name="save"
           value="<?=Loc::getMessage("MAIN_SAVE") ?>"
           title="<?=Loc::getMessage("MAIN_OPT_SAVE_TITLE") ?>"
           class="adm-btn-save"
           />
    <input type="submit"
           name="restore"
           title="<?=Loc::getMessage("MAIN_HINT_RESTORE_DEFAULTS") ?>"
           onclick="return confirm('<?= AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING")) ?>')"
           value="<?=Loc::getMessage("MAIN_RESTORE_DEFAULTS") ?>"
           />
    <?php
    $tabControl->end();
    ?>
</form>
