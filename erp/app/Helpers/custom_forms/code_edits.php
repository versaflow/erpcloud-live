<?php

function code_edits_form_get_function_name()
{

    if (! empty(request()->function_name)) {
        return request()->function_name;
    }

    return '';
}

function code_edits_form_get_function_code()
{

    $function_name = false;
    if (! empty(request()->function_name)) {
        $function_name = request()->function_name;
    }
    if ($function_name) {
        return get_function_code($function_name);
    }

    return '';
}

function aftersave_code_edits_check_code($request)
{

    if (empty($request->function_name) || empty($request->function_code)) {
        return json_alert('Function name and code required', 'warning');
    }

    if (substr_count($request->function_code, 'function ') > 1 && ! str_contains($request->function_code, 'where(function')) {
        return json_alert('Cannot declare more than one function', 'warning');
    }
    if (substr_count($request->function_code, 'function ') != 1 && ! str_contains($request->function_code, 'where(function')) {
        return json_alert('Function declaration invalid', 'warning');
    }
    $result = set_function_code($request->function_name, $request->function_code);

    if (! $result) {
        return json_alert('Code contains syntax errors.', 'error');
    }

}
