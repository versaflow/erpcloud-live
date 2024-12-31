@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
	
@endif
@push('styles')
   
   
@endpush

@push('scripts')
   
@endpush

@section('content')
<div class="col-lg-12 control-section">
    <div class="control_wrapper">
        <!-- Initialize FileManager -->
        <div id="filemanager"></div>
    </div>
</div>
@endsection

@push('page-styles')

<style>

    .control_wrapper {
        margin: auto;
        border-radius: 3px;
    }
</style>
@endpush

@push('page-scripts')

<script>

/**
 * File Manager full functionalities sample
 */

     console.log('start');
     var hostUrl = '{{ url("filemanager_actions") }}';
     console.log(hostUrl);
     var fileObject = new ej.filemanager.FileManager({
            ajaxSettings: {
                url: hostUrl,
                downloadUrl: hostUrl + '/download',
            },
            // File Manager's created event
            created: onCreate,
            toolbarSettings: { items: ['NewFolder', 'SortBy', 'Refresh', 'Cut', 'Copy', 'Paste', 'Delete', 'Download', 'Rename', 'Selection', 'View', 'Details'] },
            view: 'Details',
            contextMenuSettings: {
                layout: ["SortBy", "View", "Refresh", "|", "Paste", "|", "NewFolder", "|", "Details", "|", "SelectAll"],
                visible: true
            },
            detailsViewSettings: {
                columns: [
                    {
                        field: 'name', headerText: 'Name', customAttributes: { class: 'e-fe-grid-name' }
                    },
                    {
                        field: '_fm_modified', headerText: 'DateModified', format: 'MM/dd/yyyy hh:mm a'
                    },
                    {
                        field: 'size', headerText: 'Size', template: '<span class="e-fe-size">${size}</span>', format: 'n2'
                    }
                ]
            }
        });
    fileObject.appendTo('#filemanager');

function onCreate(args){
               console.log("File Manager has been created successfully");
          }

</script>
@endpush

