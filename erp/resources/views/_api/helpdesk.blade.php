@extends( '__app.layouts.api' )
@section('content')

<div class="card m-mt-0 mt-0">
    <div class="card-header pb-0">
        <h6>Helpdesk</h6>
    </div>
    <div class="card-body" style="font-size:14px">
    <div id='accordion'>  
        @foreach($articles as $i => $k)
        
  <div class="accordion-item">
    <h2 class="accordion-header" id="heading{{$i}}">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{$i}}" aria-expanded="false" aria-controls="collapse{{$i}}">
       {{ $k['title'] }}
      </button>
    </h2>
    <div id="collapse{{$i}}" class="accordion-collapse collapse" aria-labelledby="heading{{$i}}" data-bs-parent="#accordionExample">
      <div class="accordion-body">
         {!! nl2br($k['text']) !!}
      </div>
    </div>
  </div>
  
  
        @endforeach
    </div>
    </div>
</div>

@endsection



@push('page-styles')

<style>
.e-dialog .e-dlg-content {
    padding: 18px !important;    
    padding-top: 0px !important;
}
</style>
@endpush