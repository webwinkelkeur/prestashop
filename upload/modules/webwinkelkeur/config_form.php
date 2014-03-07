<?php

if(!defined('_PS_VERSION_'))
    exit;

?>

<form action="" method="POST">
    <input type="hidden" name="webwinkelkeur" value="1" />
    <fieldset>
        <legend>
            <img width="16" height="16" src="<?=$this->_path;?>/logo.png" />
            <?=$this->l('eValor instellingen');?>
        </legend>

        <label for="adv_link"><?=$this->l('Webwinkel ID');?></label>
        <div class="margin-form">
            <input id="adv_link" type="text" name="shop_id" value="<?=$this->escape(Configuration::get('WEBWINKELKEUR_SHOP_ID'));?>" style="width:250px" />
        </div>

        <label for="adv_link"><?=$this->l('API key');?></label>
        <div class="margin-form">
            <input id="adv_link" type="text" name="api_key" value="<?=$this->escape(Configuration::get('WEBWINKELKEUR_API_KEY'));?>" style="width:250px" />
            <p class="preference_description"><?=$this->l('Deze gegevens vindt u bij uw eValor account.');?></p>
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

        <label for="adv_link"><?=$this->l('Sidebar positie');?></label>
        <div class="margin-form">
            <input type="radio" name="sidebar_position" id="webwinkelkeur-sidebar-position-left" value="left" <?php if(Configuration::get('WEBWINKELKEUR_SIDEBAR_POSITION') == 'left') echo 'checked'; ?> />
            <label class="t" for="webwinkelkeur-sidebar-position-left">Links</label>
            
            <input type="radio" name="sidebar_position" id="webwinkelkeur-sidebar-position-right" value="right" <?php if(Configuration::get('WEBWINKELKEUR_SIDEBAR_POSITION') == 'right') echo 'checked'; ?> />
            <label class="t" for="webwinkelkeur-sidebar-position-right">Rechts</label>
        </div>

        <label for="adv_link"><?=$this->l('Sidebar hoogte');?></label>
        <div class="margin-form">
            <input id="adv_link" type="text" name="sidebar_top" value="<?=$this->escape(Configuration::get('WEBWINKELKEUR_SIDEBAR_TOP'));?>" style="width:50px" />
            <p class="preference_description"><?=$this->l('Het aantal pixels vanaf de bovenkant.');?></p>
        </div>
        <br class="clear" />

        <label for="adv_link"><?=$this->l('Uitnodiging versturen');?></label>
        <div class="margin-form">
            <label class="t" for="webwinkelkeur-invite-on">
                <img src="../img/admin/enabled.gif" alt="" />
            </label>
            <input type="radio" name="invite" id="webwinkelkeur-invite-on" value="1" <?php if(Configuration::get('WEBWINKELKEUR_INVITE') == 1) echo 'checked'; ?> />
            <label class="t" for="webwinkelkeur-invite-on">Ja, na elke bestelling</label><br />

            <label class="t" for="webwinkelkeur-invite-first">
                <img src="../img/admin/enabled.gif" alt="" />
            </label>
            <input type="radio" name="invite" id="webwinkelkeur-invite-first" value="2" <?php if(Configuration::get('WEBWINKELKEUR_INVITE') == 2) echo 'checked'; ?> />
            <label class="t" for="webwinkelkeur-invite-first">Ja, alleen bij de eerste bestelling</label><br />

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

        <label for="adv_link"><?=$this->l('Tooltip weergeven');?></label>
        <div class="margin-form">
            <label class="t" for="webwinkelkeur-tooltip-on">
                <img src="../img/admin/enabled.gif" alt="" />
            </label>
            <input type="radio" name="tooltip" id="webwinkelkeur-tooltip-on" value="1" <?php if(Configuration::get('WEBWINKELKEUR_TOOLTIP')) echo 'checked'; ?> />
            <label class="t" for="webwinkelkeur-tooltip-on">Ja</label>
            
            <label class="t" for="webwinkelkeur-tooltip-off">
                <img src="../img/admin/disabled.gif" alt="" />
            </label>
            <input type="radio" name="tooltip" id="webwinkelkeur-tooltip-off" value="" <?php if(!Configuration::get('WEBWINKELKEUR_TOOLTIP')) echo 'checked'; ?> />
            <label class="t" for="webwinkelkeur-tooltip-off">Nee</label>
        </div>
        <br class="clear" />

        <label for="adv_link"><?=$this->l('JavaScript-integratie');?></label>
        <div class="margin-form">
            <label class="t" for="webwinkelkeur-javascript-on">
                <img src="../img/admin/enabled.gif" alt="" />
            </label>
            <input type="radio" name="javascript" id="webwinkelkeur-javascript-on" value="1" <?php if(Configuration::get('WEBWINKELKEUR_JAVASCRIPT')) echo 'checked'; ?> />
            <label class="t" for="webwinkelkeur-javascript-on">Ja</label>
            
            <label class="t" for="webwinkelkeur-javascript-off">
                <img src="../img/admin/disabled.gif" alt="" />
            </label>
            <input type="radio" name="javascript" id="webwinkelkeur-javascript-off" value="" <?php if(!Configuration::get('WEBWINKELKEUR_JAVASCRIPT')) echo 'checked'; ?> />
            <label class="t" for="webwinkelkeur-javascript-off">Nee</label>
        </div>
        <br class="clear" />

        <div class="margin-form">
            <input class="button" type="submit" value="<?=$this->l('Opslaan');?>" />
        </div>
    </fieldset>
</form>

<?php if($invite_errors): ?>
<fieldset style="margin-top:1em;">
    <legend>
        <?php echo $this->l('Fouten opgetreden bij het versturen van uitnodigingen'); ?>
    </legend>
    <table>
      <?php foreach($invite_errors as $invite_error): ?>
      <tr>
        <td style="padding-right:10px;"><?php echo date('d-m-Y H:i', $invite_error['time']); ?></td>
        <td>
          <?php if($invite_error['response']): ?>
          <?php echo htmlentities($invite_error['response'], ENT_QUOTES, 'UTF-8'); ?>
          <?php else: ?>
          De eValor-server kon niet worden bereikt.
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
</fieldset>
<?php endif; ?>
