<?php

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Opensource\Reviews\ReviewTable;
use Opensource\Reviews\ReviewLogTable;

Loc::loadMessages(__FILE__);

class opensource_reviews extends CModule
{
    public function __construct()
    {
        $arModuleVersion = array();
        
        include __DIR__ . '/version.php';

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion))
        {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }
        
        $this->MODULE_ID = 'opensource.reviews';
        $this->MODULE_NAME = Loc::getMessage('OPENSOURCE_REVIEWS_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('OPENSOURCE_REVIEWS_MODULE_DESCRIPTION');
        $this->MODULE_GROUP_RIGHTS = 'N';
        $this->PARTNER_NAME = Loc::getMessage('OPENSOURCE_REVIEWS_MODULE_PARTNER_NAME');
        $this->PARTNER_URI = '';
    }

    public function doInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->installDB();
        $this->installFiles();
    }

    public function doUninstall()
    {
        $this->uninstallDB();
        $this->unInstallFiles();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function installDB()
    {
        if (Loader::includeModule($this->MODULE_ID))
        {
            ReviewTable::getEntity()->createDbTable();
            ReviewLogTable::getEntity()->createDbTable();
        }
    }

    public function uninstallDB()
    {
        if (Loader::includeModule($this->MODULE_ID))
        {
            $connection = Application::getInstance()->getConnection();
            $connection->dropTable(ReviewTable::getTableName());
            $connection->dropTable(ReviewLogTable::getTableName());
        }

        global $APPLICATION;
        $APPLICATION->GetPageProperty();
    }

    function installFiles()
    {
        CopyDirFiles(__DIR__ . '/components', $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . "/components", true, true);
        CopyDirFiles(__DIR__ . '/admin', $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . "/admin", true, true);
    }

    function unInstallFiles()
    {
        DeleteDirFilesEx(BX_ROOT . '/components/opensource/reviews');
        DeleteDirFilesEx('/local/components/opensource/reviews');
        DeleteDirFilesEx(BX_ROOT.'/admin/opensource_reviews.php');
        DeleteDirFilesEx(BX_ROOT.'/admin/opensource_reviews_edit.php');
        DeleteDirFilesEx(BX_ROOT.'/admin/opensource_reviews_user.php');
    }
}
