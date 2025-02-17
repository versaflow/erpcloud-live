<?php

function addHourtoCronFrequency($cronExpression) {
    // Split the cron expression into individual parts
    $cronParts = explode(' ', $cronExpression);

    // Update the hour part (assuming it is in the first position)
    $hour = $cronParts[1];
    $hour = ($hour + 1) % 24; // Increment hour and wrap around if needed
    $cronParts[1] = str_pad($hour, 2, '0', STR_PAD_LEFT);

    // Recreate the modified cron expression
    $modifiedCronExpression = implode(' ', $cronParts);

    return $modifiedCronExpression;
}