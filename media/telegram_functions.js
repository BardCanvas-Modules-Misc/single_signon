
function SSO_telegram_login(data)
{
    let url    = $_FULL_ROOT_PATH + '/single_signon/scripts/telegram.php';
    let params = {
        mode: 'login',
        data: data
    };
    
    $.blockUI(blockUI_default_params);
    $.get(url, params, function(response)
    {
        if( response !== 'OK' )
        {
            $.unblockUI();
            throw_notification(response, 'error');
            return;
        }
        
        let goto = $('#login_form input[name="goto"]').val();
        if( goto ) location.href = goto;
    });
}

function SSO_telegram_register(data)
{
    let url    = $_FULL_ROOT_PATH + '/single_signon/scripts/telegram.php';
    let params = {
        mode: 'register',
        data: data
    };
    
    $.blockUI(blockUI_default_params);
    $.get(url, params, function(response)
    {
        if( response !== 'OK' )
        {
            $.unblockUI();
            throw_notification(response, 'error');
            return;
        }
        
        let goto = $('#register_form input[name="redir_url"]').val();
        if( goto === undefined || goto === null || goto === '' )
            goto = $_FULL_ROOT_PATH + '/accounts/edit_account.php';
            
        location.href = goto;
    });
}

function SSO_telegram_link(data)
{
    let url    = $_FULL_ROOT_PATH + '/single_signon/scripts/telegram.php';
    let params = {
        mode: 'link',
        data: data
    };
    
    $.blockUI(blockUI_default_params);
    $.get(url, params, function(response)
    {
        if( response !== 'OK' )
        {
            $.unblockUI();
            throw_notification(response, 'error');
            return;
        }
        
        location.href = '/accounts/edit_account.php';
    });
}

function SSO_telegram_unlink()
{
    if( ! confirm($_GENERIC_CONFIRMATION) ) return;
    
    let url = $_FULL_ROOT_PATH + '/single_signon/scripts/telegram.php?mode=unlink';
    
    $.blockUI(blockUI_default_params);
    $.get(url, function(response)
    {
        if( response !== 'OK' )
        {
            $.unblockUI();
            throw_notification(response, 'error');
            return;
        }
        
        location.href = '/accounts/edit_account.php';
    });
}
