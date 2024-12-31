@extends( '__app.layouts.guest' )

@if(!request()->ajax())
	
@endif
@section('content')
<div class="container-fluid text-center mt-1">

<div id="helpdesk_tabs"></div>
<div id="faq_tab">
 <div data-reamaze-embed="kb"></div>
</div>
<div id="ticket_tab"  style="display:none;">
<div class="mt-3">
    {!! $ticket !!}
</div>
</div>
</div>
@endsection
@push('page-scripts')

<script type="text/javascript" async src="https://cdn.reamaze.com/assets/reamaze.js"></script>
<script type="text/javascript">
  var _support = _support || { 'ui': {}, 'user': {} };
  _support['account'] = 'cloudtelecoms';
  _support['ui']['contactMode'] = 'mixed';
  _support['ui']['enableKb'] = 'true';
  _support['ui']['styles'] = {
    widgetColor: 'rgb(0, 0, 0)',
    gradient: true,
  };

  _support['apps'] = {
    faq: {"enabled":true},
    recentConversations: {},
    orders: {}
  };
</script>


<script type="text/javascript" async src="https://cdn.reamaze.com/assets/reamaze.js"></script>
<script type="text/javascript">
  var support = support || { 'ui': {}, 'user': {} };
  _support['account'] = 'cloudtelecoms';
</script>
<script type="text/javascript">

</script>
<script type="text/javascript">
    $(document).ready(function() {
        
 

    var tstabs = new ej.navigations.Tab({
    items: [
    { header: { 'text': 'Helpdesk' }, content: '#faq_tab'},
    { header: { 'text': 'Submit a Ticket' }, content: '#ticket_tab'},
    ],
    selectedItem: 0,
    });
    tstabs.appendTo('#helpdesk_tabs');
    });  
    
   
    
    $('#helpdesk').on('submit', function(e) {
        e.preventDefault();
        formSubmit("helpdesk");
    });
</script>
@endpush


@push('page-styles')

<style>
.e-dialog .e-dlg-content {
    padding: 18px !important;    
    padding-top: 0px !important;
}
</style>
@endpush