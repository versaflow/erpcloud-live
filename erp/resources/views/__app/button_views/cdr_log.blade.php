<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h6>Variables</h6>
        </div>
        <div class="card-body">
            @if(!empty($gateway_name))
            <p>Gateway: {{$gateway_name}}</p>
            @endif
            @if(!empty($domain_name))
            <p>Domain: {{$domain_name}}</p>
            @endif
            @if(!empty($extension))
            <p>Extension: {{$extension}}</p>
            @endif
        <pre id="cdr_log" ></pre>
       
    </div>
    
</div>

<script>
    $(document).ready(function(){
        
        

        function update_log() {
            $.ajax({
            url:'{{ url("get_cdr_log") }}',
            type:'get',
            success:function(data){
                //console.log(data);
                $("#cdr_log").text(data);
            }
            }).then(function() {           // on completion, restart
                setTimeout(update_log, 10000);  // function refers to itself
            });
        }   
    
    update_log();

    });
</script>