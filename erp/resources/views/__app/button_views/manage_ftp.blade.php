{!! Form::open(array("url"=> "postftp", "class"=>"form-horizontal","id"=> "postftp")) !!}	
<input type="hidden" name="domain" id="domain" value="{{ $domain }}">

@if(count($ftp_accounts) > 0)
<div class="row mt-3" >
    <div class="col">
        <div class="card">
            <div class="card-header">
                Manage FTP Accounts<br>
                <p>Enter a password for the ftp account you want to update. The email credentials will be sent on save.</p>
            </div>
            <div class="card-body">
                @foreach($ftp_accounts as $ftp)
              
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="{{$ftp->username}}"> {{$ftp->fulluser}} </label>
                    </div>
                    <div class="col-md-3">
                        <input id="{{$ftp->username}}" name="{{$ftp->username}}"/>
                    </div>
                    <div class="col-md-5">
                        <input id="{{$ftp->username}}homedir" name="{{$ftp->username}}homedir" value="{{$ftp->homedir}}"/>
                    </div>
                    <div class="col-md-1 text-right">
                        <button type="button" class="mt-3 e-btn e-small ftp-del" data-attr-user="{{$ftp->username}}" ><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                @endforeach
            </div>     
        </div>
    </div>
</div>
@endif

<div class="row mt-3" >
    <div class="col">
        <div class="card">
            <div class="card-header">
                Create New FTP Account
                <p>Enter the username without {{ '@'.$domain }}.</p>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                        <input id="ftp_username" name="ftp_username"/>
                    </div>
                    <div class="col-md-3">
                        <input id="ftp_password" name="ftp_password" type="password" />
                    </div>
                    <div class="col-md-6">
                        <input id="ftp_homedir" name="ftp_homedir" />
                    </div>
                </div>
            </div>     
        </div>
    </div>
</div>

{!! Form::close() !!}

<script type="text/javascript">
$('.ftp-del').click(function(e){
    var $tr = $(this).closest('.row');
	e.preventDefault();
     $.ajax({
       type: 'post',
       url: '/postftp',
       data: {ftpuser_delete: $(this).attr('data-attr-user'),domain: $("#domain").val()},
       success: function(data){
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
    

$(document).ready(function() {
    
	
    ftp_homedir = new ej.inputs.TextBox({
		placeholder: "Home Dir",
        floatLabelType: 'Auto',
	});
	ftp_homedir.appendTo("#ftp_homedir");
	
    ftp_username = new ej.inputs.TextBox({
		placeholder: "Username",
        floatLabelType: 'Auto',
	});
	ftp_username.appendTo("#ftp_username");
	
    ftp_password = new ej.inputs.TextBox({
		placeholder: "Password",
        floatLabelType: 'Auto',
	});
	ftp_password.appendTo("#ftp_password");
	
    @foreach($ftp_accounts as $ftp)
    
	{{$ftp->username}}homedir = new ej.inputs.TextBox({
		placeholder: "Home Dir",
        floatLabelType: 'Auto',
	});
	{{$ftp->username}}homedir.appendTo("#{{$ftp->username}}homedir");
		
    {{$ftp->username}}password = new ej.inputs.TextBox({
        placeholder: "Password ",
        floatLabelType: 'Auto',
        type: 'password',
    });
    {{$ftp->username}}password.appendTo("#{{$ftp->username}}");
    @endforeach
});

$('#postftp').on('submit', function(e) {
	e.preventDefault();
   formSubmit("postftp");
   
});

</script>