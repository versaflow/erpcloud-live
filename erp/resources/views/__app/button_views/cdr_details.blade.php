@extends(( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' )


@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h6>Variables</h6>
        </div>
        <div class="card-body">
      
        <div id="ace_variables"></div>
        </div>
    </div>
    @if(!empty($callflow))
    <div class="card">
        <div class="card-header">
            <h6>Callflow</h6>
        </div>
        <div class="card-body">
        <div id="ace_callflow"></div>
        </div>
    </div>
    @endif  
    @if(!empty($applog))
    <div class="card">
        <div class="card-header">
            <h6>App Log</h6>
        </div>
        <div class="card-body">
        <div id="ace_applog"></div>
        </div>
    </div>
    @endif 
</div>
<style>

.ace_editor, .ace_editor div{
    font-family:monospace  !important;
}
</style>
@endsection
@push('page-scripts')
 <script src="{{ '/assets/libraries/jquery-json-viewer/jquery.json-viewer.js' }}"></script>

    <link href="{{ '/assets/libraries/jquery-json-viewer/jquery.json-viewer.css' }}" rel="stylesheet">
<script>
    $(document).ready(function(){
        
        $('#ace_variables').jsonViewer({!! $variables !!});
        
        @if(!empty($callflow))
        $('#ace_callflow').jsonViewer({!! $callflow !!});
        @endif
        
        @if(!empty($applog))
        $('#ace_applog').jsonViewer({!! $applog !!});
        @endif

    });
</script>
@endpush