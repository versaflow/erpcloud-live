<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>{{ $doc->doctype.' #'.$doc->id  }}</title>
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
        @if($reseller->vat_number)
        <div>VAT Number: {{ $reseller->vat_number }}</div>
        @endif
        <div>{!! nl2br($reseller->address) !!}</div>
        <div>{{ $reseller->phone }}</div>
        <div><a href="mailto:{{ ($reseller->accounts_email) ? $reseller->accounts_email : $reseller->email }}">{{ ($reseller->accounts_email) ? $reseller->accounts_email : $reseller->email }}</a></div>
      </div>
      </div>
    </header>
    <main>
      <div id="details" class="clearfix">
        <div id="client">
          <div class="to">INVOICE TO:</div>
          <h2 class="name">{{ $account->company }}</h2>
          @if($account->vat_number)
          <div class="address">VAT Number: {{ $account->vat_number }}</div>
          @endif
          <div class="address">{!! nl2br($account->address) !!}</div>
          <div class="email"><a href="mailto:{{ $account->email }}">{{ $account->email }}</a></div>
        </div>
        <div id="invoice">
          <h1>{{ $doctype_label.' #'.$doc->id  }}</h1>
          <h5 class="date">Reference: {{ $doc->reference }}</h5>
          <!--<div class="date">Date of Invoice: {{ date('Y/m/d',strtotime($doc->docdate)) }}</div>-->
          <!--<div class="date">Due Date: {{ date('Y/m/d',strtotime($doc->docdate)) }}</div>-->
        </div>
      </div>
      <table border="0" cellspacing="0" cellpadding="0" class="table table-sm">
        <thead>
          <tr>
            <th class="desc p-2">DESCRIPTION</th>
            <th class="unit p-2">UNIT PRICE</th>
            <th class="qty p-2">QUANTITY</th>
            <th class="total p-2">TOTAL</th>
          </tr>
        </thead>
        <tbody>
          @foreach($doclines as $line)
          <tr>
            <td class="desc px-3"><h3>{{ ucwords(str_replace('_',' ',$line->code)) }}</h3>{{ $line->name }}
            @if($line->description)
              <br>
              {{ $line->description }}
            @endif
            @if($line->frequency != 'once off' && empty($doc->billing_type))
              <br>
              <b>{{ ucwords($line->frequency) }} Subscription: </b> {{ $currency_symbol }}{{ currency($line->service_full_price) }}
            @endif
            </td>
            <td class="unit px-2">{{ $currency_symbol }}{{ currency($line->service_price) }}</td>
            <td class="qty px-2">{{ $line->qty }}</td>
            <td class="total px-2">{{ $currency_symbol }}{{  currency($line->service_price*$line->qty) }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
      <div class="row">
      <div class=" col-6 offset-6">
      <table class="table">
        <tfoot id="tfooter">
          <tr>
            <td>SUBTOTAL</td>
            <td>{{ $currency_symbol }}{{ currency($doc->service_total-$doc->service_tax) }}</td>
          </tr>
          @if(!empty($doc->service_tax))
          <tr>
            <td>VAT 15%</td>
            <td>{{ $currency_symbol }}{{ currency($doc->service_tax) }}</td>
          </tr>
          @endif
          <tr>
            <td>GRAND TOTAL</td>
            <td>{{ $currency_symbol }}{{ currency($doc->service_total) }}</td>
          </tr>
        </tfoot>
      </table>
      </div>
      </div>
      <div id="notices">
        
        @if(!empty($doc->notes)){
          <div>NOTICE:</div>
          <div class="notice">{!! nl2br($doc->notes) !!}</div>
        @endif
        
        @if(!empty($reseller->invoice_footer))
          <br>
          <div>NOTICE:</div>
          <div class="notice">{!! nl2br($reseller->invoice_footer) !!}</div>
        @endif
        
        @if($doc->doctype == 'Tax Invoice' && $is_product) 
          <br>
          <div>GOODS RECEIVED BY:</div>
          <div class="notice">Date: ______________________________   Name: ______________________________   Signature: ______________________________</div>
        @endif
      </div>
      @if(!empty($payfast_link))
       {!! $payfast_link !!}
      @endif
    </main>
  </body>
</html>