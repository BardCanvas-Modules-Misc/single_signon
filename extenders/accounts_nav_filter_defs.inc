<?php
/**
 * Adds filters to the accounts browser
 * 
 * @package    BardCanvas
 * @subpackage single_signon
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

global $custom_filters, $search_telegram_filter;

$lang = $this_module->language->accounts_browser_filters;

$ignore_class = $any_class = $with_sso_class = $with_channel_class = "";

if( empty($search_telegram_filter) )    $ignore_class = "state_active";
if( $search_telegram_filter == "any" )  $any_class    = "state_active";
if( $search_telegram_filter == "none" ) $none_class   = "state_active";

$custom_filters[] = "
    <span class='framed_content inlined nowrap kbl_twitch' style='padding: 5px'>
        
        <input type='hidden' name='search_telegram_filter' id='search_telegram_filter' 
               value='$search_telegram_filter'>
        
        {$lang->caption}
        
        <span class='framed_content inlined pseudo_link nowrap sso_tg_ignore $ignore_class'
              data-value='' style='margin-right: 5px'
              onclick=\"$('#search_telegram_filter').val($(this).attr('data-value')); $('#filter_form').submit();\">
            {$lang->ignore}
        </span>
        
        <span class='framed_content inlined pseudo_link nowrap sso_tg_any $any_class'
              data-value='any' style='margin-right: 5px'
              onclick=\"$('#search_telegram_filter').val($(this).attr('data-value')); $('#filter_form').submit();\">
            {$lang->any}
        </span>
        
        <span class='framed_content inlined pseudo_link nowrap sso_tg_none $none_class'
              data-value='none' style='margin-right: 5px'
              onclick=\"$('#search_telegram_filter').val($(this).attr('data-value')); $('#filter_form').submit();\">
            {$lang->none}
        </span>
        
    </span>
";
