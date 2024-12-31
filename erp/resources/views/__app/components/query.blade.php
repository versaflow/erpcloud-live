@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
    
	
@endif

@section('content')

<form id="query_builder" action="/report_query_save" class="form-horizontal">

	<input name="id" type="hidden" value="{{$id}}" />
	<input name="report_connection" type="hidden" value="{{$connection}}" />
	<input name="action" type="hidden" value="save"/>
	<div class="container mt-3 p-0 ">
	@if(!request()->ajax())
	<div class="row mt-3">
	<div class="col text-right">
	<button type="submit" id="submitbtn"  class="e-btn e-info">Submit</button>
	<a id="backbtn" href="{{ url()->previous() }}" class="e-btn">Back</a>
	</div>
	</div>
	@endif
	
			<div class="card">
				<div class="card-header" style="height:80px !important">
					<div class="row">
					<div class="col"><h6>Query Builder</h6><small>All queries will add the current date as a column named today.
						Use this formula to get the difference between a date column and today.
						max("today") - max("date_column")</small></div>
					<div class="col-auto text-right"> <button type="button" id="editSQL" class="e-btn">View SQL</button><button type="button" id="resetQuery" class="e-btn">Reset Query</button> <button type="button" id="resetJoins" class="e-btn">Reset Joins</button></div>
					</div>
				</div>
				<div class="card-body py-0 mt-2">
					<div class="form-group">
						<div class="form-row row">
						<div class="col">
						<input id="db_conn" />
						</div>
						<div class="col-auto">
							<button id="db_conn_update" class="e-btn e-small mt-4" type="button">Update</button>
						</div>
						</div>
						
					</div>
					<div class="form-group">
						<input id="db_tables" />
					</div>
					<div class="form-group">
						<input id="db_columns" />
					</div>
				</div>
		
		<div class="joinrow">
				<div class="card-body py-0">
					<div class="row">
					
						<div class="col-12">
						<table id="join_table" style="width:100%">
							<tbody>
							<tr>
								<td colspan="4" class="text-right"><a id="cloneButton"><span title="Add" class="btn btn-success btn-sm fa fa-plus" /></a></td>
							</tr>
							</tbody>
							<tbody id="lines">
							@foreach($join_type as $i => $jt)
							<tr>
							<td><input type="text" name="join_table_1[]"  class="join_table_1 form-control"></td>
							<td>
								<select name="join_type[]"  class="join_type form-control">
									<option></option>
									<option @if($jt == "LEFT JOIN") selected @endif>LEFT JOIN</option>
									<option @if($jt == "RIGHT JOIN") selected @endif>RIGHT JOIN</option>
									<option @if($jt == "INNER JOIN") selected @endif>INNER JOIN</option>
								</select>
							</td>
							<td><input type="text" name="join_table_2[]"  class="join_table_2 form-control"></td>
							<td class="text-right">
							<a href="javascript:void(0)" class="remove"><span title="Remove" class="btn btn-danger btn-sm  fa fa-minus" /></a>
							</td>
							</tr>
							@endforeach
						
							</tbody>
						</table>
						</div>
					</div>
				</div>
		</div>
		
		<div class="queryrow">
				<div class="card-body">
				
					<div id="db_filters" 	@if(!$enable_filters) style="display:none" @endif></div>
					
					<div class="row mt-1">
					<div class="col"><div id="date_filter_column" ></div></div>
					<div class="col"><div id="date_filter_value" ></div></div>
					<div class="col-auto"><button type="button" id="date_filter_reset" class="e-btn mt-3"><i class="far fa-times-circle"></i></button></div>
					</div>
					
					<div class="mt-3">
						<input id="sql_where" />
					</div>
					<div class="row mt-1">
						<div class="col form-group e-float-input e-control-wrapper">
						<label>Only our active customers</label>
						<input name="customer_filter" id="customer_filter" type="checkbox" value="1" >
						</div>
					</div>
				</div>
		</div>
		
	
		
		@if(!empty($sql_query))
		<div id="editsqlrow" style="display:none">
			
				<div class="card-body">
					@if(!empty(session('report_error')))
					<code>{!! session('report_error') !!}</code><br>
					@endif
					@if(!empty($query_error))
					<code>{!! $query_error !!}</code><br>
					@else
					<code>{!! $sql_query !!}</code>
					@endif
				</div>
		</div>
		@endif
		
			</div>
		
<div ref="component" class="field form-group has-feedback formio-component formio-component-button formio-component-submit float-right mr-2 form-group" >
<button lang="en" type="submit"  class="btn btn-primary ui button primary float-right mr-2" ref="button">
Submit
</button>
</div>
	</div>

</form>
@endsection
@push('page-scripts')

<script type="text/javascript">	

	@if(!empty($customer_filter))
	var checkbox = { label: 'Only our active customers', checked: true };
	@else
	var checkbox = { label: 'Only our active customers' };
	@endif
	var customer_filter = new ej.buttons.Switch(checkbox);
	customer_filter.appendTo("#customer_filter");
	
	@if(!empty($tables_ds))
		tables_ds = {!! json_encode($tables_ds) !!};
	@else
		tables_ds = [];
	@endif
	
	@if(!empty($columns_ds))
		columns_ds = {!! json_encode($columns_ds) !!};
	@else
		columns_ds = [];
	@endif
	
	@if(!empty($joins_ds))
		joins_ds = {!! json_encode($joins_ds) !!};
	@else
		joins_ds = [];
	@endif
	
	@if(!empty($filters_ds))
		filters_ds = {!! json_encode($filters_ds) !!};
	@else
		filters_ds = [];
	@endif
	join_tables_1_vals = {!! json_encode($join_table_1) !!};
	join_tables_2_vals = {!! json_encode($join_table_2) !!};
	join_types = [];
	join_tables_1 = [];
	join_tables_2 = [];

	$(document).ready(function(){
		@if(empty($db_conn) || empty($db_tables) || empty($db_columns))
		$(".queryrow").hide();
		$(".joinrow").hide();
		@endif
		
		$.when(save_line_template()).then(set_line_inputs());
		
	});
	
	$("#cloneButton").click(function() {
		$.when(insert_line()).then(set_line_inputs(true));
		var rowCount = $('#lines tr').length;
	
		if(rowCount == 1){
			$('.remove').hide();
		}else{
			$('.remove').css("display", "flex");
		}
			
	});
	
	$(document).off('click', '.remove').on('click', '.remove', function() {
    	var rowIndex = $(this).closest('tr').index();
		$(this).parents('tr').first().remove();
		var rowCount = $('#lines tr').length;
	
		if(rowCount == 1){
			$('.remove').hide();
		}else{
			$('.remove').css("display", "flex");
		}
    
		change_row_index(rowIndex, rowCount);
		return false;
	});
	
	function change_row_index(rowIndex, rowCount){
   
		if(rowIndex == rowCount || rowIndex == -1){
		return false;
		}
		
		var oldIndexrowLoop = rowIndex;
		while(rowLoop < rowCount){
		var oldIndex = rowLoop + 1;
		
		join_types[rowLoop] = join_types[oldIndex];
		join_tables_1[rowLoop] = join_tables_1[oldIndex];
		join_tables_2[rowLoop] = join_tables_2[oldIndex];
		rowLoop++;
		}
		return true;
    
  }
	
	function save_line_template(){
		line_template = $("#lines tr:last").clone();
	}
	
	function insert_line(){
		var cloned = line_template.clone();
		$(cloned).insertAfter("#lines tr:last");
	}
	
	function set_line_inputs(new_line = false, destroy_inputs = false){
		
		var line_count = $("#lines tr").length;
	
		$(".join_type").each(function(i, obj){
			var index = i;
			var isLastElement = (index == line_count -1);
			if(destroy_inputs){
				if (typeof join_types[i] !== 'undefined') {
					join_types[i].destroy();
				}
			}
			
			if(destroy_inputs || !new_line || (new_line && isLastElement)){
				join_types[i] = new ej.dropdowns.DropDownList({
                htmlAttributes: {name: 'join_type[]'}, 
				placeholder: 'Type'
				});
				join_types[i].appendTo(this);
			}
		});
		
		$(".join_table_1").each(function(i, obj){
			var index = i;
			var isLastElement = (index == line_count -1);
			if(destroy_inputs){
				if (typeof join_tables_1[i] !== 'undefined') {
					join_tables_1[i].destroy();
				}
			}
			if(destroy_inputs || !new_line || (new_line && isLastElement)){
				join_tables_1[i] = new ej.dropdowns.DropDownList({
                htmlAttributes: {name: 'join_table_1[]'}, 
				dataSource: joins_ds,
				placeholder: 'Join Field',
				value: join_tables_1_vals[index],
				});
				join_tables_1[i].appendTo(this);
			}
		});
		

		$(".join_table_2").each(function(i, obj){
			var index = i;
			var isLastElement = (index == line_count -1);
			if(destroy_inputs){
				if (typeof join_tables_2[i] !== 'undefined') {
					join_tables_2[i].destroy();
				}
			}
			if(destroy_inputs || !new_line || (new_line && isLastElement)){
				join_tables_2[i] = new ej.dropdowns.DropDownList({
                htmlAttributes: {name: 'join_table_2[]'}, 
				dataSource: joins_ds,
				placeholder: 'Join Field',
				value: join_tables_2_vals[index],
				});
				join_tables_2[i].appendTo(this);
			}
		});
	}
	
	db_conn = new ej.dropdowns.DropDownList({
	    floatLabelType: 'Auto',
		dataSource: {!! json_encode($connections) !!},
		allowFiltering: true,
		placeholder: 'Database ',
		filterBarPlaceholder: 'Select Database',
		@if(!empty($db_conn))
		value: "{{ $db_conn }}",
		@endif
        fields: {text: "text", value: "value"},
	    popupWidth: "auto",
        popupHeight: "200px",
		filtering: function(e){
			if(e.text == ''){
			e.updateData(db_conn.dataSource);
			}else{ 
			var query = new ej.data.Query().select(['text','value']);
			query = (e.text !== '') ? query.where('text', 'contains', e.text, true) : query;
			e.updateData(db_conn.dataSource, query);
			}
		},
		change: function(e){
		/*	set_table_list();*/
		}
	});
	db_conn.appendTo("#db_conn");
	
	db_tables = new ej.dropdowns.MultiSelect({
		dataSource: tables_ds,
		@if(!empty($db_tables))
		value: {!! json_encode($db_tables) !!},
	 	@endif
		htmlAttributes: {name: 'db_tables[]'}, 
		placeholder: 'Tables ',
		filterBarPlaceholder: 'Select Tables',
	    floatLabelType: 'Auto',
	    allowFiltering: true,
        fields: {text: "text", value: "value"},
        popupHeight: "200px",
        mode: 'CheckBox',
        showSelectAll: true,
        selectAllText: 'Select All',
		filtering: function(e){
			if(e.text == ''){
			e.updateData(db_tables.dataSource);
			}else{ 
			var query = new ej.data.Query().select(['text','value']);
			query = (e.text !== '') ? query.where('text', 'contains', e.text, true) : query;
			e.updateData(db_tables.dataSource, query);
			}
		},
	 	change: function(e){
	 		set_columns_list();
		 	var table_count = $(db_tables.value).length;
		 	if(table_count > 1){
				$(".joinrow").show();
		 	}else{
				$(".joinrow").hide();
		 	}
	 		db_table_values = db_tables.value;
	 		
			resetJoins();
	 	},
	 	created: function(e){
	 		db_table_values = db_tables.value;
	 	}
	});
	db_tables.appendTo("#db_tables");
	
	db_columns = new ej.dropdowns.MultiSelect({
		dataSource: columns_ds,
		@if(!empty($db_columns))
		value: {!! json_encode($db_columns) !!},
	 	@endif
		htmlAttributes: {name: 'db_columns[]'}, 
		placeholder: 'Columns ',
		filterBarPlaceholder: 'Select Columns',
	    floatLabelType: 'Auto',
        allowFiltering: true,
        fields: {text: "text", value: "value"},
        popupHeight: "200px",
        mode: 'CheckBox',
        showSelectAll: true,
        selectAllText: 'Select All',
		filtering: function(e){
			if(e.text == ''){
			e.updateData(db_columns.dataSource);
			}else{ 
			var query = new ej.data.Query().select(['text','value']);
			query = (e.text !== '') ? query.where('text', 'contains', e.text, true) : query;
			e.updateData(db_columns.dataSource, query);
			}
		},
	});
	db_columns.appendTo("#db_columns");


	db_filters = new ej.querybuilder.QueryBuilder({
		columns: filters_ds,
        //displayMode: "Vertical",
        @if(!empty($sql_json))
        created: function(e){
        	var rules = {!! $sql_json !!};
        	db_filters.setRules(rules);
        	bindEvent();
        },
        @endif
        change: function(args){
        
			if(args.type ==="insertRule") {
			bindEvent(); 
			}
        }
        
	});
	db_filters.appendTo('#db_filters');


        // bind filtering event for dropdownlist 
function bindEvent(){ 
let ddlColl = document.querySelectorAll('.e-dropdownlist'); 
for (let i = 0; i < ddlColl.length; i++) { 
    let ddl = ej.base.getComponent(ddlColl[i], "dropdownlist"); 
    ddl.index = -1; 
    ddl.dataBind(); 
    if(ddlColl[i].id.indexOf("operatorkey") < 0){ 
    ddl.filtering = builderonFiltering; 
    ddl.allowFiltering = true; 
    } 
} 
} 

function builderonFiltering(e) { 
	
    if(e.text == ''){
    	e.updateData(filters_ds);
    }else{ 
    	
        
	    var query = new ej.data.Query(); 
	    //frame the query based on search string with filter type. 
	 
	    query = (e.text !== '') ? query.where('label', 'contains', e.text, true) : query; 
	    //pass the filter data source, filter query to updateData method. 
	    e.updateData(db_filters.columns , query); 
    }
} 

	date_filter_column = new ej.dropdowns.DropDownList({
	    floatLabelType: 'Auto',
		dataSource: {!! json_encode($date_filter_columns) !!},
		allowFiltering: true,
		placeholder: 'Date Filter Column ',
		filterBarPlaceholder: 'Select Date Filter',
		@if(!empty($date_filter_column))
		value: "{{ $date_filter_column }}",
		@endif
        fields: {text: "text", value: "value"},
	    popupWidth: "auto",
        popupHeight: "200px",
        showClearButton: true,
		filtering: function(e){
			if(e.text == ''){
			e.updateData(date_filter_column.dataSource);
			}else{ 
			var query = new ej.data.Query().select(['text','value']);
			query = (e.text !== '') ? query.where('text', 'contains', e.text, true) : query;
			e.updateData(date_filter_column.dataSource, query);
			}
		},
	});
	date_filter_column.appendTo("#date_filter_column");
	
	
	date_filter_value = new ej.dropdowns.DropDownList({
	    floatLabelType: 'Auto',
		dataSource: {!! json_encode($date_filter_values) !!},
		placeholder: 'Date Filter Period',
		filterBarPlaceholder: 'Select Date Filter',
        showClearButton: true,
		@if(!empty($date_filter_value))
		value: "{{ $date_filter_value }}",
		@endif
	    popupWidth: "auto",
        popupHeight: "200px",
	});
	date_filter_value.appendTo("#date_filter_value");
	
	sql_where = new ej.inputs.TextBox({
		placeholder: "Where SQL",
		value: "{!! $sql_where !!}",
	});
	sql_where.appendTo("#sql_where");
	
	function set_table_list(){
		if(db_conn.value == ''){
			set_tables_datasource([]);
		}else{
			$.ajax({
			
			   url: '/report_query_save?report_connection={{$connection}}',
			   data: {action: 'get_tables', db_conn: db_conn.value},
			   type: 'post',
			   success: function(data){
					set_tables_datasource(data);
				}
			});
		}
	}
	
	function set_tables_datasource(dataSource){
		db_tables.value = '';
		db_tables.dataSource = dataSource;
		db_tables.enabled = true;
	}
	
	function set_columns_list(){
	
		var diff =$(db_tables.value).not(db_table_values).get();
	
		if(db_conn.value == '' || db_tables.value == ''){
			set_columns_datasource('');
			set_filter_columns('');
		}else{
			
			$.ajax({
			   url: '/report_query_save?report_connection={{$connection}}',
			   data: {action: 'get_columns', db_conn: db_conn.value, source_db_tables: {!! json_encode($db_tables) !!}, db_tables: db_tables.value, db_columns: db_columns.value},
			   type: 'post',
			   success: function(data){
					set_columns_datasource(data.datasource,data.values);
				}
			});
			
			
			$.ajax({
			   url: '/report_query_save?report_connection={{$connection}}',
			   data: {action: 'get_date_columns', db_conn: db_conn.value, db_tables: db_tables.value},
			   type: 'post',
			   success: function(data){
					if(data == ''){
						date_filter_column.dataSource = [];
					}else{
						db_filters.dataSource = data;
						db_filters.dataBind();
					}
				}
			});
			
			$.ajax({
			   url: '/report_query_save?report_connection={{$connection}}',
			   data: {action: 'get_filter_columns', db_conn: db_conn.value, db_tables: db_tables.value},
			   type: 'post',
			   success: function(data){
					set_filter_columns(data);
				}
			});
			
			$.ajax({
			   url: '/report_query_save?report_connection={{$connection}}',
			   data: {action: 'get_join_columns', db_conn: db_conn.value, db_tables: db_tables.value},
			   type: 'post',
			   success: function(data){
			   		joins_ds = data;
					set_joins_datasource(data);
				}
			});
		}
	}
	
	function set_columns_datasource(dataSource,values = ''){
		if(dataSource == ''){
			$(".queryrow").hide();
			db_columns.dataSource = [];
			db_columns.value = '';
		}else{
			$(".queryrow").show();
			db_columns.dataSource = dataSource;
			db_columns.value = values;
			db_columns.dataBind();
		}
	}
	
	
	function set_joins_datasource(dataSource){
		if(dataSource == ''){
			$(".join_table_1").each(function(i, obj){
				join_tables_1[i].dataSource = [];
				join_tables_1[i].dataBind();
			});
			
			$(".join_table_2").each(function(i, obj){
				join_tables_2[i].dataSource = [];
				join_tables_2[i].dataBind();
			});
		}else{
			$(".join_table_1").each(function(i, obj){
				join_tables_1[i].dataSource = dataSource;
				join_tables_1[i].dataBind();
			});
			
			$(".join_table_2").each(function(i, obj){
				join_tables_2[i].dataSource = dataSource;
				join_tables_2[i].dataBind();
			});
		}
	}
	
	function set_filter_columns(columns){
		if(columns == ''){
			db_filters.columns = [];
		}else{
			$(".queryrow").show();
			db_filters.columns = columns;
			db_filters.dataBind();
		}
	}

	
	$("#resetQuery").click(function() {	
		$.ajax({
			url: '/report_query_reset?report_connection={{$connection}}&id={{$id}}',
			success: function(data){
				toastNotify('Query Reset');
				closeActivePopup();
			}
		});
	});
	
	
	$("#db_conn_update").click(function(e) {
		e.preventDefault();
		e.stopPropagation();
		set_table_list();
		return false;
	});
	
	$("#editSQL").click(function() {
		$("#editsqlrow").toggle();
	});
	
	$("#resetJoins").click(function() {
		resetJoins();
	});
	
	function resetJoins(){
		
		$.ajax({
			url: '/report_query_save?report_connection={{$connection}}',
			data: {action: 'reset_joins', db_conn: db_conn.value, db_tables: db_tables.value, join_tables_1_vals: join_tables_1_vals, id: '{{$id}}'},
			type: 'post',
			success: function(data){
				////console.log(data);
				if(data.joins_ds){
					set_joins_datasource(data.joins_ds);
					joins_ds = data.joins_ds;
				}
				
				var join_lines = $('#lines tr').length;
				
				if(data.join_tables_1_vals.length > join_lines){
					//add lines
					var num_lines = parseInt(parseInt(data.join_tables_1_vals.length) - parseInt(join_lines));
					join_tables_1_vals = data.join_tables_1_vals;
					join_tables_2_vals = data.join_tables_2_vals;
					
					for (x = 0; x < num_lines; x++) {
						$.when(insert_line()).then(set_line_inputs(true, true));
					}
				}else if(data.join_tables_1_vals.length < join_lines){
					//remove lines
					var num_lines = parseInt(parseInt(join_lines) - parseInt(data.join_tables_1_vals.length));
					join_tables_1_vals = data.join_tables_1_vals;
					join_tables_2_vals = data.join_tables_2_vals;
					
					for (x = 0; x < num_lines; x++) {
						$.when($('#lines tr:last').find('.remove').trigger('click')).then(set_line_inputs(true, true));
					}
				}else{
					join_tables_1_vals = data.join_tables_1_vals;
					join_tables_2_vals = data.join_tables_2_vals;
					set_line_inputs(false, true);
				}
				
				$(".join_type").each(function(i, obj){
					join_types[i].value = 'LEFT JOIN';
				});
			}
		});
	}
		
	
	$('#query_builder').on('submit', function(e) {
	
		e.preventDefault();
		@if($enable_filters)
		var sql = db_filters.getSqlFromRules(db_filters.getRules());
		var sql_json = db_filters.getValidRules(db_filters.rule);
		@else
		var sql = $("#sql_where").val();
		var sql_json = '';
		@endif
			
		$.ajax({
			url: '/report_query_save?report_connection={{$connection}}',
			data: {action: 'save_rules', sql_json: sql_json, sql_where: sql, id: '{{$id}}'},
			type: 'post',
	        
			success: function(data){
			}
		});
		
		formSubmit('query_builder');
	});
	
 $(document).off('click', '#date_filter_reset').on('click', '#date_filter_reset', function() {
 	date_filter_column.value = null;
 	date_filter_value.value = null;
 });
function removeElems(src, permitted) {
	if(src.length == 0 || permitted.length == 0){
		return src;
	}
	var result = src.filter(item=>permitted.indexOf(item)==-1);

    for (var i = result.length - 1; i >= 0; i--) {
        src.splice( $.inArray(result[i], src), 1 );
    }
    return src;
}

</script>
@endpush

@push('page-styles')
<style>
#page-wrapper{
		background-color: #fbfbfb;
		box-shadow: 0 0 0.1cm rgba(0,0,0,0.3);
}
.remove{
	    display: inline-block !important;
}

#db_filters{
	border: none !important;
}
</style>
@endpush