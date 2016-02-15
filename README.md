# Simple Wordpress Export XML Parser

Wordpress makes it surprisingly easy to get a complete dump of your site's textual content as XML. 
I needed a simple way to slurp in this data and transform it, because I was migrating a blog from
Wordpress to a static site. I needed control of the output because the static site builder I'm 
using is custom (although mostly compatible with Hugo's JSON-formatted frontmatter).

I wrote this simple library to solve this need. It has the following features:

* Single PHP file with no dependencies.
* Insanely simple to use.
* Parses the XML into a nice-to-use object structure. 
* One-line of code provides complete JSON output.
* Sample exporter to Markdown with JSON-formatted frontmatter.

WP-Exomorph isn't really intended to be an export library, but there's a sample exporter provided.
If you write an exporter, please consider contributing it back to the project so that others may
use it.

### Installation

You can install via Composer:

    {
        "repositories": [
            {
                "type": "vcs",
                "url": "https://github.com/matthew-macgregor/wp-exomorph"
            }
        ],
        "require": {
            "matthew-macgregor/wp-exomorph": "0.1.*"
        }
    }

Or just download and include:

    require_once 'wp-exomorph.php';

### Example Usage

You can see this example in action in `example.php`.

    require_once 'wp-exomorph.php';

    // Load the XML from a string
    $str = file_get_contents('matthewmacgregor.wordpress.2016-02-12.xml');
    $posts = \WPExomorph\Posts::from_string($str);

    // OR Load the XML from a file
    $posts = Posts::from_file('matthewmacgregor.wordpress.2016-02-12.xml');

    // Blog-level data is in the Posts object:
    echo $posts->title . PHP_EOL;
    echo $posts->description . PHP_EOL;

    // Access the posts like so (the Posts object is iterable):
    foreach($posts as $post) {

        // Do stuff with each post
        echo $post->title . PHP_EOL;
        echo $post->post_name . PHP_EOL;   
    }

    // Use the exporter
    $exporter = new \WPExomorph\SimpleMarkdownExporter($posts);
    $exporter->set_dir_path('export');
    $exporter->export();

### License

WP-Exomorph is available under the MIT License. See the included LICENSE file for details.