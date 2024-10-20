<?php
/**
 * Popup contents
 * 
 * @package    Kobol Tools for Realms
 * @subpackage Kobol Accounts Helper
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 * 
 * Trailing vars:
 * @var string $message
 * @var string $error
 * @var string $pcontent
 */
?>

<p style="margin: 0;"><?= $settings->get("engine.website_name") ?></p>
<h1><?= $template->get_page_title(false)?></h1>

<? if($error): ?>
    <div class="framed_content state_ko">
        <i class="fa fa-warning"></i>
        <?= $error ?>
    </div>
<? endif; ?>

<? if($message): ?>
    <div class="framed_content state_ok">
        <i class="fa fa-info-circle"></i>
        <?= $message ?>
    </div>
<? endif; ?>

<div class="popup_contents">
    <?= $pcontent ?>
</div>
