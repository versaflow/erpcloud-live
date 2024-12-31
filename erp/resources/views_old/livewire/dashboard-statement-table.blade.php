<div>
    {{count($table_data) }} trx
        {{ $table_data->count() }} trx
     <div class="table table-sm-responsive">
        <table class="table table-sm table-border min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-200">
                <tr>
                    <th class="whitespace-nowrap  py-2 text-left text-xs font-medium text-gray-500 text-uppercase tracking-wider cursor-pointer">Transaction Date </th>
                    @if($account->type == 'reseller')
                    <th class="whitespace-nowrap  py-2 text-left text-xs font-medium text-gray-500 text-uppercase tracking-wider cursor-pointer">Company </th>
                    @endif
                    <th class="whitespace-nowrap  py-2 text-left text-xs font-medium text-gray-500 text-uppercase tracking-wider cursor-pointer">Reference </th>
                    <th class="whitespace-nowrap  py-2 text-end text-xs font-medium text-gray-500 text-uppercase tracking-wider cursor-pointer">Total </th>
                    <th class="whitespace-nowrap  py-2 text-end text-xs font-medium text-gray-500 text-uppercase tracking-wider cursor-pointer">Balance </th>
                    <th class="whitespace-nowrap  py-2 text-left text-xs font-medium text-gray-500 text-uppercase tracking-wider pointer-events-none text-end pl-0"> </th>
                    </tr>
            </thead>
            <tbody>
              
                    @if(count($table_data) == 0)
                <tr class="bg-white">
                    <td colspan=4>You have transactions.</td>
                </tr>
                    @else
                    @foreach($table_data as $trx)
                    
                    <tr class="bg-white">
                         <td colspan=5>{{var_dump($trx)}}</td>
                    </tr>
                    <tr class="bg-white"> 
                   
                        <td class=" py-1 text-sm text-gray-500 ">{{date('d M Y',strtotime($trx->docdate))}}</td>
                        @if($account->type == 'reseller')
                        <td class=" py-1 text-sm text-gray-500 "><span class="font-medium text-primary-500">{{$trx->service_company}}</span></td>
                        @endif
                        <td class=" py-1 text-sm text-gray-500 "><span class="font-medium text-primary-500">@if($trx->doctype=='Cashbook Customer Receipt')Payment: @endif{{$trx->reference}}</span></td>
                        <td class=" py-1 text-sm text-gray-500 text-end"><span style="font-family: sans-serif;">{{currency_formatted($trx->total,$trx->document_currency)}}</span></td>
                        <td class=" py-1 text-sm text-gray-500 text-end"><span style="font-family: sans-serif;">{{currency_formatted($trx->balance,$trx->document_currency)}}</span></td>
                        <td class=" text-sm text-gray-500 text-sm text-end pl-0">
                            @if($trx->doctype!='Cashbook Customer Receipt')
                            <a data-target="view_modal" href="{{ url($documents_url.'/view/'.$trx->id) }}" type="button" aria-haspopup="true" aria-expanded="false" class="btn btn-xs btn-icon btn-outline bg-light mb-0 p-2">
                                <i class="fas fa-file-invoice"></i>
                            </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    @endif
            </tbody>
        </table>
        </div>
 
     
    {{ $table_data->links() }}
</div>