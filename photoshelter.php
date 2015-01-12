<?php
/*
Plugin Name: PhotoShelter Plugin - Daily Mod
Description: Post your photos and galleries from PhotoShelter.
Author: PhotoShelter and Tufts Daily
Author URI: http://www.photoshelter.com
Plugin URI: http://www.photoshelter.com/help/tut/market/plugin
Version: 1.5.4
License: GPLv2 - http://www.gnu.org/licenses/gpl-2.0.html
*/

register_activation_hook(__FILE__, 'photoshelter_activate');

add_shortcode('photoshelter-gallery', 'photoshelter_gallery_handler');
add_shortcode('photoshelter-img', 'photoshelter_img_handler');
require_once( WP_PLUGIN_DIR . '/photoshelter-official-plugin/photoshelter-psiframe.php');

function photoshelter_gallery_handler($atts, $content = null, $code="") {
	$map = array(
		'bgtrans' => 'bgtrans', 
		'f_l' => 'f_htmllinks', 
		'f_fscr' => 'f_fullscreen', 
		'f_tb' => 'f_topbar', 
		'f_bb' => 'f_bbar', 
		'f_bbl' => 'f_bbarbig', 
		'f_fss' => 'fsvis', 
		'f_2up' => 'twoup', 
		'f_crp' => 'crop', 
		'f_wm' => 'f_show_watermark', 
		'f_s2f' => 'f_send_to_friend_btn', 
		'f_emb' => 'f_enable_embed_btn', 
		'f_cap' => 'f_show_caption', 
		'f_sln' => 'f_show_slidenum', 
		'ldest' => 'linkdest', 
		'imgT' => 'img_title', 
		'cred' => 'pho_credit', 
		'trans' => 'trans', 
		'target' => 'target',
		'f_link' => 'f_link',
		'f_smooth' => 'f_smooth',
		'f_mtrx' => 'f_mtrx',
		'tbs' => 'tbs',
		'f_ap' => 'f_ap',
		'f_up' => 'f_up',
		'btype' => 'btype',
		'bcolor' => 'bcolor'
	);
	
	$flsv = '';
	
	foreach($map as $k => $v) {
		$flsv .= '&'.$k.'=';
		if (isset($atts[$v]))  {
			$flsv .= urlencode($atts[$v]);
		}
	}

	if (isset($atts['wmds'])) {
		$flsv .= '&wmds=' . urlencode($atts['wmds']);
	}
	
	$fullscreen = $atts['f_fullscreen'] == 't' ? 'true' : 'false';

	$movie = PSIframe::BASE_URL . '/swf/CSlideShow.swf?feedSRC=' . urlencode(PSIframe::BASE_URL . '/gallery/' . $atts['g_id'] . '?feed=json&ppg=1000');

	$keyImg = PSIframe::BASE_URL . '/gal-kimg-get/'.$atts['g_id'].'/s/' . $atts['width'];
	$galleryURL = PSIframe::BASE_URL . '/gallery/'.$atts['g_name'].'/' . $atts['g_id'];

	$embed_code = PSIframe::EMBED_CODE;
	$embed_code = preg_replace('/{{fv}}/', $flsv, $embed_code);
	$embed_code = preg_replace('/{{width}}/', $atts['width'], $embed_code);
	$embed_code = preg_replace('/{{height}}/', $atts['height'], $embed_code);
	$embed_code = preg_replace('/{{bgcolor}}/', $atts['bgcolor'], $embed_code);
	$embed_code = preg_replace('/{{wmode}}/', ($atts['bgtrans'] == 't') ? 'transparent' : 'opaque' , $embed_code);
	$embed_code = preg_replace('/{{fullscreen}}/', $fullscreen, $embed_code);
	$embed_code = preg_replace('/{{movie}}/', $movie, $embed_code);
	$embed_code = preg_replace('/{{imgsrc}}/', $keyImg, $embed_code);
	$embed_code = preg_replace('/{{galleryurl}}/', $galleryURL, $embed_code);
	$embed_code = preg_replace('/{{galleryname}}/', $atts['g_name'], $embed_code);

	
	return $embed_code;
}


function photoshelter_img_handler($atts, $content=null, $code="") {
	$embed_code = PSIframe::IMG_CODE;
	$embed_code = preg_replace('/{{url}}/', PSIframe::BASE_URL, $embed_code);
	$embed_code = preg_replace('/{{i_id}}/', $atts['i_id'], $embed_code);
	$wstr = '';
	if (!empty($atts['width'])) {
		$embed_code = preg_replace('/{{width}}/', $atts['width'], $embed_code);
		$wstr = ' style="width: ' . $atts['width'] . 'px;"';
	}
	if (!empty($atts['height'])) {
		$embed_code = preg_replace('/{{height}}/', $atts['height'], $embed_code);
	}

	$embed_code = preg_replace('/{{buy}}/', $atts['buy'], $embed_code);

	if (!empty($atts['caption'])) {
		$embed_code = '<div id="ps_captionIns" class="wp-caption alignnone"' . $wstr .'>' . $embed_code . '<p class="wp-caption-text">' . $atts['caption'] . '</p></div>';
	}

	return $embed_code;
}

function photoshelter_activate() {
    if (version_compare(phpversion(), '5.0', '<')) {
        trigger_error('', E_USER_ERROR);
	}
}

if ($_GET['action'] == 'error_scrape') {
    die("<p>This PhotoShelter plugin requires PHP 5.0 or higher, cURL and WordPress 2.9 or above. Please contact your web host and ask them enable PHP 5.0 or above and cURL. Please deactivate the PhotoShelter plugin.</p>");
}

function system_requirement_dialog()
{

	$wp_req = $this->verify_wp();
	if( is_wp_error( $wp_req ) )
		return $wp_req->get_error_message();

	$php_req = $this->verify_php();
	if( is_wp_error( $php_req ) )
		return $php_req->get_error_message();
		
	$curl_req = $this->verify_http();
	if( is_wp_error( $curl_req ) )
		return $curl_req->get_error_message();
		
	$simplexml_req = $this->verify_simplexml();
	if( is_wp_error( $simplexml_req ) )
		return $simplexml_req->get_error_message();
	
		
	return true;
}

function verify_wp()
{
	global $wp_version, $wpmu_version;
	$wpver = ( defined('IS_WPMU') ) ? $wpmu_version : $wp_version;
	
	if( version_compare( $wpver, $this->wp_min_req, '<' ) )
		return new WP_Error( 'ps_wp_version_err', sprintf(__('You must be running at least WordPress or WordPress MU %s', 'photoshelter'), $this->wp_min_req ) );
	
	return true;
}

function verify_php()
{
	if( version_compare( PHP_VERSION, '5.0', '<' ) )
		return new WP_Error( 'ps_php_version_err', sprintf(__('Your system must meet the following requirements to use the PhotoShelter Official Plugin: WordPress 2.9 or higher, PHP 5.0 or higher, and cURL. Please upgrade accordingly or contact your web host for help. We recommend deactivating the plugin in the meantime.', 'photoshelter'), $this->php_min_req ) );
		
	return true;
}

function verify_http()
{
	if( !function_exists('curl_exec') )
		return new WP_Error( 'ps_curl_exists', __('Your PHP build does not support cURL. This plugin requires cURL to use the Photoshelter API. Please contact your host and ask them to add cURL support.', 'photoshelter') );
		
	return true;
}

function verify_simplexml()
{
	if( !function_exists('simplexml_load_file') )
		return new WP_Error( 'ps_simplexml_err', __('Your PHP build does not support simplexml. This plugin requires simplexml to use the Photoshelter API. Please contact your host and ask them to add simplexml support.', 'photoshelter') );
		
	return true;
}

function add_menu()
{
	add_menu_page('PS Option page', 'PhotoShelter', 'administrator', 'photoshelter-admin', 'ps_option_page', WP_PLUGIN_URL . '/photoshelter-official-plugin/img/ps_menu_icon.png');
}

function ps_admin_css() {
	?>
	<style type="text/css">
	.ps-ok-notice { background: #0c0; color:#fff }
	.ps-error-notice { background: #c00; color: #fff }
	.ps-error-notice a { color: #fff; text-decoration: underline; font-weight: bold }
	.notices { padding:5px; font-weight:bold;}
	.ps_meta_box { margin-top: 20px; margin-right:10px; margin-bottom:10px; width:47%; min-height:100px; float: left; background: #e3e3e3; border:1px solid #ccc; padding:5px;}
	.ps_hide { display:none }
		.ps_meta_box h3 { margin-top:0}
		.ps_meta_box label, .ps_meta_box input { display:block; }
		.ps_meta_box input { margin-bottom:10px}
		.ps_meta_box input { display:none; }
			.ps_meta_box input.show { display:block !important;}
	.ps_meta_box.wide { width:96%; clear:both; margin:-top: 10px;}
	.pagi_gal img { display:inline; margin:5px;}
	</style>
	<?php
}
function ps_option_page() {			
	global $ps_login_err;
	global $psc;
	
	// force a plugin auth check with idle
	try {
		$auth_chk = $psc->idle();
	} catch (Exception $e) {}
	
	?>
	<div class="wrap">
	<br/>
	<div id="poststuff" class="metabox-holder">
	<div class="postbox" id="ps_login_form">
		<h3 class="hndle"><span><?php _e('Log In','photoshelter'); ?></span></h3>
		<div class="inside">
	
	<?php
	
	$options = get_option('photoshelter');
	$cookie = get_option('ps_cookies');
	$offset = count( $cookie ) - 1;
	$last_cookie = $cookie['ch_' . $offset];
	
	if( $last_cookie ) {
		?>
		<p class="ps-ok-notice notices"><?php echo sprintf(__('You are logged in as %s.', 'photoshelter' ), $options['username']); ?></p>
		<form action="" method="post">
			<?php wp_nonce_field('photoshelter_admin_logout'); ?>
			<input class="hidden" type="hidden" name="photoshelter_logout" id="photoshelter_logout" value="logout" />
			<input type="hidden" class="hidden" name="_wp_http_referer" value="<?php echo esc_url($_SERVER['PHP_SELF']) ?>" />
			<input type="submit" name="photoshelter_logout_submit" id="photoshelter_logout_submit" class="show button" value="<?php _e('Log in as another user','photoshelter') ?>" />
		</form>
	<?php
		if(isset($options['orgs'])) {
	?>
	<h2>Which account would you like to use?</h2>
	<?php
			if(isset($options['auth_org_name'])) {
	?>
	<p class="ps-ok-notice notices"><?php echo sprintf(__('You may now add photos from your %s account.', 'photoshelter' ), $options['auth_org_name']); ?></p>
	<?php
	} else {
	?>
	<p class="ps-ok-notice notices"><?php echo sprintf(__('You may now add photos from your single-user account.', 'photoshelter' ), $options['auth_org_name']); ?></p>
	<?php
	}
	?>
	
	<form action='' method='post'>
	<?php wp_nonce_field('photoshelter_admin_pick_org'); ?>
	<select name='O_ID'>
	<option value="-1">Your single-user account</option>
	<?php 
		foreach($options['orgs'] as $org) {
			if($org['member'] == 't') {
				if ($options['auth_org_id'] == $org['id']) {
					echo '<option value="' . $org['id'] . '" SELECTED>' . $org['name'] . '</option>';	
				} else {
					echo '<option value="' . $org['id'] . '">' . $org['name'] . '</option>';
				}
			}
		}
	?>
	</select>
	<input class="hidden" type="hidden" name="ps_org_auth" id="ps_org_auth" value="ps_org_auth" />
	<input type="submit" name="photoshelter_org_submit" id="photoshelter_org_submit" class="button-primary ps_login_input show" value="Submit" />
	</form>
	<?php
	}
	} else {
	?>
		<?php 
		$ps_en = false;
		if( !get_option('photoshelter') )
		{
			echo '<p class="ps-error-notice notices">' . __('Not Logged In','photoshelter') . '</p>';
			$ps_en = true;
		}
		if (!$psc->logged_in && get_option('photoshelter_logged_in') != '1' && !$ps_en) {
				echo '<p class="ps-error-notice notices">' . get_option('photoshelter_logged_in') . '</p>';
		}
		?>
		
		<form action="" method="post">
			<?php wp_nonce_field('photoshelter_admin_update_username_password'); ?>
		
			<label for="ps_login_name"><?php _e('PhotoShelter Email','photoshelter') ?></label>
			<input type="text" name="ps_login_name" class="ps_login_input show" id="ps_login_name" value="<?php echo esc_attr( $options['username']) ?>"/>
			<label for="ps_login_password"><?php _e('Password','photoshelter') ?></label>
			<input type="password" name="ps_login_password" class="ps_login_input show" id="ps_login_password" value="<?php echo esc_attr( $options['password']) ?>" />
			<input class="hidden" type="hidden" name="ps_login_auth" id="ps_login_auth" value="ps_login_auth" />
			<input type="submit" id="ps_login_submit" class="button-primary ps_login_input show" value="<?php _e('Authorize','photoshelter') ?>" />
			<?php
			if ($ps_login_err) { ?>
			<span style="color: #cc0000;">The email or password you provided is incorrect.</span>
			<?php 
			}
			?>
		</form>
	<?php
	}
	?>
		</div>

	</div>
	<br />
		<?php 

		$wp_has_menu = (version_compare(get_bloginfo('version'), 3) >= 0) ? true : false;

		if ($wp_has_menu) {
			if ($menu_option = get_option('photoshelter_menu_create')) {
				require_once( ABSPATH . 'wp-admin/includes/nav-menu.php' );
				if (!is_nav_menu($menu_option))
					$menu_option = 'f';
			} else {
				$menu_option = 'f';	
			}
		}

		if ( $last_cookie && $wp_has_menu) { ?>
		<div id="poststuff" class="metabox-holder">
		<div class="postbox" id="ps_login_form">
			<h3 class="hndle"><span><?php _e('Add PhotoShelter Menu','photoshelter'); ?></span></h3>
			<div class="inside">
			<?php if ($menu_option == 'f') {  ?>
			Use the PhotoShelter Menu tool to get a jump start on integrating your PhotoShelter and WordPress sites. Click the button below, and a set of links to your PhotoShelter website will be automatically generated and added to WordPress as a menu.
			<br /><br /> <form method="post">
			<?php wp_nonce_field('photoshelter_admin_add_menu'); ?>
			<input class="hidden" type="hidden" name="ps_admin_add_menu" id="ps_admin_add_menu" value="ps_admin_add_menu" />
			<input type="submit" name="photoshelter_add_menu" id="photoshelter_add_menu" class="show button" value="<?php _e('Create PhotoShelter Menu','photoshelter') ?>" />
			</form>
			<?php } else { ?>
			You have successfully added a PhotoShelter menu to your WordPress site. To enable or edit this in your theme, select the PhotoShelter Official Menu from <a href="<?php bloginfo('wpurl'); ?>/wp-admin/nav-menus.php">Appearance -> Menus</a> in your dashboard.
			<?php } ?>
		</div>
		</div>
		<?php } ?>
	</div>

	<?php

}

function process_photoshelter_login()
{
	global $psc;
	
	if( !isset( $_POST['ps_login_auth'] ) ) {
		return false;				
	}
	
	$ps = array();
	$ps['username'] = esc_attr( $_POST['ps_login_name'] );
	$ps['password'] = esc_attr( $_POST['ps_login_password'] );	
	
	if( $options = get_option('photoshelter') ) {
		$photoshelter = array_merge( $options, $ps );
		update_option( 'photoshelter', $photoshelter );
	} else {
		add_option( 'photoshelter', $ps );
	}
	
	check_admin_referer('photoshelter_admin_update_username_password');
	
	try {
		$result = $psc->auth();
	} catch (Exception $e) {
		$GLOBALS['ps_login_err'] = true;
		//do nothing - login invalid
		return;
	}

	wp_safe_redirect( 'admin.php?page=photoshelter-admin' );
}

function process_photoshelter_org() {
	global $psc;
	
	if(!isset($_POST['ps_org_auth'])) {
		return false;
	}
	
	check_admin_referer('photoshelter_admin_pick_org');
	$result = $psc->org_auth($_POST['O_ID']);
	wp_safe_redirect( 'admin.php?page=photoshelter-admin' );
}


function media_upload_shelter() { 
	wp_iframe( 'media_upload_type_shelter');
}

function media_upload_type_shelter() { 
	// wp_iframe content
    //global $wpdb, $wp_query, $wp_locale, $type, $tab, $post_mime_types;
	//global $foldername, $pshttp;
	global $psc;
	

	$cookie = get_option('ps_cookies');
	$offset = count( $cookie ) - 1;
	$last_cookie = $cookie['ch_' . $offset];
	
	if( $last_cookie ) {
		include( plugin_basename('photoshelter-iframe.php') );
	} else {
		$admin_url = admin_url();
		echo '<p class="ps-error-notice notices">Oops!  Looks like you haven\'t entered your <a onClick="window.open(\''.$admin_url.'admin.php?page=photoshelter-admin\');return false" href="#">PhotoShelter account details</a> yet.</p>';
	}
}

function logout()
{
	if( isset($_POST['photoshelter_logout']) && $_POST['photoshelter_logout'] == 'logout' ) {
		check_admin_referer('photoshelter_admin_logout');
		delete_option('ps_cookies');
		delete_option('photoshelter');
		wp_safe_redirect( 'admin.php?page=photoshelter-admin' );
	}
}

function add_photoshelter_menu() {
	if (!isset($_POST['ps_admin_add_menu']))
		return false;

	require_once( ABSPATH . 'wp-admin/includes/nav-menu.php' );
	global $psc;
	
	
	$_nav_menu_selected_id = wp_update_nav_menu_object( 0, array('menu-name' => 'PhotoShelter Menu') );

	if ( is_wp_error( $_nav_menu_selected_id ) ) {
		$messages[] = '<div id="message" class="error"><p>' . $_nav_menu_selected_id->get_error_message() . '</p></div>';
	} else {
		$_menu_object = wp_get_nav_menu_object( $_nav_menu_selected_id );
		$nav_menu_selected_id = $_nav_menu_selected_id;
		$nav_menu_selected_title = $_menu_object->name;
		$messages[] = 'The menu ' . $nav_menu_selected_title .
			' has been created with id ' . $nav_menu_selected_id;
	}

	$custom_url = $psc->get_custom_url();
	if (empty($custom_url)) {
		echo "We were unable to retrieve information about your custom site URL from PhotoShelter.";
	}

	$menu_items = array(
		0 => array('menu-item-url' => $custom_url,
			'menu-item-title' => 'Archive',
			'menu-item-type' => 'custom'),
		1 => array('menu-item-url' => $custom_url . "/gallery-list",
			'menu-item-title' => 'Gallery List',
			'menu-item-type' => 'custom'),
		2 => array('menu-item-url' => $custom_url . "/search-page",
			'menu-item-title' => 'Search Archive',
			'menu-item-type' => 'custom'),
		3 => array('menu-item-url' => $custom_url . "/lbx/lbx-list",
			'menu-item-title' => 'Lightboxes',
			'menu-item-type' => 'custom'),
		4 => array('menu-item-url' => $custom_url . "/cart",
			'menu-item-title' => 'Cart',
			'menu-item-type' => 'custom'),
		5 => array('menu-item-url' => $custom_url . "/login",
			'menu-item-title' => 'Client Login',
			'menu-item-type' => 'custom')
		);
	
	$saved_items = wp_save_nav_menu_items($nav_menu_selected_id, $menu_items);
	$menu = wp_get_nav_menu_object($nav_menu_selected_id);
	$mi = wp_get_nav_menu_items( $menu->term_id, array('post_status' => 'any') );
	foreach ($mi as $miL) {
		$miDb[$miL->ID] = $miL;
	}

	$post_fields = array( 'menu-item-description', 'menu-item-attr-title', 'menu-item-target', 'menu-item-classes', 'menu-item-xfn' );
	foreach ($saved_items as $n => $sid) {
		$c[$sid]['menu-item-db-id'] = $sid;
		$c[$sid]['menu-item-object-id'] = $miDb[$sid]->object_id;
		$c[$sid]['menu-item-object'] = 'custom';
		$c[$sid]['menu-item-type'] = 'custom';
		$c[$sid]['menu-item-parent-id'] = ($n == 0) ?  0 : $saved_items[0];
		$c[$sid]['menu-item-position'] = ($n <= 1) ? 1 : $n;
		$c[$sid]['menu-item-title'] = $miDb[$sid]->post_title;
		$c[$sid]['menu-item-url'] = $miDb[$sid]->url;
		foreach ($post_fields as $pf) {
			$c[$sid][$pf] = '';
		}
	}

	foreach ($c as $k => $args) {
		$menu_item_db_id = wp_update_nav_menu_item( $nav_menu_selected_id, $k, $args);
	}

	update_option('photoshelter_menu_create', $nav_menu_selected_id);
};


function add_photoshelter_button() {
	global $post_ID, $temp_ID;
	//location of wordpress plugin folder
	$pluginURI = WP_PLUGIN_URL . '/photoshelter-official-plugin';
	// the id of the post or whatever for media uploading
	$uploading_iframe_ID = (int) (0 == $post_ID ? $temp_ID : $post_ID);
	$iframe_src = "media-upload.php?post_id=$uploading_iframe_ID";
	$iframe_src = apply_filters('iframe_src', "$iframe_src&amp;type=shelter");
	$title = __('Insert images from your PhotoShelter account');
	echo "<a href=\"{$iframe_src}&amp;TB_iframe=true&amp;height=500&amp;width=640\" class=\"thickbox\" title=\"$title\"><img src=\"{$pluginURI}/img/icon.gif\" alt=\"$title\" /></a>";	
}

function sendCORSHeaders($origin=null, $cred=true, $ttl=3600)
{
	$dflt_hA = array('Accept', 'Accept-Language', 'Accept-Encoding', 'Origin', 'Referer', 'User-Agent', 'Content-Type');
	$hA = array();

        if (empty($origin)) {
                $hA = (function_exists('getallheaders')) ? getallheaders() : array();
                $origin = (!empty($hA['Origin'])) ? $hA['Origin'] : '*';
        }

	if (!empty($hA)) {
		if (!empty($hA['Access-Control-Request-Headers'])) {
			header('Access-Control-Allow-Headers: ' . $hA['Access-Control-Request-Headers']);
		} else {
			header('Access-Control-Allow-Headers: ' . implode(', ', array_keys($hA)));
		}
	}
	else {
		header('Access-Control-Allow-Headers: ' . implode(', ', $dflt_hA ));
	}

        $cred = ($cred) ? 'true' : 'false';

        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: ' . $cred);
        header('Access-Control-Allow-Methods: POST, GET');
        header('Access-Control-Max-Age: ' . $ttl);

        return;
}

function ps_export_headers() {
	sendCORSHeaders();
	if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
		exit();
	}
}

function ps_get_blog_url() {
	if (!empty($_GET['PS_FEEDURL'])) {
		ps_export_headers();
		header('Content-Type: application/json');
		if (function_exists('json_encode'))
			echo json_encode(array('rss_url' => get_bloginfo("rss2_url")));
		else
			echo '{"rss_url":"' . str_replace('/','\/',get_bloginfo("rss2_url")) . '"}';
		exit();
	}
}

include_once( WP_PLUGIN_DIR . '/photoshelter-official-plugin/photoshelter_client.php');

$GLOBALS['psc'] = new Photoshelter_Client();

//$GLOBALS['ps_errors'] = new WP_Error;

add_action( 'send_headers', 'ps_export_headers' );
add_action( 'init', 'ps_get_blog_url' );

add_action( 'init', 'process_photoshelter_login' );
add_action( 'init', 'process_photoshelter_org' );
add_action( 'init', 'add_photoshelter_menu' );
add_action( 'init', 'logout', 1 );

// add media button
add_action( 'admin_menu','add_menu');
add_action( 'admin_head', 'ps_admin_css');

add_action( 'media_buttons', 'add_photoshelter_button', 20);
add_action('media_upload_shelter', 'media_upload_shelter');

?>
