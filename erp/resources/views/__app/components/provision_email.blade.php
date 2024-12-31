<div style="padding:20px">
	<div class="form-group">
	    <label for="emailaddress" class="col-sm-2 control-label">To Address</label>
	    <div class="col-sm-10">
	    <input type="email" class="form-control" id="emailaddress" name="emailaddress" placeholder="name@example.com" value="{{ $emailaddress }}">
	    </div>
    </div>
    <div class="form-group">
    	<label for="ccemailaddress" class="col-sm-2 control-label">CC Address</label>
	    <div class="col-sm-10">
    	<input type="email" class="form-control" id="ccemailaddress" name="ccemailaddress" placeholder="name@example.com" value="{{ $ccemailaddress }}">
    	</div>
    </div>
    <div class="form-group">
	    <label for="subject" class="col-sm-2 control-label">Subject</label>
	    <div class="col-sm-10">
	    <input type="text" class="form-control" id="subject" name="subject" placeholder="Subject" value="{{ $subject }}">
    	</div>
    </div>
    
    <div class="form-group">
	    <div class="col-sm-12">
	    <textarea class='form-control input-sm editor' rows='5' name='messagebox' id='messagebox{{ $message_box_id }}'>{{ $message }}</textarea>
	    </div>
    </div>
    @if(!empty($provision_id))
    <input type="hidden" name="provision_id" value="{{ $provision_id }}" />
    @endif
    <input type="hidden" name="account_id" value="{{ $account_id }}" />
    <input type="hidden" name="partner_company" value="{{ $partner_company }}" />
    <input type="hidden" name="partner_email" value="{{ $partner_email }}" />
</div>
<style>.control-label{font-weight:bold;}</style>
@if(empty($exclude_script))
<script>

    emailaddress = new ej.inputs.TextBox({
    placeholder: 'name@example.com',
    value:'{{ $emailaddress }}',
    });
    emailaddress.appendTo("#emailaddress");
    
    ccemailaddress = new ej.inputs.TextBox({
    placeholder: 'name@example.com',
    value:'{{ $ccemailaddress }}',
    });
    ccemailaddress.appendTo("#ccemailaddress");
    
    subject = new ej.inputs.TextBox({
    placeholder: 'Subject',
    value:'{{ $subject }}',
    });
    subject.appendTo("#subject");;
</script>
@endif