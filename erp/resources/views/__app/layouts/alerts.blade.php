@if($account->status == 'Disabled')
<div id="top-alert" class="alert alert-danger mb-0 text-center small" role="alert" style="z-index: 100000;">
    <button type="button" class="close" data-dismiss="alert">×</button>
    <strong>Account Disabled</strong> Your account has been disabled, all services is currently deactivated, in order to enable your services please settle your account balance.
</div>
@endif