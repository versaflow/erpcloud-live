<table width="100%" class="items-table" cellspacing="0" border="0">
    <tr class="item-table-heading-row">
        <th width="2%" class="pr-20 text-right item-table-heading">#</th>
        <th width="40%" class="pl-0 text-left item-table-heading">Code</th>
      
        <th class="pr-20 text-right item-table-heading">Quantity</th>
        <th class="pr-20 text-right item-table-heading">Price</th>
     
        <th class="text-right item-table-heading">Total</th>
    </tr>
    @php
        $index = 1
    @endphp
    @php
    $monthly_total = 0;
    @endphp
    @foreach($doclines as $line)
    @php
    if($line->is_subscription)
    $monthly_total += currency($line->full_price*$line->qty,$currency_decimals);
    @endphp
        <tr class="item-row">
            <td
                class="pr-20 text-right item-cell"
                style="vertical-align: top;"
            >
                {{$index}}
            </td>
            <td
                class="pl-0 text-left item-cell"
                style="vertical-align: top;"
            >
                <span>{{ $line->code }}</span><br>
                <span class="item-description">{!! nl2br(($item->description)) !!}
                @if(!empty($line->product_description))
                {!! nl2br(($line->product_description)) !!}
                @endif
                @if($line->description)
                {!! nl2br(($line-description)) !!}
                @endif
                @if($line->domain_tld)
                TLD: {{ $line->domain_tld }}
                @endif
                </span>
            </td>
          
            <td
                class="pr-20 text-right item-cell"
                style="vertical-align: top;"
            >
                {{ $line->qty }}
            </td>
            <td
                class="pr-20 text-right item-cell"
                style="vertical-align: top;"
            >
                {{ $currency_symbol }}{{ currency($line->price,$currency_decimals) }}
            </td>

          

            <td
                class="text-right item-cell"
                style="vertical-align: top;"
            >
               {{ $currency_symbol }}{{  currency($line->price*$line->qty,$currency_decimals) }}
            </td>
        </tr>
        @php
            $index += 1
        @endphp
    @endforeach
</table>

<hr class="item-cell-table-hr">

<div class="total-display-container">
    <table width="100%" cellspacing="0px" border="0" class="total-display-table @if(count($doclines) > 12) page-break @endif">
        <tr>
            <td class="border-0 total-table-attribute-label">Sub Total</td>
            <td class="py-2 border-0 item-cell total-table-attribute-value">
                @if($is_supplier && $import_invoice)
                {{ $currency_symbol }}{{ currency(($doc->total-$doc->tax-$doc->import_tax_usd-$doc->shipping_usd),$currency_decimals) }}
                @elseif($is_supplier)
                {{ $currency_symbol }}{{ currency(($doc->total-$doc->tax-$doc->import_tax),$currency_decimals) }}
                @else
                {{ $currency_symbol }}{{ currency(($doc->total-$doc->tax),$currency_decimals) }}
                @endif
            </td>
        </tr>

        @if(!$remove_tax_fields)
        @if(!$import_invoice)
        <tr>
            <td class="border-0 total-table-attribute-label">
                VAT 15%
            </td>
            <td class="py-2 border-0 item-cell total-table-attribute-value">
                {{ $currency_symbol }}{{ currency($doc->tax,$currency_decimals) }}
            </td>
        </tr>
        @endif
        @endif

        @if($import_invoice)
          <tr>
            <td class="border-0 total-table-attribute-label">SHIPPING</td>
            <td class="py-2 border-0 item-cell total-table-attribute-value">{{ $currency_symbol }}{{ currency($doc->shipping_usd,$currency_decimals) }}</td>
          </tr>
          <tr>
            <td class="border-0 total-table-attribute-label">IMPORT TAX</td>
            <td class="py-2 border-0 item-cell total-table-attribute-value">{{ $currency_symbol }}{{ currency($doc->import_tax_usd,$currency_decimals) }}</td>
          </tr>
          @endif
          
          @if(!$import_invoice && $is_supplier && $doc->import_tax > 0)
          <tr>
            <td class="border-0 total-table-attribute-label">IMPORT TAX</td>
            <td class="py-2 border-0 item-cell total-table-attribute-value">{{ $currency_symbol }}{{ currency($doc->import_tax,$currency_decimals) }}</td>
          </tr>
          @endif
          
          
       
          
          @php $monthly_total_tax = currency($monthly_total * 1.15); @endphp
          @if($monthly_total > 0 && empty($doc->billing_type) && $doc->bill_frequency == 1)
          @if($reseller->vat_enabled && $account->currency != 'USD')
          <tr>
          <td class="border-0 total-table-attribute-label">{{ $subscription_frequency }} TOTAL THEREAFTER</td>
          <td class="py-2 border-0 item-cell total-table-attribute-value">{{ $currency_symbol }}{{ currency($monthly_total_tax,$currency_decimals) }}</td>
          </tr>
          @else
          <tr>
          <td class="border-0 total-table-attribute-label">{{ $subscription_frequency }} TOTAL THEREAFTER</td>
          <td class="py-2 border-0 item-cell total-table-attribute-value">{{ $currency_symbol }}{{ currency($monthly_total,$currency_decimals) }}</td>
          </tr>
          @endif
          @endif

        <tr>
            <td class="py-3"></td>
            <td class="py-3"></td>
        </tr>
        <tr>
            <td class="border-0 total-border-left total-table-attribute-label">
                TOTAL
            </td>
            <td
                class="py-8 border-0 total-border-right item-cell total-table-attribute-value"
                style="color: #5851D8"
            >
                {{ $currency_symbol }}{{ currency($doc->total,$currency_decimals) }}
            </td>
        </tr>
        
        @if($import_invoice)
        <tr>
            <td class="border-0 total-border-left total-table-attribute-label">
            TOTAL (Rands)
            </td>
            <td
            class="py-8 border-0 total-border-right item-cell total-table-attribute-value"
            style="color: #5851D8"
            >
            {{ 'R' }}{{ currency(($doc->total * $doc->exchange_rate),$currency_decimals) }}
            </td>
        </tr>
        @endif
    </table>
</div>
