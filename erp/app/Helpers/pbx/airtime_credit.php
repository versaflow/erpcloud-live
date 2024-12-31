<?php

function addAirtime($account_id, $amount, $type = 'purchase', $description = null)
{

    $account = dbgetaccount($account_id);

    // Fetch allowed types from the database
    // $allowedTypes = \DB::connection('pbx')->table('airtime_types')->pluck('id')->toArray();
    // aa('Allowed types fetched', $allowedTypes);

    // // Validate type
    // if (!in_array($type, $allowedTypes)) {
    //     aa('Invalid type', $type);
    //     throw new \InvalidArgumentException("Invalid type. Allowed types are: " . implode(', ', $allowedTypes));
    // }

    if ($amount <= 0) {
        aa('Invalid amount', $amount);
        throw new \InvalidArgumentException('Amount must be greater than zero.');
    }

    // Prepare airtime history data
    $airtime_history = [
        'created_at' => date('Y-m-d H:i:s'),
        'erp' => session('instance')->directory,
        'domain_uuid' => $account->domain_uuid,
        'total' => $amount,
        'type' => $type,
        // 'description' => $description,
    ];
    aa('Airtime history prepared', $airtime_history);

    // Insert into airtime history
    \DB::connection('pbx')->table('p_airtime_history')->insert($airtime_history);
    aa('Airtime history inserted');

    // Update account balance
    \DB::connection('pbx')->table('v_domains')
        ->where('erp', session('instance')->directory)
        ->where('account_id', $account->id)
        ->increment('balance', $amount);
    aa('Account balance updated', ['account_id' => $account->id, 'amount' => $amount]);

    return "Airtime of R$amount has been successfully added.";
}
