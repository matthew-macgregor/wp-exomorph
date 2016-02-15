<?php

require_once 'wp-exomorph.php';


// Load the XML from a string
$str = file_get_contents('matthewmacgregor.wordpress.2016-02-12.xml');
$posts = \WPExomorph\Posts::from_string($str);

// Load the XML from a file
$posts = \WPExomorph\Posts::from_file('matthewmacgregor.wordpress.2016-02-12.xml');

// Blog-level data is in the Posts object:
echo $posts->title . PHP_EOL;
echo $posts->description . PHP_EOL;

// Access the posts like so (the Posts object is iterable):
foreach($posts as $post) {

    // Do stuff with each post
    echo $post->title . PHP_EOL;
    echo $post->name . PHP_EOL;   
}

// Use the exporter

$export_dir = 'export';
if( file_exists($export_dir) == false ) {
    mkdir($export_dir);
}

$exporter = new \WPExomorph\SimpleMarkdownExporter($posts);
$exporter->set_dir_path($export_dir);
$exporter->export();