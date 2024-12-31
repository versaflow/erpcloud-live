<?php

function schedule_import_github_issues()
{
    if (! is_main_instance()) {
        return false;
    }
    \DB::table('erp_github_issues')->truncate();
    $issues = get_open_github_issues();
    foreach ($issues as $issue) {

        $data = [
            'github_url' => $issue['url'],
            'title' => $issue['title'],
            'description' => $issue['body'],
        ];
        dbinsert('erp_github_issues', $data);
    }

}

function github_login()
{
    // $username = 'VersaFlow';
    $accessToken = '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';
    $params = [
        'client_id' => 'Ov23liGgaRQi6frHNgdd',
        'client_secret' => 'a633383a1c7f06fe9b0220380b82b8a088a7fa1e',
        // 'redirect_uri'  => 'https://github.com/login/oauth/authorize',
        // 'code'          => $accessToken
    ];

    try {
        $cookie_file = 'cookie1.txt';
        $ch = curl_init('https://github.com/login/oauth/authorize');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_COOKIESESSION, true);
        // curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        // curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $headers[] = 'Accept: application/json';
        $headers[] = 'Cookie: test=cookie';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        vd($response);
        // return $response;
    } catch (\Exception $e) {
        exception_log($e);
    }
}

function button_import_github_commits()
{
    schedule_import_github_commits();

    return json_alert('Done');
}

function schedule_import_github_commits()
{
    if (! is_main_instance()) {
        return false;
    }

    $system_user_id = get_system_user_id();
    // $response = github_login();
    // dd($response);
    $repo = 'erpcloud';
    $branch = 'master';
    $commits = get_github_commits_daily($repo, $branch);
    // dd($commits);
    foreach ($commits as $commit) {
        $data = [
            'repo' => $repo,
            'branch' => $branch,
            'committed_at' => date('Y-m-d H:i:s', strtotime($commit['commit']['committer']['date'])),
            'committed_date' => date('Y-m-d', strtotime($commit['commit']['committer']['date'])),
            'committer_name' => $commit['commit']['committer']['name'],
            'committer_email' => $commit['commit']['committer']['email'],
            'message' => $commit['commit']['message'],
            'html_url' => $commit['html_url'],
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $system_user_id,
            'node_id' => $commit['node_id'],
        ];
        $e = \DB::table('erp_github_commits')->where('repo', $repo)->where('branch', $branch)->where('node_id', $data['node_id'])->count();
        if (! $e) {
            \DB::table('erp_github_commits')->insert($data);
        }
    }

    $repo = 'helpdesk';
    $branch = 'master';
    $commits = get_github_commits_daily($repo, $branch);
    foreach ($commits as $commit) {
        $data = [
            'repo' => $repo,
            'branch' => $branch,
            'committed_at' => date('Y-m-d H:i:s', strtotime($commit['commit']['committer']['date'])),
            'committed_date' => date('Y-m-d', strtotime($commit['commit']['committer']['date'])),
            'committer_name' => $commit['commit']['committer']['name'],
            'committer_email' => $commit['commit']['committer']['email'],
            'message' => $commit['commit']['message'],
            'html_url' => $commit['html_url'],
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $system_user_id,
            'node_id' => $commit['node_id'],
        ];
        $e = \DB::table('erp_github_commits')->where('repo', $repo)->where('branch', $branch)->where('node_id', $data['node_id'])->count();
        if (! $e) {
            \DB::table('erp_github_commits')->insert($data);
        }
    }

    $repo = 'telecloud-flutter';
    $branch = 'dev';
    $commits = get_github_commits_daily($repo, $branch);
    foreach ($commits as $commit) {
        $data = [
            'repo' => $repo,
            'branch' => $branch,
            'committed_at' => date('Y-m-d H:i:s', strtotime($commit['commit']['committer']['date'])),
            'committed_date' => date('Y-m-d', strtotime($commit['commit']['committer']['date'])),
            'committer_name' => $commit['commit']['committer']['name'],
            'committer_email' => $commit['commit']['committer']['email'],
            'message' => $commit['commit']['message'],
            'html_url' => $commit['html_url'],
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $system_user_id,
            'node_id' => $commit['node_id'],
        ];

        $e = \DB::table('erp_github_commits')->where('repo', $repo)->where('branch', $branch)->where('node_id', $data['node_id'])->count();
        if (! $e) {
            \DB::table('erp_github_commits')->insert($data);
        }
    }

    $repo = 'erpcloud-flutter';
    $branch = 'main';
    $commits = get_github_commits_daily($repo, $branch);
    foreach ($commits as $commit) {
        $data = [
            'repo' => $repo,
            'branch' => $branch,
            'committed_at' => date('Y-m-d H:i:s', strtotime($commit['commit']['committer']['date'])),
            'committed_date' => date('Y-m-d', strtotime($commit['commit']['committer']['date'])),
            'committer_name' => $commit['commit']['committer']['name'],
            'committer_email' => $commit['commit']['committer']['email'],
            'message' => $commit['commit']['message'],
            'html_url' => $commit['html_url'],
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $system_user_id,
            'node_id' => $commit['node_id'],
        ];

        $e = \DB::table('erp_github_commits')->where('repo', $repo)->where('branch', $branch)->where('node_id', $data['node_id'])->count();
        if (! $e) {
            \DB::table('erp_github_commits')->insert($data);
        }
    }

    $repo = 'movie-magic-android-tv';
    $branch = 'main';
    $commits = get_github_commits_daily($repo, $branch);
    foreach ($commits as $commit) {
        $data = [
            'repo' => $repo,
            'branch' => $branch,
            'committed_at' => date('Y-m-d H:i:s', strtotime($commit['commit']['committer']['date'])),
            'committed_date' => date('Y-m-d', strtotime($commit['commit']['committer']['date'])),
            'committer_name' => $commit['commit']['committer']['name'],
            'committer_email' => $commit['commit']['committer']['email'],
            'message' => $commit['commit']['message'],
            'html_url' => $commit['html_url'],
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $system_user_id,
            'node_id' => $commit['node_id'],
        ];

        $e = \DB::table('erp_github_commits')->where('repo', $repo)->where('branch', $branch)->where('node_id', $data['node_id'])->count();
        if (! $e) {
            \DB::table('erp_github_commits')->insert($data);
        }
    }

    $repo = 'telecloud-pbx';
    $branch = 'master';
    $commits = get_github_commits_daily($repo, $branch);
    foreach ($commits as $commit) {
        $data = [
            'repo' => $repo,
            'branch' => $branch,
            'committed_at' => date('Y-m-d H:i:s', strtotime($commit['commit']['committer']['date'])),
            'committed_date' => date('Y-m-d', strtotime($commit['commit']['committer']['date'])),
            'committer_name' => $commit['commit']['committer']['name'],
            'committer_email' => $commit['commit']['committer']['email'],
            'message' => $commit['commit']['message'],
            'html_url' => $commit['html_url'],
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $system_user_id,
            'node_id' => $commit['node_id'],
        ];
        $e = \DB::table('erp_github_commits')->where('repo', $repo)->where('branch', $branch)->where('node_id', $data['node_id'])->count();
        if (! $e) {
            \DB::table('erp_github_commits')->insert($data);
        }
    }

}

function get_github_commits_daily($repo = false, $branch = 'master', $since = false)
{
    $pageNumber = 1; // Change this to the desired page number
    $perPage = 1000; // Number of commits per page

    if ($since) {
        $sinceDate = date('c', strtotime($since));
    } else {
        $sinceDate = date('c', strtotime('-4 days'));
    }
    $token = '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';
    $username = 'versaflow';
    $repo = 'erpcloud';

    $client = new \Github\Client;
    $auth = $client->authenticate($token, '', Github\AuthMethod::ACCESS_TOKEN);
    $commits = $client->api('repo')->commits()->all($username, $repo, ['sha' => 'master']);
    // vd($commits);
    if ($commits) {
        return $commits;
    } else {
        return null;
    }
}

function get_open_github_issues()
{
    // Replace these values with your actual GitHub credentials and repository information
    $accessToken = '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';
    $username = 'versaflow';
    $repo = 'versaflow';

    $client = new \Github\Client;
    $repositories = $client->api($username)->repositories($repo);
    $response = $client->get("https://api.github.com/repos/{$username}/{$repo}/issues", [
        'headers' => [
            'Authorization' => 'token '.$accessToken,
            'Accept' => 'application/vnd.github.v3+json',
        ],
        'query' => [
            'state' => 'open', // Retrieve only open issues
        ],
    ]);
    // use Github\Client;
    // use Symfony\Component\HttpClient\HttplugClient;

    // $client = Client::createWithHttpClient(new HttplugClient());

    // Check if the request was successful (status code 200)
    if ($response->getStatusCode() == 200) {
        // Parse the JSON response and return the open issues
        $data = json_decode($response->getBody(), true);

        return $data;
    } else {
        // Handle error, you may want to log or throw an exception
        return $response;
    }
}

function create_github_issue($title, $description)
{
    if (! is_main_instance()) {
        return false;
    }
    $e = \DB::table('erp_github_issues')->where('title', $title)->count();
    if (! $e) {
        // Replace these values with your actual GitHub credentials and repository information
        $accessToken = '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';
        $username = 'versaflow';
        $repo = 'erpcloud';

        // $client = new GitHubTalker();

        $response = $client->post("https://api.github.com/repos/{$username}/{$repo}/issues", [
            'headers' => [
                'Authorization' => 'token '.$accessToken,
                'Accept' => 'application/vnd.github.v3+json',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'title' => $title,
                'body' => $description,
            ],
        ]);

        // Print the response for debugging purposes
        //echo $response->getBody();
    }
}
