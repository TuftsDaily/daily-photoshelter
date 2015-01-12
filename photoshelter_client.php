<?php
	function dbug($a) {
		echo "<pre>" . print_r($a) . "</pre>";
		//exit();
	}

	class Photoshelter_Client {
	const BASE = 'http://www.photoshelter.com/';
	const BASE_URL = 'https://www.photoshelter.com/psapi/v1/';
		
	var $uri;
	var $username;
	var $password;
	var $first_name;
	var $last_name;
	var $account_type;
	var $user_id;
	var $logged_in;
	var $requst_attempts;
						

	function __construct() {
		$this->logged_in = false;
	}
	
	function __destruct() {}
	
	function auth() {
		$options = get_option('photoshelter');
		$authUrl = Photoshelter_Client::BASE_URL . 'authenticate?email=' . $this->good_encode($options['username']) . "&password=" . $this->good_encode($options['password']);
		$sessUrl = Photoshelter_Client::BASE_URL . 'user/session';
		$data = $this->make_request('GET', $authUrl, false);
		

		if( is_wp_error( $data ) )
		{
			update_option('photoshelter_logged_in', __('Invalid Login', 'photoshelter') );
			$this->logged_in = false;
			return false;
		}
		
		$sess = $this->make_request('GET', $sessUrl, true);
		if ( $data->response->status == 'ok' ) {
			$this->user_id = (string) $sess->response->data->id;
			
			$new_options = array(
				'user_id'		=> $this->user_id
			);
						
			if (isset($data->response->data->org)) {
				$r = (array) $data->response->data;
				$o = $r['org'];

				if (is_array($o)) {
					$orgs = array();
					foreach($o as $org) {
						array_push($orgs, (array) $org);
					}
					$new_options = array_merge( array('orgs' => $orgs), $new_options);
				} else {
					$new_options = array_merge( array('orgs' => array((array) $o)), $new_options);
				}
				
				
			}
			
			update_option('photoshelter', array_merge( $options, $new_options ) );
			$this->logged_in = true;
			update_option('photoshelter_logged_in', true);
			return true;
		} else {
			$this->logged_in = false;
			$error = strip_tags((string) $data->response->error->message);
			update_option('photoshelter_logged_in', $error );
			delete_option('ps_cookies');
			return true;
		}

		return false;
	}
	
	function org_auth($O_ID) {
		$options = get_option('photoshelter');

		if ($O_ID == '-1') {

			$new_options = array(
				'auth_org_name'	=> NULL,
				'auth_org_id' => NULL
			);
			
			
			update_option('photoshelter', array_merge( $options, $new_options ) );
			$this->auth();
			return;
		}
				
		$url = Photoshelter_Client::BASE_URL . 'organization/' . $O_ID . '/authenticate';
		
		$data = $this->make_request('GET', $url);
		
		if ( $data->response->status == 'ok') {
			$orgs = $options['orgs'];
			
			foreach($orgs as $org) {
				if ($O_ID == $org['id']) {
					$org_name = $org['name'];
				}
			}
			
			$new_options = array(
				'auth_org_name'		=> $org_name,
				'auth_org_id' => $O_ID
			);
			
			
			update_option('photoshelter', array_merge( $options, $new_options ) );
		} else {
			$error = strip_tags((string) $data->response->error->message);
			update_option('photoshelter_org', $error );
		}
		
		return $data;
	}
	
	function idle(){
		$url = Photoshelter_Client::BASE_URL . 'idle';
		$data = $this->make_request('GET', $url);

		//return (empty($data->response->data->url)) ? null : $data->response->data->url;
		
	}
	
	function gal_qry($page_url=null, $sort_by=null, $sort_dir="asc"){
		$ppg = 100;
		$sort = $sort_by ? "&sort_by=$sort_by&sort_dir=$sort_dir" : "";
		$url = $page_url ? $page_url :  Photoshelter_Client::BASE_URL . 'gallery?&ppg='. $ppg . '&page=1' . $sort;
		$data = $this->make_request('GET', $url);
		$galleries = array();
		
		foreach($data->response->data->gallery as $gallery) {
			array_push($galleries, (array) $gallery);
		}
		
		return array("galleries" => $galleries, "pag" => $data->response->data->paging);
	}
	
	function gal_getVis($g_id) {
		$url = Photoshelter_Client::BASE_URL . 'gallery/' . $g_id . '/visibility';
		$data = $this->make_request('GET', $url);

		return (empty($data->response->data->mode)) ? null : $data->response->data->mode;
	}
		
	function get_custom_url() {
		$url = Photoshelter_Client::BASE_URL . 'user/settings/site-url';
		$data = $this->make_request('GET', $url);

		return (empty($data->response->data->url)) ? null : $data->response->data->url;
	}

	function img_qry($g_id = ''){
		$ppg = 100;
		if (!empty($_GET['page_url'])) {
			$url = $_GET['page_url'];
		} else {
			$url = Photoshelter_Client::BASE_URL . 'image/query';
			if(!empty($g_id)) {
				$url .= '?gallery_id=' . rawurlencode($g_id) . '&ppg=' . $ppg . '&page=1';
			}
		}
		
		$data = $this->make_request('GET', $url);
		
		$images = array();
		
		foreach($data->response->data->image as $img) {
			array_push($images, (array) $img);
		}
		
		return array('images' => $images, 'pag' => (array) $data->response->data->paging);
		
	}
	
	function img_get($i_id) {
		$url  = Photoshelter_Client::BASE_URL . 'image/' . $i_id. '?mcSet=t';
		$data = $this->make_request('GET', $url);
		$img  = (array) $data->response->data;
		$img['is_public'] = $this->img_is_public($i_id);
		return $img;
	}

	function img_is_public($i_id) {
		$url = Photoshelter_Client::BASE_URL . 'image/' . $i_id. '/public';
		$data = $this->make_request('GET', $url, false);
		return ($data->response->data->is_public == 't');
	}

	function img_searchable($i_id) {
		$url  = Photoshelter_Client::BASE_URL . 'image/' . $i_id. '/update?f_searchable=t';
		$data = $this->make_request('GET', $url);
		$img  = (array) $data->response->data;
		return $img;
	}
	
	function img_widget_get($i_id) {
		$url = Photoshelter_Client::BASE . '/rss/imgWidget/' .$i_id. '?mcSet=t';
		$data = $this->make_request('GET', $url, true, 'xml');
		//dbug($data);
		//exit();
		return (array) $data;
	}
	
	function make_request($method, $url, $use_cookie = true, $type='json') {
		$ch = curl_init();
		$qD = (strpos($url, '?')) ? '&' : '?';
		curl_setopt($ch, CURLOPT_URL, $url . $qD . "format=json");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true );
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
		$cv = curl_version();
		if (version_compare($cv['version'], '7.28.1', '<')) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1 );
		}


		switch ($method) {
			case 'POST':
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
				break;
			case 'PUT':
				curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
			case 'DELETE':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
				break;
		}

		if ( $use_cookie ) {
			$cookies = get_option('ps_cookies');
			$last = count( $cookies ) - 1;
			if( $last >= 0 ) {
				curl_setopt( $ch, CURLOPT_COOKIE, $cookies['ch_' . $last]);
			}
		}
		
		$r = curl_exec($ch);
		curl_close($ch);

		if ($cookie = $this->handle_cookie($r))
			update_option('ps_cookies',$cookie);
		
		$return = $this->parse_response($r, $type);
		
		//check for session
		if ($return->response->error && (string) $return->response->error->class == 'SessionRequiredErr') {
			$this->logged_in = false;
			update_option('photoshelter_logged_in', false);
			delete_option('ps_cookies');
			throw new Exception('session error');
		}

						
		return $return;
	}
	
	function parse_response($response, $type='json') {
		$response = preg_split('/\r\n\r\n/', $response, 2);
		if (count($response)) {
			unset($response[0]);
			$body = implode($response);
		} else {
			$body = $response;
		}

		if ($type == 'json') {
			$rObj = new stdClass();
			$rObj->response = json_decode($body);
			return $rObj;
		} else {
			return simplexml_load_string( $body );
		}

	}
	
	function handle_cookie( $response, $delimiter ="\r\n" )	{
		$response_headers = explode( $delimiter, $response );
		$ps_cookie = array();
		preg_match_all( '/^Set-Cookie:\ (.+)/m', implode("\r\n",$response_headers), $cookies );
		foreach( $cookies[1] as $ck => $cv )
		{
			$ps_cookie['ch_' . $ck] = trim($cv);
		}
		if( empty( $ps_cookie ) )
			return false;
			
	 	return $ps_cookie;
	}
	
	function ss_preset_qry() {
		$url = Photoshelter_Client::BASE_URL . 'user/settings/slideshow/query';

		$data = $this->make_request('GET', $url);

		$return = (array) $data->response;

		$psets = (array) $return['data'];

		$presets = array();
		if (is_array($psets) && !empty($psets[0])) {
			foreach($psets as $preset) {
				array_push($presets, (array) $preset);
			}
		} else if (isset($psets)) {
			array_push($presets, (array) $psets);
		}

		return $presets;
	}
	
	function ss_preset_get($d_id, $g_id) {
		$url = Photoshelter_Client::BASE_URL . 'user/settings/slideshow/' . $d_id . '?gallery_id=' . $g_id;

		$data = $this->make_request('GET', $url);
		
		return $data->response->data;
	}
	
	function img_search($term = null, $page_url = null, $sort_by='date', $sort_dir='dsc') {
		$ppg = 100;
		$url = Photoshelter_Client::BASE_URL . '/image/search?sort_by='.$sort.'&sort_dir='.$sort_dir;
		
		if(!empty($term) && !$page) {
			$url .= '&terms=' . urlencode($term);
		}
		
		if (empty($page_url)) 
			$url .= '&ppg=' .$ppg . '&page=1';
		else
			$url = $page_url;

		$options = get_option('photoshelter');
		if (isset($options['auth_org_id'])) {
			$url .= '&org_id='.$options['auth_org_id'];
		} else {
			$url .= '&user_ID='.$options['user_id'];
		}

		$data = $this->make_request('GET', $url);

		$imgs = $data->response->data->image;

		$images = array();
		
		if (count($imgs) > 0) {
		
			foreach($imgs as $img) {
				array_push($images, (array) $img);
			}
		} else if (!empty($imgs)) {
			array_push($images, $imgs);
		}
		
		return array(
			'images' => $images, 
			'pages' => (array) $data->response->data->paging
		);
	}
	
	function good_encode($str) {
		return urlencode(html_entity_decode($str));
	}
}
?>
