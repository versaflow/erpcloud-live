<?php

function chatgpt_prompt($prompt, $apiKey = false)
{
    if (is_dev() && ! $apiKey) {
        $apiKey = env('OPENAI_API_KEY');
    }
    // Set up the API endpoint URL and headers
    $url = 'https://api.openai.com/v1/completions';
    $headers = [
        'Authorization' => 'Bearer '.$apiKey,
        'Content-Type' => 'application/json',
    ];

    // Set up the request body
    $body = json_encode([
        'model' => 'text-davinci-003',
        'prompt' => $prompt,
        'max_tokens' => 2048,
        'temperature' => 0,
    ]);

    // Send the HTTP POST request to the ChatGPT API
    $response = \Httpful\Request::post($url)
        ->addHeaders($headers)
        ->body($body)
        ->send();

    // Return the response body as a string
    return chatgpt_parse_response($response->raw_body);
}

function chatgpt_parse_response($json)
{
    $json = json_decode($json);

    return $json->choices[0]->text;
}

function chatgpt_event_descriptions($module_id)
{
    $events = \DB::table('erp_form_events')->where('type', 'schedule')->where('event_description', '')->where('module_id', $module_id)->get();
    foreach ($events as $event) {
        $function_code = get_function_code($event->function_name);

        $prompt = 'Give me a one sentence summary of the following php function: '.$function_code;

        $response = chatgpt_prompt($prompt);
        $response = trim($response);
        aa($event->function_name);
        aa($response);
        \DB::table('erp_form_events')->where('id', $event->id)->update(['event_description' => $response]);
    }
}
