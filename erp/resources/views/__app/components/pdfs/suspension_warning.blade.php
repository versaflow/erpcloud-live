<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Statement {{ $account->company  }}</title>
    <link href="/assets/libaries/bootstrap/bootstrap.min.css" rel="stylesheet" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ public_path().'/assets/pdfs/style.css' }}" media="all" />
  </head>
  <body>
    <header class="clearfix">
      <div id="logo">
        @if($logo)
          @if($is_view)
          <img src="{{ $logo }}" id="logoimg">
          @else
          <img src="{{ $logo_path }}" id="logoimg">
          @endif
        @endif
      </div>
      <div id="company">
        <h2 class="name">{{ $reseller->company }}</h2>
        <div>{!! nl2br($reseller->address) !!}</div>
        <div>{{ $reseller->phone }}</div>
        <div><a href="mailto:{{ ($reseller->accounts_email) ? $reseller->accounts_email : $reseller->email }}">{{ ($reseller->accounts_email) ? $reseller->accounts_email : $reseller->email }}</a></div>
      </div>
      </div>
    </header>
    <main>
 
    <div class="row mt-4 mb-4">
        <div class="col text-center"><h1>{{ $pdf_title }}</h1></div>
       
    </div>
    
    <div class="row pt-2">
        <div class="col text-muted">
            <b>{{ $company }}</b> <br>
            Tel: {{$account->phone}}<br>
            Email: {{$account->email}}<br>
            @if(!empty($account->address))
            Address: {!! nl2br($account->address) !!}<br>
            @endif
        </div>
         <div class="col text-right"><b>{{ date('d F Y') }}</b></div>
    </div>
    
    <div class="row mt-4" >
        <div class="col">
            Dear Client<br />
            <br />
            According to our records your account is overdue. To avoid suspension of services and interest charges, make an<br />
            immediate payment on the Client Portal.<br />
            <br />  
            <br />
            If you have already made payment, please accept our thanks and disregard this notice.<br />
            <b>Account balance: R{{$account->balance}}</b><br />
            <b>Overdue balance: R{{$account->balance}}</b><br />
            <br />
            Should you fail to settle your account and your services are suspended, reactivation of services will ensue once<br />
            payment reflects on your account and may take up to 24 hours. Whilst your services are suspended, you remain liable<br />
            for the monthly recurring charges.<br />
            <br />
            Kindly note that once access to your services have been suspended and your account remains unpaid, your services<br />
            may be terminated, and cancellation charges levied against your account. Services, once cancelled, cannot be<br />
            reinstated.<br />
            <br />
            Payment may also be made directly on the Vox Mobile App or via Electronic Transfer Fund<br />
            <br />
            Our banking details are:<br />
            {!! nl2br($bank_details) !!}
            <br />
           
            Yours sincerely,<br />
            Credit Control Department<br />
            {{session('instance')->name}}<br />
            Web: https://{{session('instance')->domain_name}}
        </div>
    </div>
    </main>
  </body>
</html>