<?php

if(!defined('_PS_VERSION_'))
    exit;

?>

<form action="" method="POST">
    <input type="hidden" name="webwinkelkeur" value="1" />
    <fieldset>
        <legend>
            <img width="16" height="16" src="<?=$this->_path;?>/logo.png" />
            <?=$this->l('Configuraciones de eValor');?>
        </legend>

        <label for="adv_link"><?=$this->l('ID de la tienda online');?></label>
        <div class="margin-form">
            <input id="adv_link" type="text" name="shop_id" value="<?=$this->escape(Configuration::get('WEBWINKELKEUR_SHOP_ID'));?>" style="width:250px" />
        </div>

        <label for="adv_link"><?=$this->l('Clave API');?></label>
        <div class="margin-form">
            <input id="adv_link" type="text" name="api_key" value="<?=$this->escape(Configuration::get('WEBWINKELKEUR_API_KEY'));?>" style="width:250px" />
            <p class="preference_description"><?=$this->l('Estos datos los encontrará al ingresar en eValor.es.');?></p>
        </div>
        <br class="clear" />

        <label for="adv_link"><?=$this->l('Mostrar sidebar');?></label>
        <div class="margin-form">
            <label class="t" for="webwinkelkeur-sidebar-on">
                <img src="../img/admin/enabled.gif" alt="" />
            </label>
            <input type="radio" name="sidebar" id="webwinkelkeur-sidebar-on" value="1" <?php if(Configuration::get('WEBWINKELKEUR_SIDEBAR')) echo 'checked'; ?> />
            <label class="t" for="webwinkelkeur-sidebar-on">Sí</label>
            
            <label class="t" for="webwinkelkeur-sidebar-off">
                <img src="../img/admin/disabled.gif" alt="" />
            </label>
            <input type="radio" name="sidebar" id="webwinkelkeur-sidebar-off" value="" <?php if(!Configuration::get('WEBWINKELKEUR_SIDEBAR')) echo 'checked'; ?> />
            <label class="t" for="webwinkelkeur-sidebar-off">No</label>
        </div>

        <label for="adv_link"><?=$this->l('Posición sidebar');?></label>
        <div class="margin-form">
            <input type="radio" name="sidebar_position" id="webwinkelkeur-sidebar-position-left" value="left" <?php if(Configuration::get('WEBWINKELKEUR_SIDEBAR_POSITION') == 'left') echo 'checked'; ?> />
            <label class="t" for="webwinkelkeur-sidebar-position-left">Izquierda</label>
            
            <input type="radio" name="sidebar_position" id="webwinkelkeur-sidebar-position-right" value="right" <?php if(Configuration::get('WEBWINKELKEUR_SIDEBAR_POSITION') == 'right') echo 'checked'; ?> />
            <label class="t" for="webwinkelkeur-sidebar-position-right">Derecha</label>
        </div>

        <label for="adv_link"><?=$this->l('Altura sidebar');?></label>
        <div class="margin-form">
            <input id="adv_link" type="text" name="sidebar_top" value="<?=$this->escape(Configuration::get('WEBWINKELKEUR_SIDEBAR_TOP'));?>" style="width:50px" />
            <p class="preference_description"><?=$this->l('Número de pixeles desde arriba');?></p>
        </div>
        <br class="clear" />

        <label for="adv_link"><?=$this->l('Enviar invitaciones');?></label>
        <div class="margin-form">
            <label class="t" for="webwinkelkeur-invite-on">
                <img src="../img/admin/enabled.gif" alt="" />
            </label>
            <input type="radio" name="invite" id="webwinkelkeur-invite-on" value="1" <?php if(Configuration::get('WEBWINKELKEUR_INVITE') == 1) echo 'checked'; ?> />
            <label class="t" for="webwinkelkeur-invite-on">Sí, después de cada pedido</label><br />

            <label class="t" for="webwinkelkeur-invite-first">
                <img src="../img/admin/enabled.gif" alt="" />
            </label>
            <input type="radio" name="invite" id="webwinkelkeur-invite-first" value="2" <?php if(Configuration::get('WEBWINKELKEUR_INVITE') == 2) echo 'checked'; ?> />
            <label class="t" for="webwinkelkeur-invite-first">Sí, sólo con el primer pedido</label><br />

            <label class="t" for="webwinkelkeur-invite-off">
                <img src="../img/admin/disabled.gif" alt="" />
            </label>
            <input type="radio" name="invite" id="webwinkelkeur-invite-off" value="" <?php if(!Configuration::get('WEBWINKELKEUR_INVITE')) echo 'checked'; ?> />
            <label class="t" for="webwinkelkeur-invite-off">No, no enviar invitaciones</label>

            <p class="preference_description"><?=$this->l('Esta función solo está disponible para socios PLUS.');?></p>
        </div>

        <label for="adv_link"><?=$this->l('Plazo para la invitación');?></label>
        <div class="margin-form">
            <input id="adv_link" type="text" name="invite_delay" value="<?=$this->escape(Configuration::get('WEBWINKELKEUR_INVITE_DELAY'));?>" style="width:50px" />
            <p class="preference_description"><?=$this->l('La invitación se envía una vez hayan pasado el número de días indicados después de enviar el pedido.');?></p>
        </div>
        <br class="clear" />

        <label for="adv_link"><?=$this->l('Mostrar logo desplegable');?></label>
        <div class="margin-form">
            <label class="t" for="webwinkelkeur-tooltip-on">
                <img src="../img/admin/enabled.gif" alt="" />
            </label>
            <input type="radio" name="tooltip" id="webwinkelkeur-tooltip-on" value="1" <?php if(Configuration::get('WEBWINKELKEUR_TOOLTIP')) echo 'checked'; ?> />
            <label class="t" for="webwinkelkeur-tooltip-on">Sí</label>
            
            <label class="t" for="webwinkelkeur-tooltip-off">
                <img src="../img/admin/disabled.gif" alt="" />
            </label>
            <input type="radio" name="tooltip" id="webwinkelkeur-tooltip-off" value="" <?php if(!Configuration::get('WEBWINKELKEUR_TOOLTIP')) echo 'checked'; ?> />
            <label class="t" for="webwinkelkeur-tooltip-off">No</label>
        </div>
        <br class="clear" />

        <label for="adv_link"><?=$this->l('Integración JavaScript');?></label>
        <div class="margin-form">
            <label class="t" for="webwinkelkeur-javascript-on">
                <img src="../img/admin/enabled.gif" alt="" />
            </label>
            <input type="radio" name="javascript" id="webwinkelkeur-javascript-on" value="1" <?php if(Configuration::get('WEBWINKELKEUR_JAVASCRIPT')) echo 'checked'; ?> />
            <label class="t" for="webwinkelkeur-javascript-on">Sí</label>
            
            <label class="t" for="webwinkelkeur-javascript-off">
                <img src="../img/admin/disabled.gif" alt="" />
            </label>
            <input type="radio" name="javascript" id="webwinkelkeur-javascript-off" value="" <?php if(!Configuration::get('WEBWINKELKEUR_JAVASCRIPT')) echo 'checked'; ?> />
            <label class="t" for="webwinkelkeur-javascript-off">No</label>
        </div>
        <br class="clear" />

        <div class="margin-form">
            <input class="button" type="submit" value="<?=$this->l('Guardar');?>" />
        </div>
    </fieldset>
</form>

<?php if($invite_errors): ?>
<fieldset style="margin-top:1em;">
    <legend>
        <?php echo $this->l('Ha habido algunos errores al enviar las invitaciones'); ?>
    </legend>
    <table>
      <?php foreach($invite_errors as $invite_error): ?>
      <tr>
        <td style="padding-right:10px;"><?php echo date('d-m-Y H:i', $invite_error['time']); ?></td>
        <td>
          <?php if($invite_error['response']): ?>
          <?php echo htmlentities($invite_error['response'], ENT_QUOTES, 'UTF-8'); ?>
          <?php else: ?>
          No se ha podido contactar con el servidor de eValor.
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
</fieldset>
<?php endif; ?>
