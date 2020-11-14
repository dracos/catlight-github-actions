<?php

$config = json_decode(file_get_contents(dirname(__FILE__) . '/config.json'));

$orgs = $config->orgs;

foreach ($orgs as &$org) {
    $org->webUrl = "https://github.com/$org->id";
    foreach ($org->buildDefinitions as &$defn) {
        $defn->webUrl = "https://github.com/$org->id/$defn->id";
        $runs = call_api("/repos/$org->id/$defn->id/actions/runs?per_page=100");
        $branches = [];
        foreach ($runs->workflow_runs as $run) {
            #$run = call_api("/repos/$org[id]/$defn[id]/actions/runs/$run->id");
            if ($run->event == 'pull_request') {
                # Sometimes we are not getting PR details
                if ($run->pull_requests) {
                    $name = "#$run->run_number $run->name PR#" . $run->pull_requests[0]->number;
                    $ref = "refs/pull/" . $run->pull_requests[0]->number . ":$run->name/merge";
                } else {
                    $name = "#$run->run_number $run->name PR#$run->head_branch";
                    $ref = "refs/pull/" . $run->head_branch . ":$run->name/merge";
                }
            } else {
                $name = "#$run->run_number $run->name $run->head_branch";
                $ref = "refs/heads/$run->head_branch:$run->name";
            }
            $branches[$ref]['id'] = $ref;
            $finished = null;
            if ($run->status == 'queued') {
                $status = 'Queued';
            } elseif ($run->status == 'in_progress') {
                $status = 'Running';
            } elseif ($run->status == 'completed') {
                $finished = $run->updated_at;
                if ($run->conclusion == 'cancelled') {
                    $status = 'Canceled';
                } elseif ($run->conclusion == 'failure') {
                    $status = 'Failed';
                } elseif ($run->conclusion == 'success') {
                    $status = 'Succeeded';
                }
            }
            $branches[$ref]['builds'][] = [
                'id' => $run->id,
                'name' => $name,
                'webUrl' => $run->html_url,
                'status' => $status,
                'startTime' => $run->created_at,
                'finishTime' => $finished,
                'triggeredByUser' => [
                    'id' => $run->head_commit->author->email,
                    'name' => $run->head_commit->author->name,
                ],
            ];
        }
        $branches = array_values($branches);
        foreach ($branches as &$r) {
            $r['builds'] = array_reverse($r['builds']);
        }
        $defn->branches = $branches;
    }
}

$out = [
    'protocol' => 'https://catlight.io/protocol/v1.0/basic',
    'id' => 'github-actions',
    'name' => 'GitHub Actions',
    'webUrl' => 'https://github.com/features/actions',
    'spaces' => $orgs,
];

header('Content-Type: application/json');
print json_encode($out);

function call_api($url) {
    global $config;
    $ch = curl_init("https://api.github.com$url");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERPWD => "$config->username:$config->token",
        CURLOPT_HTTPHEADER => [
            'Accept: application/vnd.github.v3+json',
            "User-Agent: $config->username",
        ],
    ]);
    $out = curl_exec($ch);
    curl_close($ch);
    if (!$out) {
        print "Failed to fetch $url: ";
        print curl_error($ch);
        exit;
    }
    $out = json_decode($out);
    return $out;
}
