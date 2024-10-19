
function SSO_telegram_login(data)
{
    let url    = $_FULL_ROOT_PATH + '/single_signon/scripts/telegram.php';
    let params = {
        mode:       'login',
        auth_date:  data.auth_date,
        first_name: data.first_name,
        hash:       data.hash,
        id:         data.id,
        last_name:  data.last_name,
        photo_url:  data.photo_url,
        username:   data.username
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
        mode:       'register',
        auth_date:  data.auth_date,
        first_name: data.first_name,
        hash:       data.hash,
        id:         data.id,
        last_name:  data.last_name,
        photo_url:  data.photo_url,
        username:   data.username
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
        mode:       'link',
        auth_date:  data.auth_date,
        first_name: data.first_name,
        hash:       data.hash,
        id:         data.id,
        last_name:  data.last_name,
        photo_url:  data.photo_url,
        username:   data.username
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
