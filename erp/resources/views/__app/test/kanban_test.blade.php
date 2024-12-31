<!-- https://ej2.syncfusion.com/javascript/documentation/kanban/getting-started/ -->

@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
    
	
@endif

@section('content')
<div class="control-section">
    <div class="control_wrapper m-0 ">
        <div class="card">
            <div class="card-header container-fluid">
            <div class="row">
            <div class="col-md-10">
            <span class="p-1" style="font-size:15px"><b>@if(!empty($role)) {{$role}} @endif Taskboard</b></span>
            <input id="filter" type="text">
            </div>
            <div class="col-md-2 float-right text-right">
            @if(check_access('1,31'))
            <button class="e-btn " id="addNew">Add</button>
            @endif
            </div>
            </div>
            </div>
            <div class="card-body">
                <div id="Kanban"></div>
            </div>
        </div>
    </div>
</div>
<script id="dialogTemplate" type="text/x-template">
        <table>
            <tbody>
                <tr>
                    <td class="e-label">ID</td>
                    <td>
                        <input id="Id" name="task_id" type="text" class="e-field" value="${id}" disabled required/>
                    </td>
                </tr>
                <tr>
                    <td class="e-label">Role</td>
                    <td>
                        <input type="text" name="role" id="Role" class="e-field" value="${role}" /> 
                    </td>
                </tr>
                <tr>
                    <td class="e-label">Category</td>
                    <td>
                        <input type="text" name="category" id="Category" class="e-field" value="${category}" />
                    </td>
                </tr>
                <tr>
                    <td class="e-label">Title</td>
                    <td>
                        <input id="Title" name="title" type="text" class="e-field" value="${title}"  required/>
                    </td>
                </tr>
                <tr>
                    <td class="e-label">Summary</td>
                    <td>
                        <textarea type="text" name="description" id="Summary" class="e-field" value="${description}">${description}</textarea>
                        <span class="e-float-line"></span>
                    </td>
                </tr>
                <tr>
                    <td class="e-label">Priority</td>
                    <td>
                        <input type="text" name="priority" id="Priority" class="e-field" value="${priority}" />
                    </td>
                </tr>
                <tr>
                    <td class="e-label">Status</td>
                    <td>
                        <input type="text" name="task_status" id="Status" class="e-field" value="${task_status}" required />
                    </td>
                </tr>
            </tbody>
        </table>    
</script>
<script id="headerTemplate" type="text/x-template">
    <div class="header-template-wrap">
        <div class="header-icon e-icons ${keyField}"></div>
        <div class="header-text">${headerText}</div>
    </div>
</script>
<script id="cardTemplate" type="text/x-template">
        <div class='card-template'>
            <div class='e-card-header'>
                <div class='e-card-header-caption'>
                     <div class='e-card-header-title e-tooltip-text'>${title}</div>
                </div>
                <div class="text-right">
                <span data-attr-id="${id}" class="fas fa-trash del-icon"></span>
                </div>
            </div>
            <div class='e-card-content e-tooltip-text'>
                <div class='e-text'>${description}</div>
            </div>
        </div>
</script>

@endsection

@push('page-scripts')


<script type="text/javascript">
ej.base.enableRipple(true);
window.getTags = function (data) {
    var tagDiv = '';
    var tags = data.split(',');
    for (var i = 0; i < tags.length; i++) {
        var tag = tags[i];
        tagDiv += '<div class="e-card-tag-field e-tooltip-text">' + tag + '</div>';
    }
    return tagDiv;
};
window.getString = function (role) {
    return role.match(/\b(\w)/g).join('').toUpperCase();
};

    var data = new ej.data.DataManager({
        @if(!empty($role_id))
        url: "kanban_data?role_id={{$role_id}}",
        @else
        url: "kanban_data",
        @endif
        crudUrl: "kanban_update",
        adaptor: new ej.data.UrlAdaptor
    });
    
    var kanbanObj = new ej.kanban.Kanban({
        dataSource: data,
        keyField: 'task_status',
        enableTooltip: true,
        columns: [
            { headerText: 'Open', keyField: 'Open', template: '#headerTemplate', allowToggle: true },
            { headerText: 'In Progress', keyField: 'InProgress', template: '#headerTemplate', allowToggle: true },
            { headerText: 'Closed', keyField: 'Close', template: '#headerTemplate', allowToggle: true }
        ],
        cardSettings: {
            showHeader: false,
            headerField: 'task_id',
            template: '#cardTemplate',
        },
        swimlaneSettings: {
            keyField: 'category',
        },
        cardRendered: function (args) {
            ej.base.addClass([args.element], args.data.priority);
        },
        dialogSettings: {
            template: '#dialogTemplate'
        },
        dialogOpen: onDialogOpen,
        @if($task_count >= 2)
        sortSettings: {
            field: 'sort_order',
            sortBy: 'Index',
        }
        @endif
    });
    kanbanObj.appendTo('#Kanban');
    var categoryData ={!! json_encode($categories) !!};
    var statusData = ['Open', 'InProgress', 'Close'];
    var roleData = {!! json_encode($roles) !!};
    var priorityData = ['Low', 'Normal', 'Critical', 'High'];
    function onDialogOpen(args) {
        @if(!check_access('1,31'))
        args.cancel = true;
        @endif
        if (args.requestType !== 'Delete') {
            var curData = args.data;
            var filledTextBox = new ej.inputs.TextBox({});
            
            filledTextBox.appendTo(args.element.querySelector('#Id'));
          
            var statusDropObj = new ej.dropdowns.DropDownList({
                value: curData.task_status, popupHeight: '300px',
                dataSource: statusData, fields: { text: 'Status', value: 'Status' }, placeholder: 'Status'
            });
            statusDropObj.appendTo(args.element.querySelector('#Status'));
            
            var categoryDropObj = new ej.dropdowns.DropDownList({
                value: curData.category, popupHeight: '300px',
                dataSource: categoryData, placeholder: 'Category'
            });
            categoryDropObj.appendTo(args.element.querySelector('#Category'));
            
            var roleDropObj = new ej.dropdowns.DropDownList({
                value: curData.role, popupHeight: '300px',
                dataSource: roleData, fields: { text: 'Role', value: 'Role' }, placeholder: 'Role'
            });
            roleDropObj.appendTo(args.element.querySelector('#Role'));
            
            var priorityObj = new ej.dropdowns.DropDownList({
                value: curData.priority, popupHeight: '300px',
                dataSource: priorityData, fields: { text: 'Priority', value: 'Priority' }, placeholder: 'Priority'
            });
            priorityObj.appendTo(args.element.querySelector('#Priority'));
            
            var textareaObj = new ej.inputs.TextBox({
                placeholder: 'Summary',
                multiline: true
            });
            textareaObj.appendTo(args.element.querySelector('#Summary'));
            
            var titleObj = new ej.inputs.TextBox({
                placeholder: 'Title',
            });
            titleObj.appendTo(args.element.querySelector('#Title'));
        }
    }
    
    @if(check_access('1,31'))
    var count = {{ $task_id }};
    document.getElementById('addNew').onclick = function () {
        var curData = {
             title: '', task_status: 'Open', priority: 'Normal', category: 'Developer', description: '', role: '{{$role}}', id: '{{$task_id+1}}', sort_order: 0,
        };
        kanbanObj.openDialog('Add', curData);
        count++;
    };
    @endif
    
    
    var priorityFilter = new ej.dropdowns.DropDownList({
        dataSource: ['Priority', 'Low', 'Normal', 'High', 'Critical'],
        index: 0,
        placeholder: 'Select a priority',
        width: 100,
        change: function(args){
            var filterQuery = new ej.data.Query();
            if (args.value !== 'Priority') {
                filterQuery = new ej.data.Query().where('priority', 'equal', args.value);
            }
            kanbanObj.query = filterQuery;
        }
    });
    priorityFilter.appendTo('#filter');
    
   $(document).off('click', '.del-icon').on('click', '.del-icon', function() {
        var id = $(this).attr('data-attr-id');
        kanbanObj.deleteCard(id);
    });
</script>

@endpush

@push('page-styles')
	      
<style>
 .e-kanban .header-template-wrap {
        display: inline-flex;
        font-size: 15px;
        font-weight: 500;
    }

    .e-kanban .header-template-wrap .header-icon {
        font-family: 'Kanban priority icons';
        margin-top: 3px;
        width: 10%;
    }

    .e-kanban .header-template-wrap .header-text {
        margin-left: 15px;
    }

    .e-kanban.e-rtl .header-template-wrap .header-text {
        margin-right: 15px;
    }

    .e-kanban.e-rtl .e-card-avatar {
        left: 12px;
        right: auto;
    }

    .e-kanban .e-card-avatar {
        width: 30px;
        height: 30px;
        text-align: center;
        background:  gainsboro;
        color: #6b6b6b;
        border-radius: 50%;
        position: absolute;
        right: 12px;
        bottom: 10px;
        font-size: 12px;
        font-weight: 400;
        padding: 10px 0px 0px 1px;
    }

    .e-kanban .Open::before {
        content: '\e700';
        color: #0251cc;
        font-size: 16px;
    }

    .e-kanban .InProgress::before {
        content: '\e703';
        color: #ea9713;
        font-size: 16px;
    }

    .e-kanban .e-image img {
        background: #ececec;
        border: 1px solid #c8c8c8;
        border-radius: 50%;
    }

    .e-kanban .Review::before {
        content: '\e701';
        color: #8e4399;
        font-size: 16px;
    }

    .e-kanban .Close::before {
        content: '\e702';
        color: #63ba3c;
        font-size: 16px;
    }

    .e-kanban .e-card .e-card-tag-field {
        background: #ececec;
        color: #6b6b6b;
        margin-right: 5px;
        line-height: 1.1;
        font-size: 13px;
        border-radius: 3px;
        padding: 4px;
    }

    .e-kanban .e-card-custom-footer {
        display: flex;
        padding: 0px 12px 12px;
        line-height: 1;
        height: 35px;
    }

    .e-kanban .e-kanban-content .e-content-row .e-content-cells .e-card-wrapper .e-card.Low,
    .e-kanban.e-rtl .e-kanban-content .e-content-row .e-content-cells .e-card-wrapper .e-card.Low {
        border-left: 3px solid #1F88E5;
    }

    .e-kanban .e-kanban-content .e-content-row .e-content-cells .e-card-wrapper .e-card.High,
    .e-kanban.e-rtl .e-kanban-content .e-content-row .e-content-cells .e-card-wrapper .e-card.High {
        border-left: 3px solid #673AB8;
    }

    .e-kanban .e-kanban-content .e-content-row .e-content-cells .e-card-wrapper .e-card.Normal,
    .e-kanban.e-rtl .e-kanban-content .e-content-row .e-content-cells .e-card-wrapper .e-card.Normal {
        border-left: 3px solid #02897B;
    }

    .e-kanban .e-kanban-content .e-content-row .e-content-cells .e-card-wrapper .e-card.Critical,
    .e-kanban.e-rtl .e-kanban-content .e-content-row .e-content-cells .e-card-wrapper .e-card.Critical {
        border-left: 3px solid #E64A19;
    }

    .e-kanban.e-rtl .e-kanban .e-kanban-content .e-content-row .e-content-cells .e-card-wrapper .e-card {
        border-left: none;
    }

    @font-face {
        font-family: 'Kanban priority icons';
        src:
            url(data:application/x-font-ttf;charset=utf-8;base64,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) format('truetype');
        font-weight: normal;
        font-style: normal;
    }

    [class^="sf-icon-"],
    [class*=" sf-icon-"] {
        font-family: 'Kanban priority icons' !important;
        speak: none;
        font-size: 55px;
        font-style: normal;
        font-weight: normal;
        font-variant: normal;
        text-transform: none;
        line-height: 1;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
    .del-icon,.del-icon:hover{
        cursor: pointer;
    }
</style>
@endpush