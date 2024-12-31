@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
    
	
@endif

@section('content')
<form id="fieldForm" action="/module_manager/saveformfield/{{ $field->module_id }}/{{ $field->id }}">
<div id="page-wrapper" class="container mx-auto">

	@if(str_contains($field->field_type,'select'))

	<div id="dropdown_custom">
		<div class="form-group">
			<label>Options</label><br>
			<input id="datalist" >
		</div>
		
		@if(!empty($conf['datalist']))
		<div class="form-group">
			<input id="datasort" name="datasort">
		</div>
		@endif
	</div>
	<div id="dropdown_function">
		<div class="form-group">
			<label>Function name</label><br>
			<input id="select_function" >
		</div>
		<div class="form-group">
			<label>Dependent Fields</label><br>
			<input id="dependent_fields" >
		</div>
	</div>
	<div id="dropdown_database">
		
		<div class="form-group">
			<label>Table</label><br>
			<input id="db_table" />
		</div>
		<div class="form-group">
			<label>Key Field</label><br>
			<input id="db_key" />
		</div>
		
		<div class="form-group">
			<label>Display Fields</label><br>
			<input id="display" />
		</div>
		<div class="form-group">
			<label>Where Filter. eg, account_id=1</label><br>
			<input id="db_where" />
		</div>
		<div class="form-group">
			<label>Order By</label><br>
			<input id="orderby" />
		</div>
		<div class="form-group">
			<label>SQL Filter</label><br>
			<input id="sql_filter" />
		</div>
		<div class="form-group">
			<label>Account Filter</label><br>
			<input name="account_filter" id="account_filter" type="checkbox" value="1" >
		</div>
		<div class="form-group">
			<label>Unique Filter</label><br>
			<input name="unique_filter" id="unique_filter" type="checkbox" value="1" >
		</div>
	</div>
	<div id="dropdown_results">
		
		<div class="form-group">
			<label>Select Multiple</label><br>
			<input name="select_multiple" id="select_multiple" type="checkbox" value="1" >
		</div>
		<div class="form-group">
			<label>Allow Custom Admin Only</label><br>
			<input name="allow_custom_admin" id="allow_custom_admin" type="checkbox" value="1" >
		</div>
		<div class="form-group">
			<label>Allow Custom</label><br>
			<input name="allow_custom" id="allow_custom" type="checkbox" value="1" >
		</div>
		<div class="form-group">
			<label>Disable gridsort, sorts grid alpahabetically not by order of select options</label><br>
			<input name="grid_sort_alpha" id="grid_sort_alpha" type="checkbox" value="1" >
		</div>
	</div>
	@endif	
	
	@if($field->field_type == 'file' || $field->field_type == 'image')
	<div class="form-group">
		<label>Upload Type</label><br>
		<select id="upload_type" name="upload_type" >
			<option value="image">Image</option>
			<option value="file">File</option>
		</select>
	</div>
	<div>
		<div class="form-group">
			<p>Comma seperated file extensions</p>
			@if(!empty($conf['allowed_file_extensions']))
			<input id="allowed_file_extensions" name="allowed_file_extensions" value="{{ $conf['allowed_file_extensions'] }}" >
			@else
			<input id="allowed_file_extensions" name="allowed_file_extensions"  >
			@endif
		</div>
		<div class="form-group">
			<label>Allow Multiple</label><br>
			<input name="allow_multiple" id="allow_multiple" type="checkbox" value="1" >
		</div>
	</div>
	@endif
</div>
</form>
@endsection
@push('page-scripts')

<script type="text/javascript">	
fieldsDatasource =  {!! json_encode($field_list) !!};
$(document).ready(function() {
	ej.base.enableRipple(true);
	@if(str_contains($field->field_type,'select'))
		show_field_groups();
	
		datalist = new ej.dropdowns.MultiSelect({
			dataSource: {!! json_encode($conf['datalist']) !!},
			@if($conf['datalist'])
			value: {!! json_encode($conf['datalist']) !!},
		 	@endif
			htmlAttributes: {name: 'datalist[]'}, 
		    floatLabelType: 'Auto',
		 	allowCustomValue: true,
		 	created: function(){
				@if(empty($conf['dropdown_type']))
				$(function() {
				   datalist.focusIn();
				});
				@endif
		 	},
		 	change:function(){
		 		
				@if(!empty($conf['datalist']))
		 		if(datalist.value.length != datasort.dataSource.length){
				 	datasort.dataSource = datalist.value;	
				 	datasort.refresh();
		 		}
		 		@endif
		 	}
		});
		datalist.appendTo("#datalist");
		@if(!empty($conf['datalist']))
		datasort = new ej.dropdowns.ListBox({
			dataSource: {!! json_encode($conf['datalist']) !!},
			drop:function(args){
				datalist.value = args.source.currentData; 
			},
			allowDragAndDrop: true,
		});
		datasort.appendTo("#datasort");
		@endif
	    
		tables = new ej.dropdowns.DropDownList({
		    floatLabelType: 'Auto',
			dataSource: {!! json_encode($tables) !!},
			allowFiltering: true,
			filterBarPlaceholder: 'Select Table',
			@if($conf['db_table'])
			value: "{{ $conf['db_table'] }}",
			@endif
			change: function(e){
				set_field_lists();
			}
		});
		tables.appendTo("#db_table");
	
		key = new ej.dropdowns.DropDownList({
		    floatLabelType: 'Auto',
			dataSource: fieldsDatasource,
			allowFiltering: true,
			filterBarPlaceholder: 'Select Lookup Key',
			@if($conf['db_key'])
			value: "{{ $conf['db_key'] }}",
		 	@endif
			@if(!$conf['db_table'])
			enabled: false,
			@endif
		});
		key.appendTo("#db_key");
		
	
	    
		where = new ej.inputs.TextBox({
	        floatLabelType: 'Auto',
	        value: "{!! $conf['db_where'] !!}"
	    });
	    where.appendTo("#db_where");
	    
		sql_filter = new ej.dropdowns.DropDownList({
		    floatLabelType: 'Auto',
			dataSource: {!! json_encode($sql_filters) !!},
			allowFiltering: true,
			filterBarPlaceholder: 'Select Table',
			@if($conf['sql_filter'])
			value: "{{ $conf['sql_filter'] }}",
			@endif
		});
		sql_filter.appendTo("#sql_filter");

		display = new ej.dropdowns.MultiSelect({
			mode: 'Box',
			hideSelectedItem: false,
			htmlAttributes: {name: 'display[]'}, 
		    floatLabelType: 'Auto',
			dataSource: fieldsDatasource,
			allowFiltering: true,
			filterBarPlaceholder: 'Select Display Fields',
			@if($conf['display'])
				value: {!! json_encode($conf['display']) !!},
		 	@endif
			@if(!$conf['db_table'])
				enabled: false,
			@endif
		});
		display.appendTo("#display");
		
		dependent_fields = new ej.dropdowns.MultiSelect({
			dataSource: {!! json_encode($conf['dependent_fields']) !!},
			@if($conf['dependent_fields'])
			value: {!! json_encode($conf['dependent_fields']) !!},
		 	@endif
			htmlAttributes: {name: 'dependent_fields[]'}, 
		    floatLabelType: 'Auto',
		 	allowCustomValue: true,
		});
		dependent_fields.appendTo("#dependent_fields");
	
		orderby = new ej.dropdowns.MultiSelect({
			mode: 'Box',
			hideSelectedItem: false,
			htmlAttributes: {name: 'orderby[]'},
		    floatLabelType: 'Auto',
			dataSource: fieldsDatasource,
			allowFiltering: true,
			filterBarPlaceholder: 'Select Order By',
			@if($conf['orderby'])
			value: {!! json_encode($conf['orderby']) !!},
		 	@endif
			@if(!$conf['db_table'])
			enabled: false,
			@endif
		});
		orderby.appendTo("#orderby");
		
		@if(!empty($conf['select_multiple']) && $conf['select_multiple'] == 1)
		var checkbox = { label: 'Select Multiple', checked: true };
		@else
		var checkbox = { label: 'Select Multiple' };
		@endif
	
		var select_multiple = new ej.buttons.Switch(checkbox);
		select_multiple.appendTo("#select_multiple");
		
		@if(!empty($conf['allow_custom']) && $conf['allow_custom'] == 1)
		var checkbox = { label: 'Allow Custom', checked: true };
		@else
		var checkbox = { label: 'Allow Custom' };
		@endif
	
		var allow_custom = new ej.buttons.Switch(checkbox);
		allow_custom.appendTo("#allow_custom");
		
		
		@if(!empty($conf['grid_sort_alpha']) && $conf['grid_sort_alpha'] == 1)
		var checkbox = { label: 'Disable gridsort, sorts grid alpahabetically not by order of select options', checked: true };
		@else
		var checkbox = { label: 'Disable gridsort, sorts grid alpahabetically not by order of select options' };
		@endif
	
		var grid_sort_alpha = new ej.buttons.Switch(checkbox);
		grid_sort_alpha.appendTo("#grid_sort_alpha");
		
		@if(!empty($conf['allow_custom_admin']) && $conf['allow_custom_admin'] == 1)
		var checkbox = { label: 'Allow Custom Admin Only', checked: true };
		@else
		var checkbox = { label: 'Allow Custom Admin Only' };
		@endif
	
		var allow_custom_admin = new ej.buttons.Switch(checkbox);
		allow_custom_admin.appendTo("#allow_custom_admin");
		
		@if(!empty($conf['account_filter']) && $conf['account_filter'] == 1)
		var checkbox = { label: 'Account Filter', checked: true };
		@else
		var checkbox = { label: 'Account Filter' };
		@endif
	
		var select_multiple = new ej.buttons.Switch(checkbox);
		select_multiple.appendTo("#account_filter");
		
		@if(!empty($conf['unique_filter']) && $conf['unique_filter'] == 1)
		var checkbox = { label: 'unique Filter', checked: true };
		@else
		var checkbox = { label: 'Unique Filter' };
		@endif
	
		var unique_filter = new ej.buttons.Switch(checkbox);
		unique_filter.appendTo("#unique_filter");
		
		select_function = new ej.inputs.TextBox({
			@if($conf['select_function'])
			value: "{{ $conf['select_function'] }}",
			@endif
		});
		select_function.appendTo("#select_function");

	show_field_groups();
	@endif
	
	@if($field->field_type == 'file' || $field->field_type == 'image')
		upload_type = new ej.dropdowns.DropDownList({
			floatLabelType: 'Auto',
			@if($conf['upload_type'])
			value: "{{ $conf['upload_type'] }}",
			@endif
		});
		upload_type.appendTo("#upload_type");
		
		allowed_file_extensions = new ej.inputs.TextBox({
			placeholder: 'Allowed File Extensions',
			floatLabelType: 'Auto',
		});
		allowed_file_extensions.appendTo("#allowed_file_extensions");
		
		@if(!empty($conf['allow_multiple']) && $conf['allow_multiple'] == 1)
		var checkbox = { label: 'Allow Multiple', checked: true };
		@else
		var checkbox = { label: 'Allow Multiple' };
		@endif
	
		var allow_multiple = new ej.buttons.Switch(checkbox);
		allow_multiple.appendTo("#allow_multiple");
	@endif
});

function show_field_groups(){
	var field_type = '{{$field->field_type}}';
	if(field_type == 'select_module'){
		$("#dropdown_conn").show();
		$("#dropdown_database").show();
		$("#dropdown_custom").hide();
		$("#dropdown_function").hide();
		$("#dropdown_results").show();
	}else if(field_type == 'select_custom'){
		$("#dropdown_conn").hide();
		$("#dropdown_custom").show();
		$("#dropdown_database").hide();
		$("#dropdown_function").hide();
		$("#dropdown_results").show();
	}else if(field_type == 'select_function'){
		$("#dropdown_conn").hide();
		$("#dropdown_function").show();
		$("#dropdown_database").hide();
		$("#dropdown_custom").hide();
		$("#dropdown_results").show();
	}else if(field_type == 'select_tables'){
		$("#dropdown_conn").show();
		$("#dropdown_custom").hide();
		$("#dropdown_database").hide();
		$("#dropdown_function").hide();
		$("#dropdown_results").show();
	}else if(field_type == 'select_connections'){
		$("#dropdown_conn").hide();
		$("#dropdown_custom").hide();
		$("#dropdown_database").hide();
		$("#dropdown_function").hide();
		$("#dropdown_results").hide();
	}
}

function set_field_lists(){
	if(tables.value == ''){
		set_fields_datasource([]);
	}else{
		$.ajax({
			url: '/module_manager/columnlist/{{ $field->module_id }}/'+tables.value,
			success: function(data){
				set_fields_datasource(data);
			}
		});
	}
}

function set_fields_datasource(dataSource){
	
	key.value = '';
	key.dataSource = dataSource;
	key.enabled = true;
	key.dataBind();
	display.value = '';
	display.dataSource = dataSource;
	display.enabled = true;
	display.dataBind();
	orderby.value = '';
	orderby.dataSource = dataSource;
	orderby.enabled = true;
	orderby.dataBind();
}

$('#fieldForm').on('submit', function(e) {
	e.preventDefault();
	formSubmit('fieldForm');
});
</script>
@endspush

@push('page-styles')

<style>
#page-wrapper{
		background-color: #fbfbfb;
		padding: 2%;
		margin-top:3%;
		margin-bottom:3%;
		box-shadow: 0 0 0.2cm rgba(0,0,0,0.3);
}
</style>
@endpush