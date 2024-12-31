<form class='form-horizontal' id ='menuPermission' action='/permissions_save'>
<input type="hidden" name="is_module" value="{{ $is_module }}" />
<div class="table-responsive">
		<table class="table table-striped " id="table">
		<thead class="no-border">
  <tr>
	<th>Group </th>
	@foreach($tasks as $item => $val)
		@if($val == 'Cancel')
		<th>Cancel/Status </th>
		@else
		<th>{{ $val }} </th>
		@endif
	@endforeach
  </tr>
</thead>  
<tbody class="no-border-x no-border-y">	
@foreach ($access as $gp)

<tr>
	<td>
	<input type="hidden" name="role_id[]" value="{{ $gp['role_id'] }}" />
	{{ $gp['group_name'] }} 
	</td>
	@foreach ($tasks as $item => $val)

		<td>
		<label>
	
			<input name="{{ $item }}[{{ $gp['role_id'] }}]" id="{{ $item }}{{ $gp['role_id'] }}" 
			class="c{{ $gp['role_id'] }}" type="checkbox" value="1" 
				@if ($gp[$item])
				checked="checked"
				@endif 
				@if($gp['role_id'] > 10 && ($item=='is_add' || $item=='is_export'))
				 disabled="disabled" readonly="readonly"
				@endif
		
		</label>	
		</td>
	@endforeach
</tr>
@endforeach
 
  </tbody>
</table>	
</div>

<input name="id" type="hidden" id="id" value="{{ $id }}" />	
<div ref="component" class="field form-group has-feedback formio-component formio-component-button formio-component-submit float-right mr-2 form-group" >
<button lang="en" type="submit"  class="btn btn-primary ui button primary float-right mr-2" ref="button">
Submit
</button>
</div>
</form>

<script type="text/javascript">
$('#menuPermission').on('submit', function(e) {
	e.preventDefault();
    formSubmit("menuPermission");
});
$(function() {
	$('#menuPermission input[type=checkbox]').each(function (i,el) {
		var el_id = 'switch'+$(el).attr('id');
		
		if($(el).prop('disabled')){
		
			//console.log(el_id);
		
			window[el_id] = new ej.buttons.CheckBox({
				checked: false,
				disabled: true,
			});
			
			window[el_id].appendTo("#"+$(el).attr('id'));
		}else if($(el).is(':checked')){
			window[el_id] = new ej.buttons.CheckBox({
				checked: true,
			});
			
			window[el_id].appendTo("#"+$(el).attr('id'));
		}else{
			window[el_id] = new ej.buttons.CheckBox({
				checked: false,
			});
			
			window[el_id].appendTo("#"+$(el).attr('id'));
		}
	
	});
});
</script>