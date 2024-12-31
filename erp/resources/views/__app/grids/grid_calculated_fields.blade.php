<div class="container-fluid p-2">
    <div class="row row-eq-height p-0 m-0">
        <div class="col-3 px-2">
            <div class="card h-100">
            <div class="card-header">Calculated Fields</div>
            <div class="card-body p-0">
            <div id="calculate_fields_list" tabindex="1"></div>
            </div>
            </div>
        </div>
         
        <div class="col-9 px-2">
            <div class="card h-100">
            <div class="card-header">Expression</div>
            <div class="card-body p-1">
                <div class="mb-1">
<code>Examples:
([ItemCost] * [ItemCount]) - [PackageCost]
([Profit] < 0) ? 0 : [Profit]*0.2
[ItemCost] / [ItemCount]
</code>
                </div>
                <div class="mb-1">
                    <input id="field_name" placeholder="Field Name"/>
                </div>
                <div class="mb-1">
                    <div id="field_type" tabindex="1"></div>
                </div>
                <div class="mb-1">
                    <div id="report_columns" tabindex="1"></div>
                </div>
                <div class="btn-group d-flex k-button-group mb-1" role="group">
                    <button class="k-button w-100 operator_btn">+</button>
                    <button class="k-button w-100 operator_btn">-</button>
                    <button class="k-button w-100 operator_btn">*</button>
                    <button class="k-button w-100 operator_btn">/</button>
                    <button class="k-button w-100 operator_btn">=</button>
                    <button class="k-button w-100 operator_btn">!=</button>
                    <button class="k-button w-100 operator_btn"><</button>
                    <button class="k-button w-100 operator_btn">></button>
                    <button class="k-button w-100 operator_btn"><=</button>
                    <button class="k-button w-100 operator_btn">>=</button>
                    <button class="k-button w-100 operator_btn">AND</button>
                    <button class="k-button w-100 operator_btn">OR</button>
                </div>   
                <div class="mb-1">    
                    <textarea rows="5" id="expression"></textarea>
                </div>
                <button id="save_new" class="k-button float-right ">Save New</button>
                <button id="save" class="k-button float-right d-none">Save</button>
                <button id="delete" class="k-button float-right d-none">Delete</button>
            </div>
            </div>
        </div>   
        
        
    </div>
</div>

<script>
    calculated_field_id = null;
    //Initialize ListView component
    calculate_fields_list = new ej.lists.ListView({

        //Initialize dataSource with the DataManager instance.
        dataSource: new ej.data.DataManager({
            url: '{{ url($menu_route."/report_calculated_fields/list/".$report_id) }}',
            crossDomain: true
        }),

        //Map the appropriate columns to fields property
        fields: { id: 'id', text: 'colid' },
        cssClass: "e-small",

        //Set header title
        headerTitle: 'Calculated Fields',

        //Set true to show header title
        showHeader: false,
        select: function(args){
            $(".e-ripple-element").remove();
            calculated_field_id = args.data.id;
            expression.value = args.data.expression;
            field_name.value = args.data.colid;
            field_type.value = args.data.field_type;
            $("#delete").removeClass('d-none');
            $("#save").removeClass('d-none');
        }
    });

    //Render initialized ListView component
    calculate_fields_list.appendTo('#calculate_fields_list');
    
    
    
    //Initialize ListView component
    var field_type = new ej.dropdowns.DropDownList({

        //Initialize dataSource with the DataManager instance.
        dataSource: ['Currency','Text','Boolean','Integer'],
        placeholder: 'Select type',
    });

    //Render initialized ListView component
    field_type.appendTo('#field_type');
    
    //Initialize ListView component
    var report_columns = new ej.dropdowns.DropDownList({

        //Initialize dataSource with the DataManager instance.
        dataSource: new ej.data.DataManager({
            url: '{{ url($menu_route."/report_calculated_fields/coldefs/".$report_id) }}',
            crossDomain: true
        }),
        placeholder: 'Select column',
        //Map the appropriate columns to fields property
        fields: { id: 'field', text: 'headerName' },
        
        cssClass: "e-small",

        change: function(args){
            if(this.value > ''){
                var colId = '['+args.itemData.headerName+'] ';
                $("#expression").val($("#expression").val() + colId);
                this.clear();
                $("#expression").focus();
            }
        }
    });

    //Render initialized ListView component
    report_columns.appendTo('#report_columns');
    expression = new ej.inputs.TextBox({},"#expression");
    field_name = new ej.inputs.TextBox({},"#field_name");
    
    $("#expression").focus();
    
    $(document).off('click', '.operator_btn').on('click', '.operator_btn', function() {
        var operator =$(this).text();
        $("#expression").val($("#expression").val() + operator + ' ');
        $("#expression").focus();
    });
    
    $(document).off('click', '#save').on('click', '#save', function() {
        var save_url = ' {{ url($menu_route."/report_calculated_fields/save/".$report_id) }}';
        var post_data = {id: calculated_field_id, expression: $("#expression").val(), colId: $("#field_name").val(), field_type: field_type.value};
        $.ajax({
            url: save_url,
            data: post_data,
            dataType: "json",
            success: function(data){
                calculate_fields_list.refresh();
                calculate_fields_list.dataBind();
                calculated_field_id = data.id;
        	    window.parent.recreateGrid('report', {{$report_id}});	
                
            },
            error: function(jqXHR, textStatus, errorThrown) {
            },
        });
    });
    
    $(document).off('click', '#save_new').on('click', '#save_new', function() {
        var save_url = ' {{ url($menu_route."/report_calculated_fields/save/".$report_id) }}';
        var post_data = {id: null, expression: $("#expression").val(), colId: $("#field_name").val(), field_type: field_type.value};
        $.ajax({
            url: save_url,
            data: post_data,
            dataType: "json",
            success: function(data){
                calculate_fields_list.refresh();
                calculate_fields_list.dataBind();
                calculated_field_id = data.id;
        	    window.parent.recreateGrid('report', {{$report_id}});	
            },
            error: function(jqXHR, textStatus, errorThrown) {
            },
        });
    });
    
    $(document).off('click', '#delete').on('click', '#delete', function() {
        var delete_url = '{{ url($menu_route."/report_calculated_fields/delete/".$report_id) }}';
        var post_data = {id: calculated_field_id};
              
        $.ajax({
            url: delete_url,
            data: post_data,
            dataType: "json",
            success: function(data){
                calculate_fields_list.refresh();
                calculate_fields_list.dataBind();
                calculated_field_id = null;
                
                $("#delete").addClass('d-none');
                $("#save").addClass('d-none');
                $("#expression").val('');
                $("#field_name").val('');
                field_type.clear();
                
            },
            error: function(jqXHR, textStatus, errorThrown) {
            },
        });
    });

</script>
<style>
.row-eq-height {
    display: -webkit-box;
    display: -webkit-flex;
    display: -ms-flexbox;
    display: flex;
}
.operator_btn{
    font-family: monospace;
}
</style>