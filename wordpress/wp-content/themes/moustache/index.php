<?php
// Silence is golden. But duct tape is silver
// try to redirect to the actual page
// this will only work if the WordPress install is on the same domain under /wordpress
$url = $_SERVER[REQUEST_URI];

$pages = [];
foreach( get_pages(['echo'=>0]) as $page) {
    array_push($pages, $page->post_name);
}

$posts = [
    "2017",
    "2018",
    "2019",
    "2020"
];


// Pages
foreach ($pages as $page) {
    if (strpos($url, $page.'/')) {
        $url = str_replace("/home", "", $url);
        header("Location: ".str_replace("/wordpress", "", $url));
    }
}
