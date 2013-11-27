<?php

if(!defined('_PS_VERSION_'))
    exit;

?>

<form action="" method="POST">
    <input type="hidden" name="webwinkelkeur" value="1" />
    <fieldset>
        <legend>
            <img width="16" height="16" src="<?=$this->_path;?>/logo.png" />
            <?=$this->l('Webwinkelkeur instellingen');?>
        </legend>

        <label for="adv_link"><?=$this->l('Webwinkel ID');?></label>
        <div class="margin-form">
            <input id="adv_link" type="text" name="shop_id" value="<?=$this->escape(Configuration::get('WEBWINKELKEUR_SHOP_ID'));?>" style="width:250px" />
        </div>

        <label for="adv_link"><?=$this->l('API key');?></label>
        <div class="margin-form">
            <input id="adv_link" type="text" name="api_key" value="<?=$this->escape(Configuration::get('WEBWINKELKEUR_API_KEY'));?>" style="width:250px" />
            <p class="preference_description"><?=$this->l('Deze gegevens vindt u bij uw Webwinkelkeur account.');?></p>
        </div>
        <br class="clear" />

        <label for="adv_link"><?=$this->l('Sidebar weergeven');?></label>
        <div class="margin-form">
            <label class="t" for="webwinkelkeur-sidebar-on">
                <img src="../img/admin/enabled.gif" alt="" />
            </label>
            <input type="radio" name="sidebar" id="webwinkelkeur-sidebar-on" value="1" <?php if(Configuration::get('WEBWINKELKEUR_SIDEBAR')) echo 'checked'; ?> />
            <label class="t" for="webwinkelkeur-sidebar-on">Ja</label>
            
            <label class="t" for="webwinkelkeur-sidebar-off">
                <img src="../img/admin/disabled.gif" alt="" />
            </label>
            <input type="radio" name="sidebar" id="webwinkelkeur-sidebar-off" value="" <?php if(!Configuration::get('WEBWINKELKEUR_SIDEBAR')) echo 'checked'; ?> />
            <label class="t" for="webwinkelkeur-sidebar-off">Nee</label>
        </div>
        <br class="clear" />

        <label for="adv_link"><?=$this->l('Uitnodiging versturen');?></label>
        <div class="margin-form">
            <label class="t" for="webwinkelkeur-invite-on">
                <img src="../img/admin/enabled.gif" alt="" />
            </label>
            <input type="radio" name="invite" id="webwinkelkeur-invite-on" value="1" <?php if(Configuration::get('WEBWINKELKEUR_INVITE')) echo 'checked'; ?> />
            <label class="t" for="webwinkelkeur-invite-on">Ja</label>
            
            <label class="t" for="webwinkelkeur-invite-off">
                <img src="../img/admin/disabled.gif" alt="" />
            </label>
            <input type="radio" name="invite" id="webwinkelkeur-invite-off" value="" <?php if(!Configuration::get('WEBWINKELKEUR_INVITE')) echo 'checked'; ?> />
            <label class="t" for="webwinkelkeur-invite-off">Nee</label>

            <p class="preference_description"><?=$this->l('Deze functionaliteit is alleen beschikbaar voor Plus-leden.');?></p>
        </div>

        <label for="adv_link"><?=$this->l('Wachttijd voor uitnodiging');?></label>
        <div class="margin-form">
            <input id="adv_link" type="text" name="invite_delay" value="<?=$this->escape(Configuration::get('WEBWINKELKEUR_INVITE_DELAY'));?>" style="width:50px" />
            <p class="preference_description"><?=$this->l('De uitnodiging wordt verstuurd nadat het opgegeven aantal dagen is verstreken.');?></p>
        </div>
        <br class="clear" />

        <div class="margin-form">
            <input class="button" type="submit" value="<?=$this->l('Opslaan');?>" />
        </div>
    </fieldset>
</form>
