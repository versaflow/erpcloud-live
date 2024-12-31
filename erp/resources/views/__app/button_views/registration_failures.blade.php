<button id="refresh_failures" class="btn btn-sm btn-primary m-2">Refresh</button>
<div id="registrations_failures">
</div>
<script>
$(document).ready(function() {
   refresh_failures(1);
});

function refresh_failures(first_load = 0){
    @if(!empty(request()->domain_name))
    var url = '/registration_failures_cmd_ajax?domain_name={{request()->domain_name}}';
    @else 
    var url = '/registration_failures_cmd_ajax';
    @endif
    $.ajax({
        type: 'get',
        url: url,
        success: function(data){
            if(!first_load){
            toastNotify('Refreshed','success');
            }
            $("#registrations_failures").html(data);
        },
        error: function(jqXHR, textStatus, errorThrown) {
        }
    });
}

$(document).off('click', '#refresh_failures').on('click', '#refresh_failures', function() {
    //console.log('refresh_failures');
    refresh_failures();
});
</script>