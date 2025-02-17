<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;

class DashboardStatementTable extends Component
{
    use WithPagination;

    protected $account;

    public $account_id;

    protected $table_data;

    public function mount()
    {
        $account_id = $this->account_id;
        $account = dbgetaccount($this->account_id);
        $this->account = $account;

    }

    public function render()
    {

        $table_data = $this->getTableData()->paginate(10);

        return view('livewire.dashboard-statement-table', ['account' => $this->account, 'table_data' => $table_data]);
    }

    public function getTableData()
    {
        $line_balance = $this->account->balance;
        $statement_transactions = [];
        if ($account->type != 'reseller_user') {
            $debtor_transactions = collect(get_debtor_transactions($account_id))->reverse()->take(20);

            aa($debtor_transactions);
            if ($account->type == 'reseller') {
                foreach ($debtor_transactions as $i => $trx) {
                    if ($trx->doctype == 'Tax Invoice' || $trx->doctype == 'Credit Note') {
                        $reseller_user = \DB::table('crm_accounts')
                            ->join('crm_documents', 'crm_documents.reseller_user', '=', 'crm_accounts.id')
                            ->where('crm_documents.id', $trx->id)
                            ->pluck('company')->first();

                        $debtor_transactions[$i]->service_company = $reseller_user;
                    } else {
                        $debtor_transactions[$i]->service_company = '';
                    }
                    $debtor_transactions[$i]->balance = $line_balance;
                    $line_balance -= $trx->total;
                }
            }
            $statement_transactions = $debtor_transactions;
        }
        aa($statement_transactions);

        return collect($statement_transactions);
    }
}
