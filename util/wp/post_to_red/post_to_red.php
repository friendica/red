<?php
/*
Plugin Name: CrossPost to Red Matrix
Plugin URI: http://blog.duthied.com/2011/09/12/friendika-cross-poster-wordpress-plugin/
Description: This plugin allows you to cross post to your Red Matrix account. Extended by Mike Macgirvin from a Friendica cross-posting tool 
Version: 1.2
Author: Devlon Duthied
Author URI: http://blog.duthied.com
*/

/*  Copyright 2011 Devlon Duthie (email: duthied@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define("post_to_red_path", WP_PLUGIN_URL . "/" . str_replace(basename( __FILE__), "", plugin_basename(__FILE__)));
define("post_to_red_version", "1.2");
$plugin_dir = basename(dirname(__FILE__));
$plugin = plugin_basename(__FILE__); 

define("post_to_red_acct_name", "post_to_red_admin_options");

function post_to_red_deactivate() {
	delete_option('post_to_red_seed_location');
	delete_option('post_to_red_acct_name');
	delete_option('post_to_red_user_name');
	delete_option('post_to_red_password');
}

function post_to_red_get_seed_location() {
	return get_option('post_to_red_seed_location');
}

function post_to_red_get_acct_name() {
	return get_option('post_to_red_acct_name');
}

function post_to_red_get_channel_name() {
	return get_option('post_to_red_channel_name');
}

function post_to_red_get_password() {
	return get_option('post_to_red_password');
}

function post_to_red_post($post_id) {

	$post = get_post($post_id);
	
    if (isset($_POST['post_to_red'])) {
		update_post_meta($post_id, 'post_to_red', '1');
	} 

	// if meta has been set
	if (get_post_meta($post_id, "post_to_red", true) === '1') {

		$user_name = post_to_red_get_acct_name();
		$password = post_to_red_get_password();
		$seed_location = post_to_red_get_seed_location();
		$channel = post_to_red_get_channel_name();
		
		if ((isset($user_name)) && (isset($password)) && (isset($seed_location))) {
			// remove potential comments
			$message = preg_replace('/<!--(.*)-->/Uis', '', $post->post_content);

			// get any tags and make them hashtags
			$post_tags = get_the_tags($post_id);
			if ($post_tags) {
				foreach($post_tags as $tag) {
			    	$tag_string .= "#" . $tag->name . " "; 
			  	}
			}

			$message_id = site_url() . '/' . $post_id;

			if (isset($tag_string)) {
				$message .=  "<br />$tag_string";	
			}

			$bbcode = xpost_to_html2bbcode($message);
			
			$url = $seed_location . '/api/statuses/update';
			
			$headers = array('Authorization' => 'Basic '.base64_encode("$user_name:$password"));
			$body = array(
				'title'     => xpost_to_html2bbcode($post->post_title),
				'status'    => $bbcode,
				'source'    => 'WordPress', 
				'namespace' => 'wordpress',
				'remote_id' => $message_id,
				'permalink' => $post->guid
			);
			if($channel)
				$body['channel'] = $channel;
			
			// post:
			$request = new WP_Http;
			$result = $request->request($url , array( 'method' => 'POST', 'body' => $body, 'headers' => $headers));

		}
		
	}
}


function post_to_red_delete_post($post_id) {

	$post = get_post($post_id);
	
	// if meta has been set
	if ((get_post_meta($post_id, "post_to_red", true) == '1') || (get_post_meta($post_id, "post_from_red", true) == '1')) {

		$user_name = post_to_red_get_acct_name();
		$password = post_to_red_get_password();
		$seed_location = post_to_red_get_seed_location();
		$channel = post_to_red_get_channel_name();
		
		if ((isset($user_name)) && (isset($password)) && (isset($seed_location))) {

			$message_id = site_url() . '/' . $post_id;
			$url = $seed_location . '/api/statuses/destroy';
			
			$headers = array('Authorization' => 'Basic '.base64_encode("$user_name:$password"));
			$body = array(
				'namespace' => 'wordpress',
				'remote_id' => $message_id,
			);
			if($channel)
				$body['channel'] = $channel;
			
			// post:
			$request = new WP_Http;
			$result = $request->request($url , array( 'method' => 'POST', 'body' => $body, 'headers' => $headers));

		}
		
	}
}




function post_to_red_displayAdminContent() {
	
	$seed_url = post_to_red_get_seed_location();
	$password = post_to_red_get_password();
	$user_acct = post_to_red_get_acct_name();
	$channel = post_to_red_get_channel_name();
	
	// debug...
	// echo "seed location: $seed_url</br>";
	// echo "password: $password</br>";
	// echo "user_acct: $user_acct</br>";
	
	echo <<<EOF
	<div class='wrap'>
		<h2>CrossPost to Red Matrix</h2>
		<p>This plugin allows you to cross post to your Red Matrix channel.</p>
	</div>
	
	<div class="wrap">
		<h2>Configuration</h2>
		<form method="post" action="{$_SERVER["REQUEST_URI"]}">
			Enter the login details of your Red Matrix account<br /><br />
			<input type="text" name="post_to_red_acct_name" value="{$user_acct}"/> &nbsp;
			Password: <input type="password" name="post_to_red_password" value="{$password}"/> &nbsp;
			Red Matrix URL: <input type="text" name="post_to_red_url" value="{$seed_url}"/> &nbsp;
			Optional channel nickname: <input type="text" name="post_to_red_channel" value="{$channel}"/> &nbsp;
			<input type="submit" value="Save" name="submit" />
		</form>
		<p></p>
	</div>
EOF;

	if(isset($_POST['submit']))	{
		echo "<div style='text-align:center;padding:4px;width:200px;background-color:#FFFF99;border:1xp solid #CCCCCC;color:#000000;'>Settings Saved!</div>";
	}
}

function post_to_red_post_checkbox() {

    add_meta_box(
        'post_to_red_meta_box_id', 
        'Cross Post to Red Matrix',
        'post_to_red_post_meta_content',
        'post',
        'normal',
        'default'
    );
}

function post_to_red_post_meta_content($post_id) {
    wp_nonce_field(plugin_basename( __FILE__ ), 'post_to_red_nonce');
    echo '<input type="checkbox" name="post_to_red" value="1" /> Cross post?';
}

function post_to_red_post_field_data($post_id) {

    // check if this isn't an auto save
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;

    // security check
	if((! array_key_exists('post_to_red_nonce', $_POST))
    || (!wp_verify_nonce( $_POST['post_to_red_nonce'], plugin_basename( __FILE__ ))))
        return;

    // now store data in custom fields based on checkboxes selected
    if (isset($_POST['post_to_red'])) {
		update_post_meta($post_id, 'post_to_red', '1');
	} 
}

function post_to_red_display_admin_page() {
	
	if ((isset($_REQUEST["post_to_red_acct_name"])) && (isset($_REQUEST["post_to_red_password"]))) {
		
		$password = $_REQUEST["post_to_red_password"];
		$red_url = $_REQUEST["post_to_red_url"];
		
		update_option('post_to_red_acct_name', $_REQUEST["post_to_red_acct_name"]);
		update_option('post_to_red_channel_name', $channelname);
		update_option('post_to_red_seed_location', $red_url);
		update_option('post_to_red_password', $password);
		
	}
	
	post_to_red_displayAdminContent();
}

function post_to_red_settings_link($links) { 
	$settings_link = '<a href="options-general.php?page=xpost-to-redmatrix">Settings</a>'; 
  	array_unshift($links, $settings_link); 
  	return $links; 
}

function post_to_red_admin() {
	add_options_page("Crosspost to redmatrix", "Crosspost to redmatrix", "manage_options", "xpost-to-redmatrix", "post_to_red_display_admin_page");
}

register_deactivation_hook( __FILE__, 'post_to_red_deactivate' );

add_filter("plugin_action_links_$plugin", "post_to_red_settings_link");

add_action("admin_menu", "post_to_red_admin");
add_action('publish_post', 'post_to_red_post');
add_action('add_meta_boxes', 'post_to_red_post_checkbox');
add_action('save_post', 'post_to_red_post_field_data');
add_action('before_delete_post', 'post_to_red_delete_post');

add_filter('xmlrpc_methods', 'red_xmlrpc_methods');


function red_xmlrpc_methods($methods) {
	$methods['red.Comment'] = 'red_comment';
	return $methods;
}

function red_comment($args) {
	global $wp_xmlrpc_server;
	$wp_xmlrpc_server->escape( $args );

	$blog_id  = $args[0];
	$username = $args[1];
	$password = $args[2];
	$post       = $args[3];
	$content_struct = $args[4];

	if ( ! $user = $wp_xmlrpc_server->login( $username, $password ) )
		return $wp_xmlrpc_server->error;

	if ( is_numeric($post) )
		$post_id = absint($post);
	else
		$post_id = url_to_postid($post);

	if ( ! $post_id )
		return new IXR_Error( 404, __( 'Invalid post ID.' ) );
	if ( ! get_post($post_id) )
		return new IXR_Error( 404, __( 'Invalid post ID.' ) );

	$comment['comment_post_ID'] = $post_id;

	$comment['comment_author'] = '';
	if ( isset($content_struct['author']) )
		$comment['comment_author'] = $content_struct['author'];

	$comment['comment_author_email'] = '';
	if ( isset($content_struct['author_email']) )
		$comment['comment_author_email'] = $content_struct['author_email'];

	$comment['comment_author_url'] = '';
	if ( isset($content_struct['author_url']) )
		$comment['comment_author_url'] = $content_struct['author_url'];

	$comment['user_ID'] = 0;

	if ( get_option('require_name_email') ) {
		if ( 6 > strlen($comment['comment_author_email']) || '' == $comment['comment_author'] )
			return new IXR_Error( 403, __( 'Comment author name and email are required' ) );
		elseif ( !is_email($comment['comment_author_email']) )
			return new IXR_Error( 403, __( 'A valid email address is required' ) );
	}

	if(isset($content_struct['comment_id'])) {
		$comment['comment_ID'] = intval($content_struct['comment_id']);
		$edit = true;
	}
	$comment['comment_post_ID']  = $post_id;
	$comment['comment_parent']   = isset($content_struct['comment_parent']) ? absint($content_struct['comment_parent']) : 0;
	$comment['comment_content']  = isset($content_struct['content'])        ? $content_struct['content'] : null;

	do_action('xmlrpc_call', 'red.Comment');

	if($edit) {
		$result = wp_update_comment($comment);
		$comment_ID = $comment['comment_ID'];
	}
	else {
       	$comment_ID = wp_new_comment( $comment );
		if($comment_ID)
			wp_set_comment_status($comment_ID,'approve');
	}

	do_action( 'xmlrpc_call_success_red_Comment', $comment_ID, $args );

	return $comment_ID;
}



// from:
// http://www.docgate.com/tutorial/php/how-to-convert-html-to-bbcode-with-php-script.html
function xpost_to_html2bbcode($text) {
	$htmltags = array(
		'/\<b\>(.*?)\<\/b\>/is',
		'/\<i\>(.*?)\<\/i\>/is',
		'/\<u\>(.*?)\<\/u\>/is',
		'/\<ul.*?\>(.*?)\<\/ul\>/is',
		'/\<li\>(.*?)\<\/li\>/is',
		'/\<img(.*?) src=\"(.*?)\" alt=\"(.*?)\" title=\"Smile(y?)\" \/\>/is',		// some smiley
		'/\<img(.*?) src=\"http:\/\/(.*?)\" (.*?)\>/is',
		'/\<img(.*?) src=\"(.*?)\" alt=\":(.*?)\" .*? \/\>/is',						// some smiley
		'/\<div class=\"quotecontent\"\>(.*?)\<\/div\>/is',	
		'/\<div class=\"codecontent\"\>(.*?)\<\/div\>/is',	
		'/\<div class=\"quotetitle\"\>(.*?)\<\/div\>/is',	
		'/\<div class=\"codetitle\"\>(.*?)\<\/div\>/is',
		'/\<cite.*?\>(.*?)\<\/cite\>/is',
		'/\<blockquote.*?\>(.*?)\<\/blockquote\>/is',
		'/\<div\>(.*?)\<\/div\>/is',
		'/\<code\>(.*?)\<\/code\>/is',
		'/\<br(.*?)\>/is',
		'/\<strong\>(.*?)\<\/strong\>/is',
		'/\<em\>(.*?)\<\/em\>/is',
		'/\<a href=\"mailto:(.*?)\"(.*?)\>(.*?)\<\/a\>/is',
		'/\<a .*?href=\"(.*?)\"(.*?)\>http:\/\/(.*?)\<\/a\>/is',
		'/\<a .*?href=\"(.*?)\"(.*?)\>(.*?)\<\/a\>/is'
	);

	$bbtags = array(
		'[b]$1[/b]',
		'[i]$1[/i]',
		'[u]$1[/u]',
		'[list]$1[/list]',
		'[*]$1',
		'$3',
		'[img]http://$2[/img]' . "\n",
		':$3',
		'\[quote\]$1\[/quote\]',
		'\[code\]$1\[/code\]',
		'',
		'',
		'',
		'\[quote\]$1\[/quote\]',
		'$1',
		'\[code\]$1\[/code\]',
		"\n",
		'[b]$1[/b]',
		'[i]$1[/i]',
		'[email=$1]$3[/email]',
		'[url]$1[/url]',
		'[url=$1]$3[/url]'
	);

	$text = str_replace ("\n", ' ', $text);
	$ntext = preg_replace ($htmltags, $bbtags, $text);
	$ntext = preg_replace ($htmltags, $bbtags, $ntext);

	// for too large text and cannot handle by str_replace
	if (!$ntext) {
		$ntext = str_replace(array('<br>', '<br />'), "\n", $text);
		$ntext = str_replace(array('<strong>', '</strong>'), array('[b]', '[/b]'), $ntext);
		$ntext = str_replace(array('<em>', '</em>'), array('[i]', '[/i]'), $ntext);
	}

	$ntext = strip_tags($ntext);
	
	$ntext = trim(html_entity_decode($ntext,ENT_QUOTES,'UTF-8'));
	return $ntext;
}


