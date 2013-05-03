<?php

function google_plus_count($url)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, "https://clients6.google.com/rpc");
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"' . $url . '","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-type: application/json'
    ));
    $curl_results = curl_exec($curl);
    curl_close($curl);
    $json = json_decode($curl_results, true);
    $val  = $json[0]['result']['metadata']['globalCounts']['count'];
    if ($val) {
        return $val;
    } else {
        return 0;
    }
    ;
    
}

function twitter_count($url)
{
    $twitter_url = "http://urls.api.twitter.com/1/urls/count.json?url=" . $url;
    $curl        = curl_init();
    curl_setopt($curl, CURLOPT_URL, $twitter_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-type: application/json'
    ));
    $curl_results = curl_exec($curl);
    curl_close($curl);
    $json = json_decode($curl_results, true);
    $val  = $json['count'];
    if ($val) {
        return $val;
    } else {
        return 0;
    }
    ;
}

function hn_count($url)
{
    $hn_url = 'http://api.thriftdb.com/api.hnsearch.com/items/_search?filter[fields][url][]=' . $url;
    $curl   = curl_init();
    curl_setopt($curl, CURLOPT_URL, $hn_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-type: application/json'
    ));
    $curl_results = curl_exec($curl);
    curl_close($curl);
    $json = json_decode($curl_results, true);
    
    $i = 0;
    if ($json['results']) {
        $json = $json['results'];
        
        foreach ($json as $item) {
            $item_val = $item['item'];
            if ($i > 0) {
                echo "\n$url,,,,";
            }
            
            echo $item_val['points'] . "," . $item_val['num_comments'] . "," . $item_val['username'] . ",";
            $i++;
        }
    }
    if ($i == 0) { // never submitted
        echo ",,,";
    }
    // points num_comments username
}

$sql = "select comment_count, post_name from wp_posts where post_type = 'post' and post_status='publish';";

$hostname = "127.0.0.1";
$username = "";
$password = "";
$db       = "";

$reddit_name = "";
$reddit_pass = "";

$dbhandle = mysql_connect($hostname, $username, $password);
$selected = mysql_select_db($db, $dbhandle);

$result = mysql_query($sql);

require_once("reddit.php");
$reddit = new reddit($reddit_name, $reddit_pass);

echo "SLUG,COMMENTS,GOOGLE_PLUS,TWITTER,HN_POINTS,HN_COMMENTS,HN_USER,SUBREDDIT,REDDIT_LIKES,REDDIT_SCORE,REDDIT_DOWNS,REDDIT_UPS,REDDIT_NUM_COMMENTS,REDDIT_SUBMISSION,REDDIT_AUTHOR,HN\n";
while ($row = mysql_fetch_array($result)) {
    $url = "http://garysieling.com/blog/" . $row{'post_name'};
    
    echo $url . "," . $row{'comment_count'} . ",";
    echo google_plus_count($url) . "," . twitter_count($url) . ",";
    hn_count($url);
    
    $pageInfo = $reddit->getPageInfo($url);
    $i        = 0;
    if ($pageInfo and $pageInfo->data and $pageInfo->data->children) {
        $children = $pageInfo->data->children;
        
        foreach ($pageInfo->data->children as $submission) {
            
            $submission   = $submission->data;
            $subreddit    = $submission->subreddit;
            $likes        = $submission->likes;
            $score        = $submission->score;
            $downs        = $submission->downs;
            $ups          = $submission->ups;
            $num_comments = $submission->num_comments;
            $num_reports  = $submission->num_reports;
            $author       = $submission->author;
            
            if ($i > 0) {
                echo "\n" . $url . "," . $row{'comment_count'};
                echo ",,,,,,";
            }
            
            echo "$subreddit,$likes,$score,$downs,$ups,$num_comments,$num_reports,$author";
            
            $i++;
        }
    }
    
    echo " 
";
    
}
