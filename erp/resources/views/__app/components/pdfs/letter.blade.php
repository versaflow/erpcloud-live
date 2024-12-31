<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>{{$pdf_title}} - {{ $account->company  }}</title>
    <link rel="stylesheet" href="/assets/libaries/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/pdfs/style2.css"/>
    <link rel="stylesheet" href="/assets/OpenSans.pdf" type='text/css'>
    <style>
    body{
      font-family: 'Open Sans', sans-serif;
    }
    </style>
  </head>
  <body>
    <main>
    
      </div>
  
    <div class="row mt-5">
        <div class="col text-center"><h4>LETTER OF DEMAND IN TERMS OF S29(1)
OF THE SMALL CLAIMS COURTS ACT, 1984 (ACT NO. 61 OF 1984)</h4></div>
    </div>
   
    <hr>
    
    <div class="row">
      
        <div class="col text-muted">
            <b><b>FROM:</b> <br>
            Name: Muhammad Reyaaz Kola</b><br>
            Tel: 010 500 7500<br>
            Email: kola@telecloud.co.za<br>
            Address: 1257 Willem Botha Street,Wierdapark,Gauteng<br>
        </div>
        </div><br>
    <div class="row">
        <div class="col text-muted">
            <b>TO:</b> <br>
            <b>{{ $company }}</b> <br>
            @if(!empty($account->contact))
            Contact: {{$account->contact}}<br>
            @endif
            @if(!empty($account->phone))
            Tel: {{$account->phone}}<br>
            @endif
            Email: {{$account->email}}<br>
            @if(!empty($account->address))
            Address: {!! nl2br($account->address) !!}<br>
            @endif
        </div>
    </div>
    
    <hr>
    
    <div class="row">
        <div class="col">
        <h6>DEMAND</h6>
        <p>
        I, Muhammad Reyaaz Kola, hereby claim the sum of {{get_currency_symbol($account->currency)}}{{ currency($account->balance)}}
        from you OR {{$account->company}}, in respect of your {{$reseller->company}} account
        for outstanding balances and services rendered. 
        </p>
        <p>
        You are required within 14 (fourteen) days from the date of receipt of this demand, to pay or to settle
        the amount of {{get_currency_symbol($account->currency)}}{{ currency($account->balance)}}.
        If you fail to comply with this demand within the 14 (fourteen) days, I will institute action against you in the
        Small Claims Court to obtain a judgment for my claim against you.
        </p>
         <p>
        Signed on {{ date('d F Y') }}, <br>
        Muhammad Reyaaz Kola. <br><br><br>
        <img src="/assets/img/kola_signature.png" width="200px"/>
        </p>
        </div>
    </div>
    </main>
  </body>
</html>