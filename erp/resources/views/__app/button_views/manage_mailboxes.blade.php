{!! Form::open(array("url"=> "postmailbox", "class"=>"form-horizontal","id"=> "postmailbox")) !!}	
<input type="hidden" name="domain" id="domain" value="{{ $domain }}">
<input type="hidden" name="subscription_id" id="subscription_id" value="{{ $subscription_id }}">
<input type="hidden" name="account_id" id="account_id" value="{{ $account_id }}">
<input type="hidden" name="mail_list" id="mail_list" value="{{ $mail_list }}">
<input type="hidden" name="api" id="api" value="{{ $api }}">

@if(count($mailbox_accounts) > 0)
<div class="row" >
    <div class="col">
        <div class="card">
            <div class="card-header">
                Manage Email Accounts<br>
                <p>Enter a password for the mailbox account you want to update. The email credentials will be sent on save.</p>
            </div>
            <div class="card-body">
                @foreach($mailbox_accounts as $mailbox)
              
                <div class="row">
                    <div class="col-md-5 text-left align-self-center">
                    <label for="{{$mailbox}}"> {{$mailbox.'@'.$domain}} </label>
                    </div>
                    <div class="col-md-5">
                        <input id="{{$mailbox}}" name="{{$mailbox}}"/>
                    </div>
                    <div class="col-md-2 text-right">
                        <button type="button" class="mt-3 e-btn e-small mailbox-send" data-attr-user="{{$mailbox}}" title="Send Details"><i class="fas fa-envelope"></i></button>
                        <button type="button" class="mt-3 e-btn e-small mailbox-del" data-attr-user="{{$mailbox}}" title="Delete"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                @endforeach
            </div>     
        </div>
    </div>
</div>
@endif

<div class="row" >
    <div class="col">
        <div class="card">
            <div class="card-header">
                Create New Email Account
                <p>Enter the username without {{ '@'.$domain }}.</p>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 text-left align-self-center">
                        <input id="mailbox_username" name="mailbox_username"/>
                    </div>
                    <div class="col-md-6">
                        <input id="mailbox_password" name="mailbox_password" type="password" />
                    </div>
                </div>
            </div>     
        </div>
    </div>
</div>

{!! Form::close() !!}

<script type="text/javascript">
$('.mailbox-del').click(function(e){
    var $tr = $(this).closest('.row');
	e.preventDefault();
     $.ajax({
       type: 'post',
       url: '/postmailbox',
       data: {mailboxuser_delete: $(this).attr('data-attr-user'),subscription_id: $("#subscription_id").val(),domain: $("#domain").val(), account_id: $("#account_id").val(), api: $("#api").val()},
       success: function(data){
           //console.log(data);
        toastNotify(data.message, data.status);
        if(data.status == 'success'){
        $tr.hide();
        }
       },
       error: function(jqXHR, textStatus, errorThrown) {
        if(error_msg == '')
        error_msg = textStatus;
        toastNotify(error_msg, 'error');
       }
    });
    return false;
});

$('.mailbox-send').click(function(e){
    var $tr = $(this).closest('.row');
	e.preventDefault();

    sidebarform('mailbox-send','manage_mailbox_send/'+$("#subscription_id").val()+'?domain='+$("#domain").val()+'&mailboxuser_send='+$(this).attr('data-attr-user'));
	/*
     $.ajax({
       type: 'post',
       url: '/postmailbox',
       data: {mailboxuser_send: $(this).attr('data-attr-user'),subscription_id: $("#subscription_id").val(),domain: $("#domain").val(), account_id: $("#account_id").val(), api: $("#api").val()},
       success: function(data){
           //console.log(data);
        toastNotify(data.message, data.status);
       
       },
       error: function(jqXHR, textStatus, errorThrown) {
        if(error_msg == '')
        error_msg = textStatus;
        toastNotify(error_msg, 'error');
       }
    });
    */
    return false;
});
    

$(document).ready(function() {
    
	
    mailbox_username = new ej.inputs.TextBox({
		placeholder: "Username",
        floatLabelType: 'Auto',
	});
	mailbox_username.appendTo("#mailbox_username");
	
    mailbox_password = new ej.inputs.TextBox({
		placeholder: "Password",
        floatLabelType: 'Auto',
	});
	mailbox_password.appendTo("#mailbox_password");
	
    @foreach($mailbox_accounts as $mailbox)
		
    {{$mailbox}}password = new ej.inputs.TextBox({
        placeholder: "Password ",
        floatLabelType: 'Auto',
        type: 'password',
    });
    {{$mailbox}}password.appendTo("#{{$mailbox}}");
    @endforeach
});

$('#postmailbox').on('submit', function(e) {
	e.preventDefault();
   formSubmit("postmailbox");
   
});

</script>