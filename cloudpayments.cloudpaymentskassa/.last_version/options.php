<?php
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main;
use Bitrix\Main\Context;
global $APPLICATION;
$module_id="cloudpayments.cloudpaymentskassa";
$POST_RIGHT = $APPLICATION->GetGroupRight($module_id);
Loc::loadMessages(__FILE__);
Main\Loader::includeModule($module_id);
if($POST_RIGHT>="R") :
    Loc::loadMessages($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
    $arSites = array();
    $arSiteList = array();
    $dbSites=Main\SiteTable::getList(array(
        'filter'=>array("ACTIVE"=>"Y"),
        'select'=>array("*","LID","NAME")
    ));
    while ($arSite = $dbSites->fetch()){
        $arSites[] = $arSite;
        $arSiteList[] = $arSite['LID'];
    }
    $NALOGTYPE['REFERENCE']=array(Loc::getMessage('NALOG_TYPE_0'),
        Loc::getMessage('NALOG_TYPE_1'),
        Loc::getMessage('NALOG_TYPE_2'),
        Loc::getMessage('NALOG_TYPE_3'),
        Loc::getMessage('NALOG_TYPE_4'),
        Loc::getMessage('NALOG_TYPE_4'),
    );
    $NALOGTYPE['REFERENCE_ID']=array(0,1,2,3,4,5);
    \Bitrix\Main\Loader::includeModule("sale");
    $res = \CSalePaySystem::GetList(array("NAME"=>"ASC"));
    while ($r = $res->Fetch()) {
        $PAYS['REFERENCE'][]="[".$r['ID']."] ".$r['NAME'];
        $PAYS['REFERENCE_ID'][]=$r['ID'];
    }


    $aTabs = array(
        array("DIV" => "edit1", "TAB" => Loc::getMessage("MAIN_TAB_SET"), "ICON" => "ib_settings", "TITLE" => Loc::getMessage("MAIN_TAB_TITLE_SET")),
        array("DIV" => "edit2", "TAB" => Loc::getMessage("MAIN_TAB_RIGHTS"), "ICON" => "", "TITLE" => Loc::getMessage("MAIN_TAB_TITLE_RIGHTS")),
    );
    $tabControl = new CAdminTabControl("tabControl", $aTabs);
    if($REQUEST_METHOD=="POST" && strlen($Update.$Apply.$RestoreDefaults)>0 && $POST_RIGHT=="W" && check_bitrix_sessid()){
        if(strlen($RestoreDefaults)>0)
        {
            Option::delete($module_id);
        }
        else{
            $Update = $Update.$Apply;
            Option::set($module_id, "use_on_sites", serialize($_POST["use_on_sites"]));
            foreach($arSiteList as $site):
                $params=$_POST[$site];
                Option::set($module_id,'SETTINGS',serialize($params),$site);
            endforeach;
            foreach($arSiteList as $site):
                $params=$_POST[$site];
            endforeach;
        }
        ob_start();
        require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
        ob_end_clean();
        if(strlen($_REQUEST["back_url_settings"]) > 0)
        {
            if((strlen($Apply) > 0) || (strlen($RestoreDefaults) > 0))
                LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($module_id)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl->ActiveTabParam());
            else
                LocalRedirect($_REQUEST["back_url_settings"]);
        }
        else
        {
            LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($module_id)."&lang=".urlencode(LANGUAGE_ID)."&".$tabControl->ActiveTabParam());
        }
    }
    $tabControl->Begin();?>
    <form method="post" id="frm" name="frm" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=urlencode($module_id)?>&amp;lang=<?=LANGUAGE_ID?>">
        <?$tabControl->BeginNextTab();?>
        <tr>
            <td colspan="2">
                <?foreach($arSites as $arSite)
                    $aSiteTabs[] = array("DIV" => "opt_site_".$arSite["LID"], "TAB" => '['.$arSite["LID"].'] '.htmlspecialchars($arSite["NAME"]),
                        'TITLE' => Loc::getMessage("VBCH_CLDKASSA_OPTION_SITE_TITLE").' ['.$arSite["LID"].'] '.htmlspecialchars($arSite["NAME"]));
                $siteTabControl = new CAdminViewTabControl("siteTabControl", $aSiteTabs);
                $siteTabControl->Begin();
                $arUseOnSites = unserialize(Option::get($module_id, "use_on_sites", ""));
                foreach($arSiteList as $site):
                    $SETTINGS=array();
                    $SETTINGS=unserialize(Option::get($module_id,'SETTINGS',"",$site));
                    $suffix = ($site <> ''? '_bx_site_'.$site:'');
                    $siteTabControl->BeginNextTab();
                    if($site <> ''):?>
                        <table cellpadding="0" width="100%" cellspacing="0" border="0" class="edit-table">
                            <tr>
                                <td width="50%" class="field-name"><label for="use_on_sites<?=$suffix?>"><?echo Loc::getMessage("VBCH_CLDKASSA_OPTION_SITE_APPLY")?></td>
                                <td width="50%" style="padding-left:7px;">
                                    <input type="hidden" name="use_on_sites[<?=htmlspecialchars($site)?>]" value="N">
                                    <input type="checkbox" name="use_on_sites[<?=htmlspecialchars($site)?>]" value="Y"<?if($arUseOnSites[$site] == "Y") echo ' checked'?> id="use_on_sites<?=$suffix?>" onclick="BX('site_settings<?=$suffix?>').style.display=(this.checked? '':'none');">
                                </td>
                            </tr>
                        </table>
                    <?endif?>
                    <table cellpadding="0" width="100%" cellspacing="0" border="0" class="edit-table" id="site_settings<?=$suffix?>"<?if($site <> '' && $arUseOnSites[$site] <> "Y") echo ' style="display:none"';?>>
                        <tr class="heading">
                            <td colspan="2">
                                <?=Loc::getMessage('VBCH_CLD_KASSA_OPTION_GENERAL')?>
                            </td>
                        </tr>
                        <tr>
                            <td width="40%" class="adm-detail-content-cell-l">
                                <label for="KEY"><?=Loc::getMessage('VBCH_CLDKASSA_PUBLICID')?></label>
                            </td>
                            <td width="60%" class="adm-detail-content-cell-r">
                                <input type="text" size="30" name="<?=$site?>[APIKEY]" value="<?=$SETTINGS['APIKEY']?>"/>
                            </td>
                        </tr>
                        <tr>
                            <td width="40%" class="adm-detail-content-cell-l">
                                <label for="PSW"><?=Loc::getMessage('VBCH_CLDKASSA_APISECRET')?></label>
                            </td>
                            <td width="60%" class="adm-detail-content-cell-r">
                                <input type="text" size="30" name="<?=$site?>[APIPSW]" value="<?=$SETTINGS['APIPSW']?>"/>
                            </td>
                        </tr>
                        <tr>
                            <td width="50%" class="adm-detail-content-cell-l">
                               <?=Loc::getMessage('VBCH_CLDKASSA_SEND_CHECK')?>
                            </td>
                            <td width="50%" class="adm-detail-content-cell-r">
                                <input type="checkbox" name="<?=$site?>[PHONE]" value="Y" <?if($SETTINGS['PHONE']=="Y") echo "checked='true'"?>/> <?=Loc::getMessage('VBCH_CLDKASSA_SEND_CHECK_PHONE')?><br/>
                                <input type="checkbox" name="<?=$site?>[EMAIL]" value="Y" <?if($SETTINGS['EMAIL']=="Y") echo "checked='true'"?>/> <?=Loc::getMessage('VBCH_CLDKASSA_SEND_CHECK_EMAIL')?>
                            </td>
                        </tr>
                        <tr>
                            <td width="40%" class="adm-detail-content-cell-l">
                                <label for="INN"><?=Loc::getMessage('VBCH_CLDKASSA_INN')?></label>
                            </td>
                            <td width="60%" class="adm-detail-content-cell-r">
                                <input type="text" size="30" name="<?=$site?>[INN]" value="<?=$SETTINGS['INN']?>"/>
                            </td>
                        </tr>
                        <tr>
                            <td width="40%" class="adm-detail-content-cell-l">
                                <label for="PAY_SYSTEM_ID"><?=Loc::getMessage('VBCH_CLDKASSA_PAYMENT')?></label>
                            </td>
                            <td width="60%" class="adm-detail-content-cell-r">
                                <?=SelectBoxMFromArray($site."[PAY_SYSTEM_ID][]", $PAYS,$SETTINGS["PAY_SYSTEM_ID"])?>
                            </td>
                        </tr>
                        <tr>
                            <td width="40%" class="adm-detail-content-cell-l">
                                <label for="NALOG_TYPE"><?=Loc::getMessage('VBCH_CLDKASSA_NALOG')?></label>
                            </td>
                            <td width="60%" class="adm-detail-content-cell-r">
                                <?=SelectBoxFromArray($site."[NALOG_TYPE]", $NALOGTYPE,$SETTINGS["NALOG_TYPE"])?>
                            </td>
                        </tr>
                    </table>
                <?endforeach;?>
                <?$siteTabControl->End();?>
            </td>
        </tr>
        <?$tabControl->BeginNextTab();
        require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");?>
        <?$tabControl->Buttons();?>
        <input <?if ($POST_RIGHT<"W") echo "disabled" ?> type="submit" name="Update" value="<?=Loc::getMessage("MAIN_SAVE")?>" title="<?=Loc::getMessage("MAIN_OPT_SAVE_TITLE")?>">
        <input <?if ($POST_RIGHT<"W") echo "disabled" ?> type="submit" name="Apply" value="<?=Loc::getMessage("MAIN_OPT_APPLY")?>" title="<?=Loc::getMessage("MAIN_OPT_APPLY_TITLE")?>">
        <?if(strlen($_REQUEST["back_url_settings"])>0):?>
            <input <?if ($POST_RIGHT<"W") echo "disabled" ?> type="button" name="Cancel" value="<?=Loc::getMessage("MAIN_OPT_CANCEL")?>" title="<?=Loc::getMessage("MAIN_OPT_CANCEL_TITLE")?>" onclick="window.location='<?echo htmlspecialchars(CUtil::addslashes($_REQUEST["back_url_settings"]))?>'">
            <input type="hidden" name="back_url_settings" value="<?=htmlspecialchars($_REQUEST["back_url_settings"])?>">
        <?endif?>
        <input <?if ($POST_RIGHT<"W") echo "disabled" ?> type="submit" name="RestoreDefaults" title="<?echo Loc::getMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="return confirm('<?echo AddSlashes(Loc::getMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>')" value="<?echo Loc::getMessage("MAIN_RESTORE_DEFAULTS")?>">
        <?=bitrix_sessid_post();?>
        <?$tabControl->End();?>
    </form>
<?endif;?>