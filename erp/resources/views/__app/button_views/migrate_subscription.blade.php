<form class='form-horizontal' id ='migrateSubscription' action='/subscription_migrate'>
<input type="hidden" name="subscription_id" value="{{ $subscription_id }}">
<div class="card card-body bg-light mt-5">
@if(session('role_level') == 'Admin')
<div class="row mt-1 mb-1" >
    <label for="migration_document" class=" control-label col-md-3 text-left">Migration request </label>
    <div class="col-md-9">
        <input  name="migration_document" id="migration_document" type="file" aria-label="files" />
    
    </div>
</div>
@endif

@if(check_access('1,3,7'))
<div class="row mt-3" >
    <label for="manager_override" class=" control-label col-md-3 text-left">Manager Override </label>
    <div class="col-md-9">
        <input name="manager_override" id="manager_override" type="checkbox" value="1" >
    
    </div>
</div>
@endif
    <div class="row mt-1 mb-5">
        <div class="col">
            <label for="new_product_id">Select product to migrate</label>
            @if($subscription->provision_type == 'hosting')
            <p>IMPORTANT: Migrating your hosting package to a sitebuilder account, will delete your hosting account details.<br>Please make sure you have backups of your hosting account, this cannot be undone. <br> Your files, databases and emails will be deleted and your domain will be moved to the sitebuilder server.</p>
            @endif
            <select name="new_product_id" id="new_product_id" required>
                <option></option>
                @foreach($available_products as $product)
                <option value="{{ $product->id }}">{{ ucwords(str_replace("_"," ",$product->code)) }} | Current Price Excl: R {{ currency($product->price_current) }} | New Price Excl: R {{ currency($product->price_new) }} | Difference Excl: R {{ currency($product->price_diff) }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
</form>
<script>
    var migrate_select = new ej.dropdowns.DropDownList({
    	placeholder: 'Select product',
    });
    migrate_select.appendTo("#new_product_id");
    
    var uploadObj = new ej.inputs.Uploader({
        autoUpload: false,
        multiple: false,
    });
    uploadObj.appendTo("#migration_document");
    @if(check_access('1,3,7'))
    var checkbox = { label: 'Manager override.' };
	var manager_override = new ej.buttons.Switch(checkbox);
	manager_override.appendTo("#manager_override");
	@endif
    
    $('#migrateSubscription').on('submit', function(e) {
    	e.preventDefault();
    	formSubmit('migrateSubscription');
    });
</script>