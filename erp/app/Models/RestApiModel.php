<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestApiModel extends Model
{
    public $module;

    public function __construct($module_id)
    {
        $this->module = \DB::connection('default')->table('erp_cruds')->where('id', $module_id)->get()->first();
    }


    public function getRows($request)
    {
        $where = $this->accountFilter();
        $data = \DB::connection($this->module->connection)->table($this->module->db_table)
        ->whereRaw('1=1 '.$where)
        ->get();
        
        return response()->json(['message' => 'OK','status'=>'success', 'data' => $data], 200);
    }

    public function getRow($id)
    {
        $allowed = $this->singleRecordAccess($id);
        if (!$allowed) {
            return response()->json(['message' => 'You do not have access to the requested resource','status'=>'error'], 403);
        }
        $data = \DB::connection($this->module->connection)->table($this->module->db_table)
        ->where($this->module->db_key, $id)
        ->whereRaw('1=1 '.$where)
        ->get()->first();
        return response()->json(['message' => 'OK','status'=>'success', 'data' => $data], 200);
    }

    public function createRow($request)
    {
        $settings = [];
        if ('crm_supplier_import_documents' == $this->module->db_table || 'crm_supplier_documents' == $this->module->db_table || 'crm_documents' == $this->module->db_table) {
            $settings = ['validate_document' => true];
        }

        $db = new \DBEvent($this->module->id, $settings);

        $response = $db->save($request);

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $response->setStatusCode(403);
            return $response;
        } elseif (!is_array($response) || empty($response['id'])) {
            return response()->json(['status' => 'error', 'message' => $response], 403);
        } else {
            $data = \DB::connection($this->module->connection)->table($this->module->db_table)
            ->where($this->module->db_key, $response['id'])
            ->get()->first();
            return response()->json(['message' => 'OK','status'=>'success', 'data' => $data], 200);
        }
    }

    public function updateRow($request, $id)
    {
        $settings = [];
        if ('crm_supplier_import_documents' == $this->module->db_table || 'crm_supplier_documents' == $this->module->db_table || 'crm_documents' == $this->module->db_table) {
            $settings = ['validate_document' => true];
        }

        $db = new \DBEvent($this->module->id, $settings);

        $allowed = $this->singleRecordAccess($id);
        if (!$allowed) {
            return response()->json(['message' => 'You do not have access to the requested resource','status'=>'error'], 403);
        }
        $db = new \DBEvent($this->module->id);
        $data = \DB::connection($this->module->connection)->table($this->module->db_table)
            ->where($this->module->db_key, $id)
            ->get()->first();

        $db_columns = $this->getTableFields();
        if (in_array('status', $db_columns) && $data->status == 'Deleted') {
            return response()->json(['message' => 'Record is deleted, cannot be updated','status'=>'error'], 401);
        }

        $data = (array) $data;
        foreach ($request->all() as $key => $val) {
            $data[$key] = $val;
        }

        $data[$this->module->db_key] = $id;
        $response = $db->save($data);

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $response->setStatusCode(404);
            return $response;
        } else {
            $data = \DB::connection($this->module->connection)->table($this->module->db_table)
            ->where($this->module->db_key, $id)
            ->get()->first();
            return response()->json(['message' => 'OK','status'=>'success', 'data' => $data], 200);
        }
        return true;
    }

    public function deleteRow($id)
    {
        // validate account record access
        $allowed = $this->singleRecordAccess($id);
        if (!$allowed) {
            return response()->json(['message' => 'You do not have access to the requested resource','status'=>'error'], 401);
        }

        if ($this->module->id == 334) { // subscriptions
            $erp_subscriptions = new \ErpSubs();
            $result = $erp_subscriptions->deleteSubscription($id);
            if ($result !== true) {
                return json_alert($result, 'error');
            }
        } else {
            $db = new \DBEvent($this->module->id);
            $result = $db->deleteRecord(['id'=>$id]);
            if ($result instanceof \Illuminate\Http\JsonResponse) {
                if ($result->getData()->status == 'success') {
                    return true;
                } else {
                    return $result;
                }
            } else {
                return response()->json(['status' => 'error', 'message' => $result]);
            }
            return true;
        }
    }

    public function accountFilter()
    {
        $db_columns = $this->getTableFields();
        if (14 == $this->module->app_id && !empty(session('sms_account_id')) && 1 != session('sms_account_id')) {
            if (in_array('account_id', $db_columns)) {
                return ' and '.$this->module->db_table.'.account_id='.session('sms_account_id');
            }
        }

        if (empty(session('account_id')) || empty(session('user_id'))) {
            return ' and 1=0';
        }

        if ($this->module->app_id == 12 && empty(session('pbx_account_id'))) {
            return ' and 1=0';
        }

        if ($this->module->connection != 'freeswitch' && $this->module->app_id == 12 && (!empty(session('pbx_partner_level')) || (!empty(session('pbx_account_id')) && 1 != session('pbx_account_id')))) {
            if (in_array('partner_id', $db_columns) && session('role_level') == 'Admin' && !empty(request()->query_params['show_all']) && request()->query_params['show_all'] == 1) {
                return ' ';
            } elseif (in_array('domain_uuid', $db_columns) && session('role_id') <= 11 && session('pbx_partner_level')) {
                return ' and '.$this->module->db_table.'.domain_uuid IN (select domain_uuid from v_domains where partner_id='.session('account_id').')';
            } elseif (in_array('partner_id', $db_columns) && session('role_id') <= 11 && session('pbx_partner_level')) {
                return ' and '.$this->module->db_table.'.partner_id="'.session('account_id').'"';
            } elseif (in_array('account_id', $db_columns) && session('role_id') <= 11 && session('pbx_partner_level')) {
                return ' and '.$this->module->db_table.'.account_id="'.session('account_id').'"';
            } elseif (in_array('account_id', $db_columns)) {
                return ' and '.$this->module->db_table.'.account_id='.session('pbx_account_id');
            } elseif (in_array('domain_uuid', $db_columns)) {
                return ' and '.$this->module->db_table.'.domain_uuid="'.session('pbx_domain_uuid').'"';
            }
        }


        if ($this->module->connection == 'freeswitch' && session('pbx_domain') != '156.0.96.60' && session('pbx_domain') != '156.0.96.69' && session('pbx_domain') != '156.0.96.61') {
            if ($this->module->db_table == 'registrations') {
                return ' and realm="'.session('pbx_domain').'" ';
            }
            if ($this->module->db_table == 'channels') {
                return ' and initial_context="'.session('pbx_domain').'" ';
            }
        }

        if (session('role_level') == 'Admin' && 507 == $this->module->id) {
            return ' and pricelist_id = 1 ';
        } elseif (session('role_level') == 'Admin') {
            return '';
        }

        if (session('role_level') == 'Partner') {
            if ('crm_accounts' == $this->module->db_table && $this->menu->menu_type == 'module_form') {
                return ' and '.$this->module->db_table.'.id='.session('account_id');
            }
            if ('crm_accounts' == $this->module->db_table) {
                return ' and '.$this->module->db_table.'.partner_id='.session('account_id');
            }

            if (in_array('partner_id', $db_columns) && in_array('account_id', $db_columns)) {
                return ' and ('.$this->module->db_table.'.account_id='.session('account_id')
                .' or '.$this->module->db_table.'.partner_id='.session('account_id').')';
            }

            if (in_array('account_id', $db_columns)) {
                return ' and ('.$this->module->db_table.'.account_id IN (select id from crm_accounts where partner_id='.session('account_id').')'
                .' or '.$this->module->db_table.'.account_id ='.session('account_id').')';
            }

            if (in_array('partner_id', $db_columns)) {
                return ' and '.$this->module->db_table.'.partner_id='.session('account_id');
            }
        }

        if (session('role_level') == 'Customer') {
            if (588 == $this->module->id && (check_access('21') || (!empty(session('grid_role_id')) && session('grid_role_id') == 21))) {
                return ' and '.$this->module->db_table.'.ratesheet_id='.session('pbx_ratesheet_id');
            }

            if (507 == $this->module->id) {
                return ' and '.$this->module->db_table.'.pricelist_id IN (select pricelist_id from crm_accounts where id='.session('account_id').')';
            }

            if (508 == $this->module->id || 524 == $this->module->id) {
                return ' and pricelist_id IN (select id from crm_pricelists where partner_id='.session('account_id').')';
            }

            if ('crm_accounts' == $this->module->db_table) {
                return ' and '.$this->module->db_table.'.id='.session('account_id');
            }

            if (1 != session('parent_id')) {
                if ('crm_documents' == $this->module->db_table) {
                    return ' and '.$this->module->db_table.'.reseller_user='.session('account_id');
                }
            }

            if (in_array('partner_id', $db_columns) && 21 != session('role_id')) {
                return ' and '.$this->module->db_table.'.partner_id='.session('account_id');
            }

            if (in_array('account_id', $db_columns)) {
                return ' and '.$this->module->db_table.'.account_id='.session('account_id');
            }

            if (in_array('user_id', $db_columns)) {
                return ' and '.$this->module->db_table.'.user_id='.session('user_id');
            }
        }
    }

    public function singleRecordAccess($id)
    {
        $where = $this->accountFilter();
        return \DB::connection($this->module->connection)->table($this->module->db_table)
            ->where($this->module->db_key, $id)
            ->whereRaw('1=1 '.$where)
            ->count();
    }

    public function getTableFields()
    {
        return get_columns_from_schema($this->module->db_table, null, $this->module->connection);
    }
}
