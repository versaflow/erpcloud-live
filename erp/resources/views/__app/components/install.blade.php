@extends( '__app.layouts.app'  )



@section('content')

<div  class="container mx-auto text-center">
<div class="col mt-0 pt-4">
	<h2>{{ $menu_name }}</h2>
</div>

<form role="form" class="form-horizontal" id="install-form" route="install">
<div id="install_tabs"></div>
<div id="company_tab" class="text-left">
    <div class="card mt-3">
        <div class="card-body">
            <input id="company" >
            <input id="contact" >
            <input id="email" >
            <input id="phone" >
            <input id="vat_number">
            <textarea id="address" ></textarea>
        </div>
    </div>
</div>
<div id="user_tab" style="display:none"  class="text-left">
    <div class="card mt-3">
        <div class="card-body">
            <input id="username" >
            <input id="password" type="password" >
        </div>
    </div>
</div>
<div id="billing_tab" style="display:none"  class="text-left">
    <div class="card mt-3">
        <div class="card-body">
            <textarea id="bank_details" ></textarea>
            <label for="vat_enabled" class="text-muted">Vat Enabled</label><br />
            <input id="vat_enabled"><br />
        </div>
    </div>
</div>
<div id="email_tab" style="display:none"  class="text-left">
    <div class="card mt-3">
        <div class="card-body">
            <input id="accounts_email" >
            <input id="sales_email" >
        </div>
    </div>
</div>

<div id="portal_tab" style="display:none"  class="text-left">
    <div class="card mt-3">
        <div class="card-body">
            <label for="logo" class="text-muted">Logo</label>
            <input id="logo"><br />
            <label for="enable_signup" class="text-muted">Enable Signup</label><br />
        </div>
    </div>
</div>

<div id="app_tab" style="display:none"  class="text-left">
    <div class="card mt-3">
        <div class="card-body">
            @foreach($apps as $app)
            <label for="app_{{$app->id}}" class="text-muted">{{$app->name}}</label><br />
            <input id="app_{{$app->id}}"><br />
            @endforeach
        </div>
    </div>
</div>

<div id="balances_tab" style="display:none"  class="text-left">
    <div class="card mt-3">
        <div class="card-body">
            <p>Opening Balances as at {{ date('Y-m-d') }}</p>
            @foreach($ledger_accounts as $ledger_account)
            <div class="row">
                <div class="col">
                    {{$ledger_account->name}}
                </div>
                <div class="col">
                    <input id="ledger_account_{{$ledger_account->id}}" name="ledger_account_{{$ledger_account->id}}">
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="mt-2 text-center">
<button type="submit" id="submitbtn">Submit</button>
</div>
</form>
@endsection
@push('page-scripts')

<script>
$(document).ready(function() {
username = new ej.inputs.TextBox({
	placeholder: "Username ",
    floatLabelType: 'Auto',
});
username.appendTo("#username");

password = new ej.inputs.TextBox({
	placeholder: "Password ",
    floatLabelType: 'Auto',
    type: 'password',
});
password.appendTo("#password");

company = new ej.inputs.TextBox({
	placeholder: "Company ",
    floatLabelType: 'Auto',
    required: true,
});
company.appendTo("#company");

contact = new ej.inputs.TextBox({
	placeholder: "Full Name ",
    floatLabelType: 'Auto',
});
contact.appendTo("#contact");

phone = new ej.inputs.TextBox({
	placeholder: "Mobile ",
    floatLabelType: 'Auto',
});
phone.appendTo("#phone");

email = new ej.inputs.TextBox({
	placeholder: "Email Address ",
    floatLabelType: 'Auto',
});
email.appendTo("#email");

vat_number = new ej.inputs.TextBox({
	placeholder: "Vat Number ",
    floatLabelType: 'Auto',
});
vat_number.appendTo("#vat_number");

address = new ej.inputs.TextBox({
	placeholder: "Address ",
    floatLabelType: 'Auto',
});
address.appendTo("#address");

logo = new ej.inputs.Uploader({
	placeholder: "Logo ",
    floatLabelType: 'Auto',
    autoUpload: false,
    multiple: false,
    
});
logo.appendTo("#logo");

bank_details = new ej.inputs.TextBox({
	placeholder: "Bank Details ",
    floatLabelType: 'Auto',
});
bank_details.appendTo("#bank_details");


vat_enabled = new ej.buttons.Switch({
	label: "Vat Enabled ",
    floatLabelType: 'Auto',
});
vat_enabled.appendTo("#vat_enabled");


bill_customers = new ej.buttons.Switch({
	label: "Bill Customers ",
    floatLabelType: 'Auto',
});
bill_customers.appendTo("#bill_customers");


accounts_email = new ej.inputs.TextBox({
	placeholder: "Accounts Email ",
    floatLabelType: 'Auto',
});
accounts_email.appendTo("#accounts_email");


sales_email = new ej.inputs.TextBox({
	placeholder: "Sales Email ",
    floatLabelType: 'Auto',
});
sales_email.appendTo("#sales_email");


disable_signup = new ej.buttons.Switch({
	label: "Disable Signup",
    floatLabelType: 'Auto',
});
disable_signup.appendTo("#disable_signup");

disable_partner_level = new ej.buttons.Switch({
	label: "Disable Partner Level",
    floatLabelType: 'Auto',
});
disable_partner_level.appendTo("#disable_partner_level");

@foreach($apps as $app)
app_{{$app->id}} = new ej.buttons.Switch({
	label: "Enable {{$app->name}} ",
    floatLabelType: 'Auto',
    @if($app->id == 1 || $app->id == 2 || $app->id == 9)
    disabled: true,
    checked: true,
    @endif
});
app_{{$app->id}}.appendTo("#app_{{$app->id}}");
@endforeach
 
hr_package= new ej.buttons.Switch({
	label: "Install HR package ",
    floatLabelType: 'Auto',
});
hr_package.appendTo("#hr_package");

@foreach($ledger_accounts as $ledger_account)
var {{'ledger_account_'.$ledger_account->id}}currency = new ej.inputs.NumericTextBox({
	format: 'R ###########.00',
	showSpinButton: false,
	decimals: 2,
	value: 0,
});
{{'ledger_account_'.$ledger_account->id}}currency.appendTo("#{{'ledger_account_'.$ledger_account->id}}");
@endforeach
submitbtn = new ej.buttons.Button({ cssClass: `e-info`}, '#submitbtn');



var install_tabs = new ej.navigations.Tab({
    items: [
        { header: { 'text': 'Company Info' }, content: '#company_tab'},
        { header: { 'text': 'User Settings' }, content: '#user_tab'},
        { header: { 'text': 'Billing Settings' }, content: '#billing_tab'},
        { header: { 'text': 'Email Settings' }, content: '#email_tab'},
        { header: { 'text': 'Portal Settings' }, content: '#portal_tab'},
        { header: { 'text': 'Opening Balances' }, content: '#balances_tab'},
    ],
	selectedItem: 0,
});
install_tabs.appendTo('#install_tabs');
});
$('#install-form').on('submit', function(e) {
    e.preventDefault();
    formSubmit('install-form');

});
</script>
@endpush

@push('page-styles')

<style>
body{
background-image: url(/assets/img/000.jpg);    
}
#page-wrapper{
		background-color: #fbfbfb;
		padding: 2%;
		margin-top:3%;
		margin-bottom:3%;
		box-shadow: 0 0 0.2cm rgba(0,0,0,0.3);
}
</style>
@endpush