<?php
/**
  Plugin Name: Morgan and Morgan
  Description: Ingests submissions to self.wordpress on the WordPress subreddit 
  Author: William Van Pelt
  Version: 0.1a
 */

namespace MorganAndMorgan;

class Morgan_And_Morgan {

    private $src = 'https://www.reddit.com/r/wordpress.json';
    private $before_option_name = 'morgan_before';
    private $cron_time_name = 'morgan_cron_time';
    private $before = '';
    private $cron_time = 'hourly';
    
    public function __construct() {
	$this->after = get_option($this->before_option_name, '');
	$this->cron_time = get_option($this->cron_time_name, '');
	
	register_activation_hook(__FILE__, [$this, 'activate']);
	register_deactivation_hook(__FILE__, [$this, 'deactivate']);
	
	add_action('reddit_scrape', [$this, 'run']);
	add_action('admin_menu', [$this, 'options']);
	
	add_filter( 'pre_update_option_' . $this->cron_time_name, [$this, 'update_option'], 10, 2 );
    }
    
    /**
     * Once the scheduler is updated
     * reset cron.
     * 
     * @param mixed $new_value
     * @return mixed
     */
    public function update_option($new_value) {
	wp_clear_scheduled_hook('reddit_scrape');
	wp_schedule_event(time(), $new_value, 'reddit_scrape');
	return $new_value;
    }

    /** 
     * Option page
     */
    public function options() {
	add_options_page( 'Morgan and Morgan', 'Morgan and Morgan', 'manage_options', 'morgan-and-morgan', [$this, 'show_options']);	
    }
    
    /**
     * Show options page for cron scheduler
     */
    public function show_options() { ?>
	<div class="wrap">
	    <h2>Morgan and Morgan Options</h2>
	    <form method="post" action="options.php">
		<?php wp_nonce_field('update-options') ?>
		<p><strong>Cron Time:</strong><br />
		    <select name="<?php echo $this->cron_time_name; ?>">
			<option value="hourly"<?php echo get_option($this->cron_time_name) === 'hourly' ? ' selected="selected"' : ''; ?>>Hourly</option>
			<option value="twicedaily"<?php echo get_option($this->cron_time_name) === 'twicedaily' ? ' selected="selected"' : ''; ?>>Twice Daily</option>
			<option value="daily"<?php echo get_option($this->cron_time_name) === 'daily' ? ' selected="selected"' : ''; ?>>Daily</option>
		    </select>
		</p>
		<p><input type="submit" name="Submit" value="Save" /></p>
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="page_options" value="<?php echo $this->cron_time_name; ?>" />
	    </form>
	</div><?php
    }
    
    
    /**
     * Activate Plugin
     * Set cron job hourly
     */
    public function activate() {
	wp_schedule_event(time(), $this->cron_time, 'reddit_scrape');
    }
    
    /**
     * Deactivate Plugin. 
     * Kill Cron Job
     */
    public function deactivate() {
	wp_clear_scheduled_hook('reddit_scrape');
    }
    
    /**
     * Run the query to grab the reddit posts 
     * and store them.
     * 
     * @return boolean
     */
    public function run() {
	$src = $this->src . (!empty($this->before) ? '?before=' . $this->before : '');
	$json = file_get_contents($src);
	if (json_last_error() ) {
	    $this->getJsonError();
	}
	$posts = json_decode($json);
	$before = false;
	foreach($posts->data->children as $post) {
	    if (false === $before) {
		update_option($this->before_option_name, $post->data->name);
		$before = true;
	    }
	    
	    if ( strtolower($post->data->domain) === 'self.wordpress') {
		$url = $post->data->url;
		$created = $post->data->created_utc;
		$ups = $post->data->ups;
		$author = $post->data->author;
		$name = $post->data->name;
		
		if (false === $this->checkIfNameExists($name)) {
		    $new_post = [
			'post_title' => wp_strip_all_tags( $post->data->title ),
			'post_content' => $post->data->selftext,
			'post_status' => 'publish',
			'post_author' => 1,
			'post_date' => date('Y-m-d H:i:s', $created)
		    ];

		    $post_id = wp_insert_post($new_post);

		    add_post_meta($post_id, 'reddit_name', $name);
		    add_post_meta($post_id, 'reddit_url', $url);
		    add_post_meta($post_id, 'reddit_created_utc', $created);
		    add_post_meta($post_id, 'reddit_ups', $ups);
		    add_post_meta($post_id, 'reddit_author', $author);
		}
	    }
	}
	return true;
    }
    
    /**
     * Check to see if we already have the same post
     * 
     * @param string $name
     * @return boolean
     */
    private function checkIfNameExists($name) {
	$args = [
	   'post_type' => 'post',
	   'meta_query' => [
		[
		   'key' => 'reddit_name',
		   'value' => $name
		]
	   ],
	   'fields' => 'ids'
	];

	$query = new \WP_Query( $args );
	$ids = $query->posts;

	if (!empty($ids) ) {
	    return true;
	}
	return false;
    }
    
    /**
     * Just log some errors if there is any getting the JSON
     */
    private function getJsonError() {
	switch (json_last_error()) {
	    case JSON_ERROR_DEPTH:
		error_log('JSON Error - Maximum stack depth exceeded');
		break;
	    case JSON_ERROR_STATE_MISMATCH:
		error_log('JSON Error - Underflow or the modes mismatch');
		break;
	    case JSON_ERROR_CTRL_CHAR:
		error_log('JSON Error - Unexpected control character found');
		break;
	    case JSON_ERROR_SYNTAX:
		error_log('JSON Error - Syntax error, malformed JSON');
		break;
	    case JSON_ERROR_UTF8:
		error_log('JSON Error - Malformed UTF-8 characters, possibly incorrectly encoded');
		break;
	}	
    }
}
$morgan = new Morgan_And_Morgan();