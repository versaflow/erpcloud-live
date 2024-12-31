<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>{{ $doc->doctype.' #'.$doc->id  }}</title>
    <!-- <link rel="stylesheet" type="text/css" href="/assets/pdfs/boostrap4.3.1.min.css"> -->
    <link rel="stylesheet" href="{{ public_path().'/assets/pdfs/style.css' }}" media="all" />
  </head>
  <body>
    <header class="clearfix">
      <div id="logo">
        @if(isset($logo))
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
        @if(!empty($reseller->phone))
        <div>{{ $reseller->phone }}</div>
        @endif
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
          @if($account->company_registration_number)
          <div class="address">Company Registration: {{ $account->company_registration_number }}</div>
          @endif
          <div class="address">{!! nl2br($account->address) !!}</div>
          <div class="email"><a href="mailto:{{ $account->email }}">{{ $account->email }}</a></div>
        </div>
        <div id="invoice">
          <h1>{{ $doctype_label.' #'.$doc->id  }}</h1>
          @if( $doc->reference)
          <h2 class="date">Reference: {{ $doc->reference }}</h2>
          @endif
          <h2 class="date">Date of Invoice: {{ date('Y/m/d',strtotime($doc->docdate)) }}</h2>
          <h2 class="date">Due Date of Invoice: {{ $due_date }}</h2>
          
        @if($doc->billing_type > '' && !empty($billing_period) && session('instance')->id == 1)
          <div class="date">Billing Period: {{ $billing_period }}</div>
        @endif
        
        @if($doc->contract_period > '')
          <div class="date">Contact Period: {{ $doc->contract_period }} Months</div>
        @endif
        
        @if($doc->bill_frequency > 1)
          <div class="date">Bill Frequency: Billed every {{ $doc->bill_frequency }} Months</div>
        @endif
        </div>
        
       
      </div>
      <table border="0" cellspacing="0" cellpadding="0" class="table table-sm table-bordered table-striped">
        <thead>
          <tr>
            <th class="desc p-2">DESCRIPTION</th>
            <th class="qty p-2">QUANTITY</th>
            
			      @if(!$remove_tax_fields)
            <th class="unit p-2">PRICE EX</th>
            <th class="total p-2">TOTAL EX</th>
            <th class="total p-2">TOTAL INC</th>
            @else
            <th class="unit p-2">PRICE</th>
            <th class="total p-2">TOTAL</th>
            @endif
          </tr>
        </thead>
        <tbody>
          @php
          $monthly_total = 0;
          @endphp
          @foreach($doclines as $line)
          @php
            if($line->is_subscription)
            $monthly_total += currency($line->full_price*$line->qty,$currency_decimals);
          @endphp
          <tr>
            <td class="desc px-3"><h3>{{ strtoupper($line->code) }} </h3>
            @if(empty($line->subscription_id))
            <b> {{ ucwords(str_replace('_',' ',$line->name)) }}</b><br>
            @endif
            @if(!empty($line->product_description))
              @php
               $description = preg_replace( "/\r|\n/", "<br>", $line->product_description);
               $description_arr = explode("<p>",$description);
              
               foreach($description_arr as $i => $dr)
               {
                 if($i<11){
                 echo '<p>'.$dr;
                 }
               }
              @endphp
             
            @endif
            @if($line->description)
              {!! preg_replace( "/\r|\n/", "<br>", $line->description) !!}
            @endif
            @if($line->domain_tld)
              TLD: {{ $line->domain_tld }}
            @endif
            </td>
            <td class="qty px-2">{{ $line->qty }}</td>
            <td class="unit px-2">{{ $currency_symbol }}{{ currency($line->price,$currency_decimals) }}</td>
            <td class="total px-2">{{ $currency_symbol }}{{  currency($line->price*$line->qty,$currency_decimals) }}
            @if($remove_tax_fields && $line->is_subscription && !$remove_monthly_totals && $doc->bill_frequency == 1)
              @if($reseller->vat_enabled && $account->currency != 'USD')
                <br><span class="mt-2">Thereafter <br>{{ $currency_symbol }}{{ currency((($line->full_price*$line->qty)* 1.15),$currency_decimals) }} / month</span>
              @else
                <br><span class="mt-2">Thereafter <br>{{ $currency_symbol }}{{ currency(($line->full_price*$line->qty),$currency_decimals) }} / month</span>
              @endif
            @endif
            </td>
            
			      @if(!$remove_tax_fields)
            @if($reseller->vat_enabled && $account->currency != 'USD' && !$import_invoice)
              <td class="total px-2">{{ $currency_symbol }}{{  currency((($line->price*$line->qty)* 1.15),$currency_decimals) }}
            @else
              <td class="total px-2">{{ $currency_symbol }}{{  currency($line->price*$line->qty,$currency_decimals) }}
            @endif
            
            @if(!$remove_tax_fields && $line->is_subscription && !$remove_monthly_totals && $doc->bill_frequency == 1)
              @if($reseller->vat_enabled && $account->currency != 'USD')
                <br><span class="mt-2">Thereafter <br>{{ $currency_symbol }}{{ currency((($line->full_price*$line->qty)* 1.15),$currency_decimals) }} / month</span>
              @else
                <br><span class="mt-2">Thereafter <br>{{ $currency_symbol }}{{ currency(($line->full_price*$line->qty),$currency_decimals) }} / month</span>
              @endif
            @endif  
          
            </td>
            @endif
           
          </tr>
          @endforeach
        </tbody>
      </table>
      <div class="row">
      <div class="col-6 offset-6">
      <table class="table">
        <tfoot id="tfooter">
          <tr>
            <td>SUBTOTAL</td>
            @if($is_supplier && $import_invoice)
            <td>{{ $currency_symbol }}{{ currency(($doc->total-$doc->tax-$doc->import_tax_usd-$doc->shipping_usd),$currency_decimals) }}</td>
            @elseif($is_supplier)
            <td>{{ $currency_symbol }}{{ currency(($doc->total-$doc->tax-$doc->import_tax),$currency_decimals) }}</td>
            @else
            <td>{{ $currency_symbol }}{{ currency(($doc->total-$doc->tax),$currency_decimals) }}</td>
            @endif
          </tr>
          
		      @if(!$remove_tax_fields)
          @if(!$import_invoice)
          <tr>
            <td>VAT 15%</td>
            <td>{{ $currency_symbol }}{{ currency($doc->tax,$currency_decimals) }}</td>
          </tr>
          @endif
          @endif
         
          @if($import_invoice)
          <tr>
            <td>SHIPPING</td>
            <td>{{ $currency_symbol }}{{ currency($doc->shipping_usd,$currency_decimals) }}</td>
          </tr>
          <tr>
            <td>IMPORT TAX</td>
            <td>{{ $currency_symbol }}{{ currency($doc->import_tax_usd,$currency_decimals) }}</td>
          </tr>
          @endif
          
          @if(!$import_invoice && $is_supplier && $doc->import_tax > 0)
          <tr>
            <td>IMPORT TAX</td>
            <td>{{ $currency_symbol }}{{ currency($doc->import_tax,$currency_decimals) }}</td>
          </tr>
          @endif
          <tr class="total-highlight">
            <td>GRAND TOTAL</td>
            <td>{{ $currency_symbol }}{{ currency($doc->total,$currency_decimals) }}</td>
          </tr>
          
          @if($import_invoice)
          <tr class="total-highlight">
            <td>GRAND TOTAL (Rands)</td>
            <td>{{ 'R' }}{{ currency(($doc->total * $doc->exchange_rate),$currency_decimals) }}</td>
          </tr>
          @endif
          
          @php $monthly_total_tax = currency($monthly_total * 1.15); @endphp
          @if($monthly_total > 0 && empty($doc->billing_type) && $doc->bill_frequency == 1)
          @if($reseller->vat_enabled && $account->currency != 'USD')
          <tr class="monthly-total-highlight">
          <td>{{ $subscription_frequency }} TOTAL THEREAFTER</td>
          <td>{{ $currency_symbol }}{{ currency($monthly_total_tax,$currency_decimals) }}</td>
          </tr>
          @else
          <tr class="monthly-total-highlight">
          <td>{{ $subscription_frequency }} TOTAL THEREAFTER</td>
          <td>{{ $currency_symbol }}{{ currency($monthly_total,$currency_decimals) }}</td>
          </tr>
          @endif
          @endif
        </tfoot>
      </table>
      </div>
      </div>
      <div>
        
        @if(!empty($doc->notes))
          <!--<div>NOTICE:</div>
          <div class="notice">{!! nl2br($doc->notes) !!}</div>-->
        @endif
        
        @if(!empty($reseller->invoice_footer))
          <br>
          <div>NOTICE:</div>
          <div class="">{!! nl2br($reseller->invoice_footer) !!}</div>
        @endif
        
        @if($doc->doctype == 'Tax Invoice' && $is_product) 
          <br>
          <div>GOODS RECEIVED BY:</div>
          <div class="mt-2">
            Date: ___________________________&nbsp;&nbsp;&nbsp;&nbsp;Name: ___________________________&nbsp;&nbsp;&nbsp;&nbsp;Signature: ___________________________
            </div>
        @endif
      </div>
      
     
      
        @if(!$is_supplier && !empty($bank_details) && $account->currency != 'USD')
       
        <div id="notices" class="mt-4 px-3">
        <div><h5>BANK DETAILS:</h5></div>
        <div class="notice">{!! nl2br($bank_details) !!}</div>
        </div>
        @endif
        
        @if(!$is_supplier && !empty($bank_details_usd) && $account->currency == 'USD')
       
        <div id="notices" class="mt-4 px-3">
        <div><h5>BANK DETAILS:</h5></div>
        <div class="notice">{!! nl2br($bank_details_usd) !!}</div>
        </div>
        @endif
     
      
    </main>
 
  </body>
</html>