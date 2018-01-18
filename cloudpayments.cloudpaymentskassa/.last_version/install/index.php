<?
IncludeModuleLangFile(__FILE__);
if (class_exists('cloudpayments_cloudpaymentskassa')) return;
Class cloudpayments_cloudpaymentskassa extends CModule
{
    const MODULE_ID = 'cloudpayments.cloudpaymentskassa';
    var $MODULE_ID = 'cloudpayments.cloudpaymentskassa';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    var $strError = '';

    function __construct()
    {
        $arModuleVersion = array();
        include(dirname(__FILE__)."/version.php");
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = GetMessage("cloudpayments.cloudpaymentskassa_MODULE_NAME");
        $this->MODULE_DESCRIPTION = GetMessage("cloudpayments.cloudpaymentskassa_MODULE_DESC");
        $this->PARTNER_NAME = GetMessage("cloudpayments.cloudpaymentskassa_PARTNER_NAME");
        $this->PARTNER_URI = GetMessage("cloudpayments.cloudpaymentskassa_PARTNER_URI");
    }

    function InstallDB($arParams = array())
    {
        global $DB, $DBType, $APPLICATION;
        $this->errors = false;
        $this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/db/mysql/install.sql");
        if($this->errors !== false)
        {
            $APPLICATION->ThrowException(implode("", $this->errors));
            return false;
        }
        return true;

    }

    function UnInstallDB($arParams = array())
    {
        global $DB, $DBType, $APPLICATION;
        $this->errors = false;
        $this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/db/mysql/uninstall.sql");
        if($this->errors !== false)
        {
            $APPLICATION->ThrowException(implode("", $this->errors));
            return false;
        }
        return true;

    }

    function InstallEvents()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->registerEventHandler("sale", "OnSaleOrderPaid", $this->MODULE_ID, "CCloudpaymentskassa", "OnSaleOrderPaid",9999);
        $eventManager->registerEventHandler("main", "OnAdminContextMenuShow", $this->MODULE_ID, "CCloudpaymentskassa", "OnAdminContextMenuShowHandler",9999);



        return true;
    }

    function UnInstallEvents()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->unRegisterEventHandler("sale", "OnAfterIBlockElementUpdate", $this->MODULE_ID, "CCloudpaymentskassa", "OnSaleOrderPaid");
        $eventManager->unRegisterEventHandler("main", "OnAdminContextMenuShow", $this->MODULE_ID, "CCloudpaymentskassa", "OnAdminContextMenuShowHandler",9999);
        return true;
    }

    function InstallFiles($arParams = array())
    {
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/tools", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools",true,true);
        return true;
    }

    function UnInstallFiles()
    {
        DeleteDirFilesEx('/bitrix/tools/'.$this->MODULE_ID."/");
       return true;
    }

    function DoInstall()
    {
        global $APPLICATION;
        RegisterModule(self::MODULE_ID);
        $this->InstallFiles();
        $this->InstallDB();
        $this->InstallEvents();
    }

    function DoUninstall()
    {
        global $APPLICATION;
        $this->UnInstallEvents();
        $this->UnInstallDB();
        $this->UnInstallFiles();
        UnRegisterModule(self::MODULE_ID);
    }


}
?>