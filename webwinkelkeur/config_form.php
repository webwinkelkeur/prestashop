<?php

if(!defined('_PS_VERSION_'))
    exit;

?>

<form action="" method="POST">
    <input type="hidden" name="webwinkelkeur" value="1" />
    <fieldset>
        <legend>
            <img width="16" height="16" src="<?=$this->_path;?>/logo.png" />
            <?=$this->l('WebwinkelKeur instellingen');?>
        </legend>

        <label for="adv_link"><?=$this->l('Webwinkel ID');?></label>
        <div class="margin-form">
            <input id="adv_link" type="text" name="shop_id" value="<?=$this->escape(Configuration::get('WEBWINKELKEUR_SHOP_ID'));?>" style="width:250px" />
        </div>

        <label for="adv_link"><?=$this->l('API key');?></label>
        <div class="margin-form">
            <input id="adv_link" type="text" name="api_key" value="<?=$this->escape(Configuration::get('WEBWINKELKEUR_API_KEY'));?>" style="width:250px" />
            <p class="preference_description"><?=$this->l('Deze gegevens vindt u bij uw WebwinkelKeur account.');?></p>
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

            <p class="preference_description"><?= sprintf(html_entity_decode($this->l('Gebruik de JavaScript-integratie om de sidebar en de tooltip op uw site te plaatsen.<br>Alle instellingen voor de sidebar en de tooltip, vindt u in het %sWebwinkelKeur Dashboard%s.'), ENT_QUOTES, 'UTF-8'), '<a href="https://dashboard.webwinkelkeur.nl/integration" target="_blank">', '</a>'); ?></p>
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
        </div>

        <label for="adv_link"><?=$this->l('Wachttijd voor uitnodiging');?></label>
        <div class="margin-form">
            <input id="adv_link" type="text" name="invite_delay" value="<?=$this->escape(Configuration::get('WEBWINKELKEUR_INVITE_DELAY'));?>" style="width:50px" />
            <p class="preference_description"><?=$this->l('De uitnodiging wordt verstuurd nadat het opgegeven aantal dagen is verstreken.');?></p>
        </div>
        <br class="clear" />

        <label for="adv_link"><?=$this->l('Rich snippet sterren');?></label>
        <div class="margin-form">
            <label class="t" for="webwinkelkeur-rich-snippet-on">
                <img src="../img/admin/enabled.gif" alt="" />
            </label>
            <input type="radio" name="rich_snippet" id="webwinkelkeur-rich-snippet-on" value="1" <?php if(Configuration::get('WEBWINKELKEUR_RICH_SNIPPET')) echo 'checked'; ?> />
            <label class="t" for="webwinkelkeur-rich-snippet-on">Ja</label>
            
            <label class="t" for="webwinkelkeur-rich-snippet-off">
                <img src="../img/admin/disabled.gif" alt="" />
            </label>
            <input type="radio" name="rich_snippet" id="webwinkelkeur-rich-snippet-off" value="" <?php if(!Configuration::get('WEBWINKELKEUR_RICH_SNIPPET')) echo 'checked'; ?> />
            <label class="t" for="webwinkelkeur-rich-snippet-off">Nee</label>

            <p class="preference_description"><?=html_entity_decode($this->l('Voeg een <a href="https://support.google.com/webmasters/answer/99170?hl=nl">rich snippet</a> toe aan de footer. Google kan uw waardering dan in de zoekresultaten tonen. Gebruik op eigen risico.'), ENT_QUOTES, 'UTF-8');?></p>
        </div>
        <br class="clear" />

        <div class="margin-form">
            <label class="t">
                <input type="checkbox" name="limit_order_data" value="1" <?php if(Configuration::get('WEBWINKELKEUR_LIMIT_ORDER_DATA')) echo 'checked '; ?>/>
                <?=$this->l('Do not send order information to WebwinkelKeur')?>
            </label>
            <p class="preference_description">
                <?=$this->l('Please note: not all WebwinkelKeur functionality will be available if you check this option!')?>
            </p>
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
          De WebwinkelKeur-server kon niet worden bereikt.
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
</fieldset>
<?php endif; ?>
