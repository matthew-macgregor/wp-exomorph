<?php

namespace WPExomorph;

class Posts implements \Iterator {

	/*
	* Loads the posts object from a valid Wordpress export xml file.
	*/
	public static function from_file($path) {
		if(file_exists($path)) {
			$xml = simplexml_load_file( $path );
			return new Posts($xml);
		} else {
			throw new Exception("$path is not a valid file path.");
		}
	}
	
	/*
	* Loads the posts object from a valid Wordpress export xml string.
	*/
	public static function from_string($str) {
		if( $str )  {
			$xml = new \SimpleXMLElement($str);
			return new Posts($xml);
		} else {
			throw new Exception("Input is not a valid string.");
		}
	}

	/*
	* Extracts data and builds the posts object.
	*/
	private function __construct($xml) {
		if(! $xml ) {
			throw new Exception("XML is missing or invalid.");
		}
	
        $this->position = 0;
		$this->posts = [];
		
		$channel = $xml->channel;
		$namespaces = $channel->getNameSpaces(true);
		
		$this->title = (string) $channel->title;
		$this->link = (string) $channel->link;
		$this->description = (string) $channel->description;
		$this->pub_date = (string) $channel->pubDate;
		$this->language = (string) $channel->language;
		
		$wp_ns	   = $channel->children($namespaces['wp']);
		$this->base_site_url = (string) $wp_ns->base_site_url;
		$this->base_blog_url = (string) $wp_ns->base_blog_url;
		
		$this->categories = [];
		foreach($wp_ns->category as $cat) {
			array_push($this->categories, [
				'term_id' => (string) $cat->term_id,
				'nicename' => (string) $cat->category_nicename,
				'parent' => (string) $cat->category_parent,
				'name' => (string) $cat->cat_name
			]);
		}
		$this->tags = [];
		foreach($wp_ns->tag as $tag) {
			array_push($this->tags, [
				'term_id' => (string) $tag->term_id,
				'slug' => (string) $tag->tag_slug,
				'name' => (string) $tag->tag_name,
			]);
		}
		
		foreach( $channel->item as $content ) {
			array_push($this->posts, new Post($content));
		}
		
	}
	
	/*
	* Exports the posts object to a JSON-formatted string.
	*/
	public function to_json() {
		return json_encode((array) $this, JSON_PRETTY_PRINT);
	}

    /*
    * Implements Iterable.
    */
    function rewind() {
        $this->position = 0;
    }

    /*
    * Implements Iterable.
    */
    function current() {
        return $this->posts[$this->position];
    }
    
    /*
    * Implements Iterable.
    */
    function key() {
        return $this->position;
    }

    /*
    * Implements Iterable.
    */
    function next() {
        ++$this->position;
    }
    
    /*
    * Implements Iterable.
    */
    function valid() {
        return isset($this->posts[$this->position]);
    }

}

class Post {

	/*
	* Extracts the data from each post.
	*/
	function __construct($node) {
		
		$namespaces 	   = $node->getNameSpaces(true);
		$this->title = (string) $node->title;
		$this->link  = (string) $node->pubDate;
		$this->guid = (string) $node->guid;
		$this->description = (string) $node->description;
		
		$wp_ns	   = $node->children($namespaces['wp']);
		$this->id = (string) $wp_ns->post_id;
		$this->date = (string) $wp_ns->post_date;
		$this->date_gmt = (string) $wp_ns->post_date_gmt;
		$this->name = (string) $wp_ns->post_name;
		$this->status = (string) $wp_ns->status;
		$this->type = (string) $wp_ns->post_type;
		$this->is_sticky = (string) $wp_ns->is_sticky;
		$this->password = (string) $wp_ns->post_password;

		$dc_ns 	   = $node->children($namespaces['dc']);
		$this->creator = (string) $dc_ns->creator;
		
		$content_ns = $node->children($namespaces['content']);
		$this->content = (string)$content_ns->encoded;
		
		$excerpt_ns = $node->children($namespaces['excerpt']);
		$this->excerpt = (string)$excerpt_ns->encoded;
		
		$categories = $node->children($namespaces['category']);	
		$this->categories = [];
		
		foreach( $categories->category as $category ) {
			$attributes = $category->attributes();
			$nicename  = (string) $attributes->nicename;
			$display_name = (string) $category;
			$type = (string) $attributes->domain;
			array_push($this->categories, [ 'nicename' => $nicename, 'name' => $display_name, 'type' => $type ]);
		}
	
	}
	
	/*
	* Exports the post object to a JSON-formatted string.
	*/
	function to_json() {
		return json_encode((array) $this, JSON_PRETTY_PRINT);
	}

}

/*
* Provides a common interface for exports.
*/
interface Exporter {

	function export();

}

/*
* Exports all posts to markdown files with JSON-formatted frontmatter. 
*/
class SimpleMarkdownExporter implements Exporter {

	public function __construct($posts) {
		$this->posts = $posts;
		$this->path = "";
	}

	/*
	* Sets the path to a directory where the posts will be outputted. The directory
	* must exist.
	*/
	public function set_dir_path($path) {
		if( file_exists($path) == false ) {
			mkdir(dirname($path), $recursive = true);
		} 
        
        $this->path = $path;
	}

	/*
	* Exports the posts to a series of files. The post name (slug) will be used for 
	* the filename. 
	*/
	public function export() {
		foreach($this->posts as $post) {
			$this->export_post($post);		
		}
	}
	
	/*
	* Extracts a list of categories (or tags), returning a list of category names
	* as strings.
	*/
	private function get_category_names($type, $categories) {
		$category_names = [];
		foreach($categories as $cat) {
			if($cat['type'] == $type) {
				array_push($category_names, $cat['name']);
			}
		}
		return $category_names;
	}
	
	/*
	* Handles exporting the post.
	*/
	private function export_post($post) {
		$title = ($post->title) ? $post->title : "Title Unknown";
		$date = $post->date;
		$status = $post->status;
		$type = $post->type;
		$author = $post->creator;
		$description = $post->description;
		$content = $post->content;
		$name = $post->name;
		$filename = Path::join($this->path,"$name.md");
		$frontmatter = [
			"title" => $title,
			"description" => $description,
			"date" => $date,
			"slug" => $name,
			"categories" => $this->get_category_names('category', $post->categories),
			"tags" => $this->get_category_names('post_tag', $post->categories)
		];
		
		$fm_str = json_encode($frontmatter, JSON_PRETTY_PRINT);
		$content = $fm_str . "\n\n" . $post->content;
		file_put_contents($filename, $content);
		
	}
}

class Path {

	/*
	* Adapted from http://stackoverflow.com/questions/1091107/how-to-join-filesystem-path-strings-in-php
	*/
	static function join() {
		$args = func_get_args();
		$paths = array();
		foreach ($args as $arg) {
			$paths = array_merge($paths, (array)$arg);
		}
		
		$paths = array_map(function($p) { return trim($p, "/"); }, $paths);
		$paths = array_filter($paths);
		return join('/', $paths);
	}
}
