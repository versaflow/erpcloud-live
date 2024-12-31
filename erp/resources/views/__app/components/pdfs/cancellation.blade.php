<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Cancellation Letter - {{ $account->company  }}</title>
    <link href="/assets/libaries/bootstrap/bootstrap.min.css" rel="stylesheet" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
      <link rel="stylesheet" href="/assets/pdfs/style2.css" media="all" />
    <link href="/assets/OpenSans.pdf" rel="stylesheet"  type='text/css'>
    <style>
    body{
      /*font-family: 'Open Sans', sans-serif;*/
    }
    </style>
  </head>
  <body>
    <main>
      <div class="row ">
    
      <div  class=" col text-right" style="text-align: right; float:right;">
        <h2 class="name">{{ $reseller->company }}</h2>
        <div>{!! nl2br($reseller->address) !!}</div>
        <div><br> <b>Tel: {{ $reseller->phone }}</b></div>
        <div><a href="mailto:{{ ($reseller->accounts_email) ? $reseller->accounts_email : $reseller->email }}">{{ ($reseller->accounts_email) ? $reseller->accounts_email : $reseller->email }}</a></div>
      </div>
      </div>
      
      <hr>
      </div>
    <div class="row mt-5">
        <div class="col"><b>{{ date('d F Y',strtotime('2019-11-04')) }}<br>{{ $contact }}<br>{!! nl2br($account->address) !!}</b></div>
    </div>
    <div class="row mt-5">
        <div class="col text-center"><h1>Cancellation Letter</h1></div>
    </div>
    <div class="row">
        <div class="col text-muted">
            Dear Sir / Madam <br>
            <b>{{ $company }}</b> <br>
            {{ $contact }} <br>
            Tel: {{$account->phone}}<br>
            Email: {{$account->email}}
        </div>
    </div>
    <hr>
    <div class="row">
        <div class="col">
        {!! $pdf_text !!}
        Yours faithfully, <br>
        {{ $reseller->company }} Accounts Department.
        </div>
    </div>
    </main>
  </body>
</html>