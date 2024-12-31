{!! Form::open(array("url"=> $menu_route."/password_confirmed_action", "class"=>"form-horizontal","files" => true , "parsley-validate"=>"","novalidate"=>" ","id"=> "validatePasswordForm")) !!}		
@if(!empty($account_id))
<input type="hidden" name="account_id" value="{{ $account_id }}">
@endif
@if(!empty($subscription_id))
<input type="hidden" name="subscription_id" value="{{ $subscription_id }}">
@endif
<input type="hidden" name="action" value="{{ $action }}">

<div class="row mt-3">
    <div class="col">
        <div class="card">
            <div class="card-header">
                Confirm Password
            </div>
            <div class="card-body">
                
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="password"> Password </label>
                    </div>
                    <div class="col-md-9">
    					<input  name="password" id="password" type="password"  />
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>
{!! Form::close() !!}

<script type="text/javascript">
$(function() {
    password = new ej.inputs.TextBox({
	placeholder: "Password ",
    floatLabelType: 'Auto',
    type: 'password',
    });
    password.appendTo("#password");
});

$('#validatePasswordForm').on('submit', function(e) {
 	e.preventDefault();
    formSubmit("validatePasswordForm");
});
</script>