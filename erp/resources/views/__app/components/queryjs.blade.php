@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
    
	
@endif

@section('content')

<form id="query_builder" action="/report_query_save" class="form-horizontal">

	<input name="id" type="hidden" value="{{$id}}" />
	<input name="connection" type="hidden" value="{{$connection}}" />
	<input name="action" type="hidden" value="save"/>
	<div class="container mx-auto p-2 ">
	@if(!request()->ajax())
	<div class="row mt-3">
	<div class="col text-right">
	<button type="submit" id="submitbtn"  class="e-btn e-info">Submit</button>
	<a id="backbtn" href="{{ url()->previous() }}" class="e-btn">Back</a>
	</div>
	</div>
	@endif
		<div class="row mt-3">
    		<div class="col">
			<div class="card">
				<div class="card-header">
					Schema
				</div>
				<div class="card-body">
					<div class="form-group">
						<input id="db_conn" />
					</div>
					<div class="form-group">
						<input id="db_tables" />
					</div>
					<div class="form-group">
						<input id="db_columns" />
					</div>
				</div>
			</div>
			</div>
		</div>
		
		<div class="row mt-3 joinrow">
    		<div class="col">
			<div class="card">
				<div class="card-header">
					Joins
				</div>
				<div class="card-body">
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
									<option @if($jt == "INNER JOIN") selected @endif>INNER JOIN</option>
									<option @if($jt == "LEFT JOIN") selected @endif>LEFT JOIN</option>
									<option @if($jt == "RIGHT JOIN") selected @endif>RIGHT JOIN</option>
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
			</div>
		</div>
		
		<div class="row mt-3 queryrow">
    		<div class="col">
			<div class="card">
				<div class="card-header">
					Filters
				</div>
				
				<div class="card-body">
					<div>
						<input id="sql_where" />
					</div>
					<div id="db_filters" ></div>
				</div>
			</div>
			</div>
		</div>
		
		
		@if(!empty($sql_query))
		<div class="row mt-3">
    		<div class="col">
			<div class="card">
				<div class="card-header">
					Query
				</div>
				<div class="card-body">
					@if(!empty(session('report_error')))
					<code>{!! session('report_error') !!}</code><br>
					@endif
					<code>{!! $sql_query !!}</code>
				</div>
			</div>
			</div>
		</div>
		@endif
		
		@if(!empty($query))	
		<div class="row mt-3 resultrow">
    		<div class="col">
			<div class="card">
				<div class="card-header">
					Query
				</div>
				<div class="card-body">	
		            <div class="row">
						<div id="query_preview"><code>{!! $query !!}</code></div>
		            </div>
				</div>
			</div>
			</div>
		</div>
		@endif
	</div>
</form>
@endsection
@push('page-scripts')

<script type="text/javascript">	
	
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
	
	function set_line_inputs(new_line = false){
		
		var line_count = $("#lines tr").length;
		join_types = [];
		join_tables_1 = [];
		join_tables_2 = [];
		join_tables_1_vals = {!! json_encode($join_table_1) !!};
		join_tables_2_vals = {!! json_encode($join_table_2) !!};
		$(".join_type").each(function(i, obj){
			var index = i;
			var isLastElement = (index == line_count -1);
			if(!new_line || (new_line && isLastElement)){
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
			if(!new_line || (new_line && isLastElement)){
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
			if(!new_line || (new_line && isLastElement)){
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
	    allowFiltering: true,
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
			set_table_list();
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
	    popupWidth: "auto",
        popupHeight: "200px",
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
	 	},
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
	    popupWidth: "auto",
        popupHeight: "200px",
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

	$('#db_filters').queryBuilder({

  filters: [{
    id: 'name',
    label: 'Name',
    type: 'string'
  }, {
    id: 'category',
    label: 'Category',
    type: 'integer',
    input: 'select',
    values: {
      1: 'Books',
      2: 'Movies',
      3: 'Music',
      4: 'Tools',
      5: 'Goodies',
      6: 'Clothes'
    },
    operators: ['equal', 'not_equal', 'in', 'not_in', 'is_null', 'is_not_null']
  }, {
    id: 'in_stock',
    label: 'In stock',
    type: 'integer',
    input: 'radio',
    values: {
      1: 'Yes',
      0: 'No'
    },
    operators: ['equal']
  }, {
    id: 'price',
    label: 'Price',
    type: 'double',
    validation: {
      min: 0,
      step: 0.01
    }
  }, {
    id: 'id',
    label: 'Identifier',
    type: 'string',
    placeholder: '____-____-____',
    operators: ['equal', 'not_equal'],
    validation: {
      format: /^.{4}-.{4}-.{4}$/
    }
  }]
});


	
	
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
			
			   url: '/report_query_save?connection={{$connection}}',
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
		
		if(db_conn.value == '' || db_tables.value == ''){
			set_columns_datasource('');
			set_filter_columns('');
		}else{
			$.ajax({
			   url: '/report_query_save?connection={{$connection}}',
			   data: {action: 'get_columns', db_conn: db_conn.value, db_tables: db_tables.value},
			   type: 'post',
			   success: function(data){
					set_columns_datasource(data);
				}
			});
			
			$.ajax({
			   url: '/report_query_save?connection={{$connection}}',
			   data: {action: 'get_filter_columns', db_conn: db_conn.value, db_tables: db_tables.value},
			   type: 'post',
			   success: function(data){
					set_filter_columns(data);
				}
			});
			
			$.ajax({
			   url: '/report_query_save?connection={{$connection}}',
			   data: {action: 'get_join_columns', db_conn: db_conn.value, db_tables: db_tables.value},
			   type: 'post',
			   success: function(data){
			   		joins_ds = data;
					set_joins_datasource(data);
				}
			});
		}
	}
	
	function set_columns_datasource(dataSource){
		if(dataSource == ''){
			$(".queryrow").hide();
			db_columns.value = '';
			db_columns.dataSource = [];
		}else{
			$(".queryrow").show();
			db_columns.dataSource = dataSource;
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
	
	}
	
	
	$('#query_builder').on('submit', function(e) {
		e.preventDefault();
	/*
		var sql = db_filters.getSqlFromRules(db_filters.getRules());
		var sql_json = db_filters.getValidRules(db_filters.rule);
			
		$.ajax({
			url: '/report_query_save?connection={{$connection}}',
			data: {action: 'save_rules', sql_json: sql_json, sql_where: sql, id: '{{$id}}'},
			type: 'post',
	        
			success: function(data){
			}
		});
	*/	
		formSubmit('query_builder');
	});
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
.e-rule-filter .e-input-group{
	width:500px !important;
}
.e-query-builder .e-group-body .e-horizontal-mode .e-rule-filter{   
    width: auto !important;
}
</style>
@endpush