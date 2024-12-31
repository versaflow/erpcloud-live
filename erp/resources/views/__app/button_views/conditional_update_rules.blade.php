@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
    
	
@endif

@section('content')
<form id="querybuilderform"></form>
<div class="col-lg-12 control-section">
    <div id="querybuilder" class="row">
    </div>
    
    
</div>
</form>

@endsection
@push('page-scripts')

<script type="text/javascript">	

    @if(!empty($filter_columns))
    	var filter_columns = {!! json_encode($filter_columns) !!};
    @else
    	var filter_columns = [];
    @endif
    
    qryBldrObj = new ej.querybuilder.QueryBuilder({
    	columns: filter_columns,
        @if(!empty($update_rules))
        created: function(e){
        	var rules = {!! $update_rules !!};
        	qryBldrObj.setRules(rules);
        
        },
        @endif
        actionBegin: function(args){
           
            if (args.requestType === 'value-template-create') {
                ////console.log(args);
                let ds = [];
                        //console.log(args.field);
                $(filter_columns).each(function(i, obj){
                    //console.log(obj);
                    //console.log(obj.field);
                    if(obj.field == args.field){
                        //console.log('match');
                        ds = obj.values;
                    }
                });
                fieldObj = new ej.dropdowns.DropDownList({
                dataSource: ds,
                fields: args.fields,
                value: args.rule.value,
                change: function (e) {
                qryBldrObj.notifyChange(e.value, e.element, 'field');
                }
                });
                fieldObj.appendTo('#' + args.ruleID + '_valuekey');
            }
        }
    });
    qryBldrObj.appendTo('#querybuilder');


	$('#querybuilderform').on('submit', function(e) {
	
		e.preventDefault();
	    var update_rules = qryBldrObj.getValidRules(qryBldrObj.rule);
    	var sql_where = qryBldrObj.getSqlFromRules(qryBldrObj.getRules());
		
		$.ajax({
			url: '/conditional_update_rule_save',
			data: {id: '{{$id}}', update_rules: update_rules, sql_where: sql_where},
			type: 'post',
			success: function(data){
			    
           
			    toastNotify(data.message,data.status);
			    if(data.status == 'success'){
                    try {
                    window['close_sidebar_callback'] = true;
                    sidebarformcontainer.hide();
                    }catch (e) {}
			    }
			}
		});
	});
 //console.log(qryBldrObj);
 //console.log(filter_columns);
</script>
@endpush

@push('page-styles')

<style>
    .e-query-builder {
      margin: 0 auto;
    }
</style>
@endpush