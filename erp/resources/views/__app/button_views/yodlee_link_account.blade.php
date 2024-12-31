@extends(( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' )

@section('content')

<div class="card">
<div class="card-body">
  <div id="container-fastlink">
    <div style="text-align: center;">
      <input type="submit" id="btn-fastlink" class="e-btn " value="Link an Account">
    </div>
  </div>
</div>
</div>
 
@if(check_access(1))
<script>
   $(document).on('click','.delete_yodlee', function(){
      var account_id = $(this).attr('yodlee_id');
      //console.log(account_id);
      $.ajax({
      url:'/delete_yodlee_account/'+account_id,
      type:'post',
      success:function(data){
        //console.log(data);
      processAjaxSuccess(data);
      }
      });
   });
</script>
<script>

   $(document).on('click','.delete_provider_yodlee', function(){
      var confirm_text = "This will also delete all associated accounts linked to this providerID, continue?"
      var confirmation = confirm(confirm_text);
      if (confirmation) {
        var account_id = $(this).attr('yodlee_id');
        //console.log(account_id);
        $.ajax({
        url:'/delete_yodlee_provider_account/'+account_id,
        type:'post',
        success:function(data){
          //console.log(data);
        processAjaxSuccess(data);
        }
        });
      }
   });

</script>
@endif
  <script>
    (function (window) {
      //Open FastLink
      var fastlinkBtn = document.getElementById('btn-fastlink');

      fastlinkBtn.addEventListener('click', function() {
        window.fastlink.open({
          fastLinkURL: '{!! $fastlink_url !!}',
          accessToken: 'Bearer {!! $access_token !!}',
          params: {
            configName : 'CloudTelecoms'
          },
          onSuccess: function (data) {
            //console.log('onSuccess');
            //console.log(data);
            toastNotify('Account linked successfully.', 'success');
           // window.location.href = '{{ url("update_yodlee_accounts") }}';
          },
          onError: function (data) {
            //console.log('onError');
            //console.log(data);
            window.fastlink.close();
            if(data.additionalStatus){
              toastNotify('Fastlink error - '+data.additionalStatus, 'error');
            }else{
              toastNotify('Fastlink error, check console.', 'error');
            }
           
          },
          onExit: function (data) {
            //console.log('onExit');
            //console.log(data);
            toastNotify('Fastlink closed.', 'info');
            
          },
          onEvent: function (data) {
            //console.log('onEvent');
            //console.log(data);
          }
        },
        'container-fastlink');
      },
      false);
    }(window));
  </script>
@endsection