<?php

function button_potential_supplier_copy_to_suppliers($request)
{
    $db = new DBEvent();
    $db->setTable('crm_suppliers');
    $row = \DB::table('acc_potential_suppliers')->where('id', $request->id)->get()->first();
    // if(!$row->is_importer){
    //     return json_alert('Supplier needs to be importer.','warning');
    // }
    // if(!$row->is_stockist){
    //     return json_alert('Supplier needs to be stockist.','warning');
    // }
    $data = [
        'company' => $row->name,
        'contact' => $row->contact,
        'email' => $row->email,
        'phone' => $row->phone,
        'has_moq' => $row->has_moq,
        'status' => 'Enabled',
    ];
    $result = $db->save($data);
    if ($result instanceof \Illuminate\Http\JsonResponse) {
        return $result;
    }
    if ($result && isset($result['id'])) {
        return json_alert('Supplier created.');
    } else {
        return json_alert('Error creating supplier.', 'error');
    }
}
