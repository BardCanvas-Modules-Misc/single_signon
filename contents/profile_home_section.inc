<?php
/**
 * Accounts extender: shows SSO details to self and admins 
 * 
 * @package    Crypto Garuda
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 * 
 * @var account $author
 */

use hng2_base\account;

global $template, $settings, $account;

$author = $template->get("user_profile_account");

if( $account->id_account != $author->id_account &&
    $account->level < $config::MODERATOR_USER_LEVEL )
        return;

if( empty($author->engine_prefs["telegram:user_id"]) && 
    empty($author->engine_prefs["twitch:user_id"])   && 
    empty($author->engine_prefs["google:token"])        ) return;
?>

<section id="sso_details">
    <h2>
        <i class="fa fa-gear"></i>
        <?= $this_module->language->user_summary->title ?>
    </h2>
    <? $class = $account->id_account == $author->id_account ? "" : "state_highlight"; ?>
    <div class="framed_content <?= $class ?>">
        
        <? if( ! empty($author->engine_prefs["telegram:user_id"]) ): ?>
            <p>
                <i class="fa fa-telegram fa-fw"></i>
                <? if( $account->level >= $config::MODERATOR_USER_LEVEL) : ?>
                    <code class="pseudo_link clipboard-copy"
                          data-clipboard-text="<?= $author->engine_prefs["telegram:user_id"] ?>"
                          ><?= $author->engine_prefs["telegram:user_id"] ?><i class="fa fa-copy fa-fw"></i></code>
                <? endif; ?>
                <? if( empty($author->engine_prefs["telegram:username"]) ): ?>
                    <?= $this_module->language->user_summary->telegram ?>
                <? else: ?>
                    <a target="_blank" href="https://t.me/<?= $author->engine_prefs["telegram:username"] ?>"
                    ><?= $author->engine_prefs["telegram:username"] ?></a>
                <? endif; ?>
            </p>
        <? endif; ?>
        
        <? if( ! empty($author->engine_prefs["google:token"]) ): ?>
            <p>
                <i class="fa fa-brands fa-google fa-fw"></i>
                <?= $this_module->language->user_summary->google ?>
            </p>
        <? endif; ?>
        
        <? if( ! empty($author->engine_prefs["twitch:user_id"]) ): ?>
            <p>
                <i class="fa fa-brands fa-twitch fa-fw"></i>
                <? if( $account->level >= $config::MODERATOR_USER_LEVEL) : ?>
                    <code class="pseudo_link clipboard-copy"
                          data-clipboard-text="<?= $author->engine_prefs["twitch:user_id"] ?>"
                          ><?= $author->engine_prefs["twitch:user_id"] ?><i class="fa fa-copy fa-fw"></i></code>
                <? endif; ?>
                <? if( empty($author->engine_prefs["twitch:username"]) ): ?>
                    <?= $this_module->language->user_summary->twitch ?>
                <? else: ?>
                    <a target="_blank" href="https://twitch.tv/<?= $author->engine_prefs["twitch:username"] ?>"
                    ><?= $author->engine_prefs["twitch:username"] ?></a>
                <? endif; ?>
            </p>
        <? endif; ?>
        
    </div>
</section>
