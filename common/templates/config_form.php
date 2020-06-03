<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

?>

<form action="" method="POST">
    <input type="hidden" name="<?= $module->getName(); ?>" value="1">
    <fieldset>
        <legend>
            <img width="16" height="16" src="<?= $module->_path; ?>/logo.png">
            <?= sprintf($module->l('%s settings'), $module->getDisplayName()); ?>
        </legend>

        <label for="adv_link"><?= $module->l('Store ID'); ?></label>
        <div class="margin-form">
            <input id="adv_link" type="text" name="shop_id" value="<?= $module->escape($module->getConfigValue('SHOP_ID')); ?>" style="width:250px" required pattern="[0-9]+">
        </div>

        <label for="adv_link"><?= $module->l('API key'); ?></label>
        <div class="margin-form">
            <input id="adv_link" type="text" name="api_key" value="<?= $module->escape($module->getConfigValue('API_KEY')); ?>" style="width:250px">
            <p class="preference_description"><?= sprintf($module->l('This information can be found in your %s account.'), $module->getDisplayName()); ?></p>
        </div>
        <br class="clear">

        <label for="adv_link"><?= $module->l('JavaScript integration'); ?></label>
        <div class="margin-form">
            <label class="t" for="webwinkelkeur-javascript-on">
                <img src="../img/admin/enabled.gif" alt="">
            </label>
            <input type="radio" name="javascript" id="webwinkelkeur-javascript-on" value="1" <?= $module->getConfigValue('JAVASCRIPT') ? 'checked' : ''; ?>>
            <label class="t" for="webwinkelkeur-javascript-on"><?= $module->l('Yes'); ?></label>

            <label class="t" for="webwinkelkeur-javascript-off">
                <img src="../img/admin/disabled.gif" alt="">
            </label>
            <input type="radio" name="javascript" id="webwinkelkeur-javascript-off" value="" <?= !$module->getConfigValue('JAVASCRIPT') ? 'checked' : ''; ?>>
            <label class="t" for="webwinkelkeur-javascript-off"><?= $module->l('No'); ?></label>

            <p class="preference_description"><?= sprintf(html_entity_decode($module->l('Use the JavaScript integration to add the sidebar and tooltip to your site.<br>The settings for the sidebar and the tooltip can be changed in the %s%s Dashboard%s.'), ENT_QUOTES, 'UTF-8'), "<a href='https://{$module->getDashboardDomain()}/integration' target='_blank' rel='noopener'>", $module->getDisplayName(), '</a>'); ?></p>
        </div>
        <br class="clear">

        <label for="adv_link"><?= $module->l('Send invitations'); ?></label>
        <div class="margin-form">
            <label class="t" for="webwinkelkeur-invite-on">
                <img src="../img/admin/enabled.gif" alt="">
            </label>
            <input type="radio" name="invite" id="webwinkelkeur-invite-on" value="1" <?= $module->getConfigValue('INVITE') == 1 ? 'checked' : ''; ?>>
            <label class="t" for="webwinkelkeur-invite-on"><?= $module->l('Yes, for every order'); ?></label><br>

            <label class="t" for="webwinkelkeur-invite-first">
                <img src="../img/admin/enabled.gif" alt="">
            </label>
            <input type="radio" name="invite" id="webwinkelkeur-invite-first" value="2" <?= $module->getConfigValue('INVITE') == 2 ? 'checked' : ''; ?>>
            <label class="t" for="webwinkelkeur-invite-first"><?= $module->l("Yes, only for a customer's first order"); ?></label><br>

            <label class="t" for="webwinkelkeur-invite-off">
                <img src="../img/admin/disabled.gif" alt="">
            </label>
            <input type="radio" name="invite" id="webwinkelkeur-invite-off" value="" <?= !$module->getConfigValue('INVITE') ? 'checked' : ''; ?>>
            <label class="t" for="webwinkelkeur-invite-off"><?= $module->l('No'); ?></label>
        </div>

        <label for="adv_link"><?= $module->l('Invitation delay'); ?></label>
        <div class="margin-form">
            <input id="adv_link" type="number" required min="0" name="invite_delay" value="<?= $module->escape($module->getConfigValue('INVITE_DELAY', null, null, null, 3)); ?>" style="width:50px">
            <p class="preference_description">
                <?= $module->l('The invitation will be sent after the order has been marked as sent, and the configured amount of days has passed.'); ?>
            </p>
        </div>
        <br class="clear">

        <label for="adv_link"><?= $module->l('Minimum order number'); ?></label>
        <div class="margin-form">
            <input id="adv_link" type="number" required min="1" name="invite_first_order_id" value="<?= $module->escape($module->getConfigValue('INVITE_FIRST_ORDER_ID', null, null, null, $module->getLastOrderId() + 1)); ?>" style="width:50px">
            <p class="preference_description">
                <?= $module->l('Invitations will be sent starting from this order number. On installation, this is set to the next order number.'); ?>
            </p>
        </div>
        <br class="clear">

        <label for="adv_link"><?= $module->l('Rich snippet'); ?></label>
        <div class="margin-form">
            <label class="t" for="webwinkelkeur-rich-snippet-on">
                <img src="../img/admin/enabled.gif" alt="">
            </label>
            <input type="radio" name="rich_snippet" id="webwinkelkeur-rich-snippet-on" value="1" <?= $module->getConfigValue('RICH_SNIPPET') ? 'checked' : ''; ?>>
            <label class="t" for="webwinkelkeur-rich-snippet-on"><?= $module->l('Yes'); ?></label>

            <label class="t" for="webwinkelkeur-rich-snippet-off">
                <img src="../img/admin/disabled.gif" alt="">
            </label>
            <input type="radio" name="rich_snippet" id="webwinkelkeur-rich-snippet-off" value="" <?= !$module->getConfigValue('RICH_SNIPPET') ? 'checked' : ''; ?>>
            <label class="t" for="webwinkelkeur-rich-snippet-off"><?= $module->l('No'); ?></label>

            <p class="preference_description"><?= sprintf($module->l('Add a %srich snippet%s to the footer. This allows Google to show your reviews in search results. Use this at your own risk.'), '<a href="https://support.google.com/webmasters/answer/99170?hl=' . $module->context->language->iso_code . '" target="_blank" rel="noopener">', '</a>'); ?></p>
        </div>
        <br class="clear">

        <div class="margin-form">
            <label class="t">
                <input type="checkbox" name="limit_order_data" value="1" <?= $module->getConfigValue('LIMIT_ORDER_DATA') ? 'checked ' : ''; ?>/>
                <?= sprintf($module->l('Do not send extended order data to %s'), $module->getDisplayName()); ?>
            </label>
            <p class="preference_description">
                <?= sprintf($module->l('By default we send details about the customer and the ordered products to our API, so that we can offer additional features. If you check this box, that will not happen, and not all %s features may be available.'), $module->getDisplayName()); ?>
            </p>
        </div>
        <br class="clear">

        <div class="margin-form">
            <input class="button" type="submit" value="<?= $module->l('Save changes'); ?>">
        </div>
    </fieldset>
</form>

<?php if ($invite_errors): ?>
<fieldset style="margin-top:1em;">
    <legend>
        <?= $module->l('Errors that occurred during the sending of invitations'); ?>
    </legend>
    <table>
        <?php foreach ($invite_errors as $invite_error): ?>
        <tr>
            <td style="padding-right:10px;"><?= date('d-m-Y H:i', $invite_error['time']); ?></td>
            <td>
                <?php if ($invite_error['response']): ?>
                <?= htmlentities($invite_error['response'], ENT_QUOTES, 'UTF-8'); ?>
                <?php else: ?>
                <?= $module->l('An unknown error occurred.'); ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</fieldset>
<?php endif; ?>
