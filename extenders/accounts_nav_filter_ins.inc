<?php
/**
 * Inserts filters to the accounts browser
 * 
 * @package    BardCanvas
 * @subpackage single_signon
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

global $where_line, $search_telegram_filter, $where_lines;

switch( $search_telegram_filter )
{
    case 'any':
        $where_lines[] = "
            account.id_account in (
                select aep.id_account from account_engine_prefs aep
                where aep.name = 'telegram:user_id'
            ) 
        ";
        break;
    case 'none':
        $where_lines[] = "
            account.id_account not in (
                select aep.id_account from account_engine_prefs aep
                where aep.name = 'telegram:user_id'
            ) 
        ";
        break;
}
