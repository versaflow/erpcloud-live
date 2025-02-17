<?php

function button_edit_button_code($request)
{
    $button_function = \DB::connection('default')->table('erp_grid_buttons')->where('id', $request->id)->pluck('function_name')->first();
    $code_edit_url = get_menu_url_from_table('erp_code_edits');
    return redirect()->to('/'.$code_edit_url.'/edit?tab_load=1&function_name='.$button_function);
}

function button_edit_event_code($request)
{
    $event_function = \DB::connection('default')->table('erp_form_events')->where('id', $request->id)->pluck('function_name')->first();

    $code_edit_url = get_menu_url_from_table('erp_code_edits');
    
    return redirect()->to('/'.$code_edit_url.'/edit?tab_load=1&function_name='.$event_function);
}


function button_edit_activation_code($request)
{
    $activation_plan = \DB::connection('default')->table('sub_activation_plans')->where('id', $request->id)->get()->first();
    if (!empty($activation_plan->function_name)) {
        $function_name = $activation_plan->function_name;
    } else {
        $function_name =  'provision_'.function_format($activation_plan->name);
    }

    
    $code_edit_url = get_menu_url_from_table('erp_code_edits');
    return redirect()->to('/'.$code_edit_url.'/edit?tab_load=1&function_name='.$function_name);
}
function button_edit_activation_form_code($request)
{
    $activation_plan = \DB::connection('default')->table('sub_activation_plans')->where('id', $request->id)->get()->first();
    if (!empty($activation_plan->function_name)) {
        $function_name = $activation_plan->function_name;
    } else {
        $function_name =  'provision_'.function_format($activation_plan->name).'_form';
    }

    
    $code_edit_url = get_menu_url_from_table('erp_code_edits');
    return redirect()->to('/'.$code_edit_url.'/edit?tab_load=1&function_name='.$function_name);
}


function get_function_code($function_name)
{
    if (!function_exists($function_name)) {
        return false;
    }
    
    $func = new ReflectionFunction($function_name);
    $filename = $func->getFileName();
    $start_line = $func->getStartLine() - 1; // it's actually - 1, otherwise you wont get the function() block
    $end_line = $func->getEndLine();
    $length = $end_line - $start_line;

    $source = file($filename);
    $body = implode("", array_slice($source, $start_line, $length));
    return $body;
}

function set_function_code($function_name, $code)
{
    try{
    if (!function_exists($function_name)) {
        return false;
    }

    $func = new \ReflectionFunction($function_name);
    $filename = $func->getFileName();
    $start_line = $func->getStartLine() - 1; // it's actually - 1, otherwise you wont get the function() block
    $end_line = $func->getEndLine();
    $length = $end_line - $start_line;

    $original_file = file_get_contents($filename);
    $cmd = 'chmod 666 '.$filename;
    Erp::ssh('portal.telecloud.co.za', 'root', 'Ahmed777', $cmd);

    $source = file($filename);
    $body = implode("", array_slice($source, $start_line, $length));


    $lines = file($filename, FILE_IGNORE_NEW_LINES);

    foreach ($lines as $key => $line) {
        if ($key == 0) {
            unset($lines[$key]);
        }
        if ($key >= $start_line && $key <= $end_line) {
            unset($lines[$key]);
        }
    }
    $formatted_lines = ['<?php',''];
    $code_lines = explode(PHP_EOL, $code);

    foreach ($code_lines as $code_line) {
        $formatted_lines[] = $code_line;
    }
    foreach ($lines as $line) {
        $formatted_lines[] = $line;
    }

    $data = implode(PHP_EOL, $formatted_lines);
    file_put_contents($filename, $data);
    //aa($filename);
    //aa($data);
    // file_put_contents(base_path().'/test_save.php',$data);

    $syntax_check_passed = php_syntax_check_passed($filename);
    if (!$syntax_check_passed) {
        file_put_contents($filename, $original_file);
        return false;
    }

    //file_put_contents($filename, $original_file);
    //return false;
    return true;
    }catch(\Throwable $ex){
        return false;
    }
}

function php_syntax_check_passed($filename)
{
    $cmd = "php -l $filename";
    $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);

    if (str_contains($result, 'No syntax errors detected')) {
        // Correct syntax
        return true;
    } else {
        // Syntax errors
        return false;
    }
}

function add_code_definition($function_name, $type)
{
    if (function_exists($function_name)) {
        return true;
    }
    $filename = app_path().'/Helpers/core/_code_add.php';
    if ($type == 'card') {
        $filename = app_path().'/Helpers/core/_module_card_functions.php';
    }
    $original_file = file_get_contents($filename);
    if ($type == 'request') {
        $contents = '
        function '.$function_name.'($request){
        }
        ';
    } elseif ($type == 'kpi') {
        $contents = '
        function '.$function_name.'(){
        }
        ';
    } else {
        $contents = '
        function '.$function_name.'(){
        }
        ';
    }


    $cmd = 'chmod 666 '.$filename;
    $result = Erp::ssh('localhost', 'root', 'Ahmed777', $cmd);
    file_put_contents($filename, PHP_EOL.$contents.PHP_EOL, FILE_APPEND | LOCK_EX);
    $syntax_check_passed = php_syntax_check_passed($filename);
    if (!$syntax_check_passed) {
        file_put_contents($filename, $original_file);
        return false;
    }
    return true;
}
