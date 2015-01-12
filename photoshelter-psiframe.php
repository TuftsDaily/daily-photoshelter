<?php
class PSIframe {
	const EMBED_CODE = "<object classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' width='{{width}}' height='{{height}}'><param name='movie' value='{{movie}}'></param><param name='wmode' value='{{wmode}}'></param><param name='allowScriptAccess' value='always'></param><param name='allowFullScreen' value='{{fullscreen}}'></param><param name='bgColor' value='{{bgcolor}}'></param><param name='flashvars' value='{{fv}}'></param><!--[if !IE]><!--><object type='application/x-shockwave-flash' data='{{movie}}' width='{{width}}' height='{{height}}' ><param name='wmode' value='{{wmode}}'></param><param name='allowScriptAccess' value='always'></param><param name='allowFullScreen' value='{{fullscreen}}'></param><param name='bgColor' value='{{bgcolor}}'></param><param name='flashvars' value='{{fv}}'></param><!--<![endif]--><a href='{{galleryurl}}'><img src='{{imgsrc}}' alt='' /></a><!--[if !IE]><!--></object><!--<![endif]--></object>";
	const IMG_CODE = "<object width='{{width}}' height='{{height}}'\'><param name='movie' value='{{url}}/swf/imgWidget.swf'></param><param name='allowFullScreen' value='true'></param><param name='FlashVars' value='i={{i_id}}&b={{buy}}'></param><param name='allowScriptAccess' value='always'></param><embed src='{{url}}/swf/imgWidget.swf' type='application/x-shockwave-flash' allowScriptAccess='true' FlashVars='i={{i_id}}&b={{buy}}' allowfullscreen='true' width='{{width}}' height='{{height}}'></embed></object>";

	const BASE_URL = 'http://www.photoshelter.com';

	var $psc;
	
	function __construct(&$client) {
		$this->psc = $client;
	}
	
	function render_session_error(){
		echo '<p class="ps-error-notice notices">Oops!  Looks like your session has expired. <a onClick="window.open(\''.$admin_url.'admin.php?page=photoshelter-admin\');return false" href="#">Click here</a> to log back in.</p>';
	}
	
	function render_tabs($current = 'browse') {
		echo '<div id="media-upload-header">';
		echo '<ul id="sidemenu">';
		if ($current == 'browse') {
			echo '<li><a  class="current" href="'.$this->iframeURL('').'">Galleries</a></li>';
			echo '<li><a href="'.$this->iframeURL('&recent=t').'">Recent Images</a></li>';
		} else {
			echo '<li><a href="'.$this->iframeURL('').'">Galleries</a></li>';
			echo '<li><a class="current" href="'.$this->iframeURL('&recent=t').'">Recent Images</a></li>';
		}
		echo '</ul>';
		echo '</div>';
	}
	
	function render_header($term = '') {
		echo "<div id='searchbox'>";
		echo "<form method='post' action='".$this->iframeURL('')."'>";
		echo "Search images by keyword: ";
		echo "<input name='I_DSC' type='text' value ='" . $term . "'/>";
		echo "<input type='submit' value='Search!' class='button'/>";
		echo "</form>";
		echo "</div>";
	}

	function iframeURL($opts) {
		$iframe_src = "media-upload.php?post_id=$uploading_iframe_ID";
		$iframe_src = apply_filters('iframe_src', "$iframe_src&amp;type=shelter");

		if (!empty($_GET['recent']) && strpos($opts, 'page_url')) 
			$iframe_src .= '&recent=t';

		return $iframe_src . '&amp;TB_iframe=true&amp;height=500&amp;width=640' . $opts;
	}

	function render_sorter($cur_sort_by, $cur_sort_dir){
		$sort_button = "";
		$selected = "";

		$fields = array(
			'name' => 'Name',
			'creation_time' => 'Date created'
		);

		echo "<select id='gal-sorter' data-url='{$this->iframeURL("")}'>";

		foreach ($fields as $field=>$human_readable) {
			if ($field == $cur_sort_by) {
				$selected = "selected";
			}else{
				$selected = "";
			}

			echo "<option $selected value='$field'>$human_readable&nbsp;&nbsp;&nbsp;&nbsp;</option>";
		}

		echo "</select>";
		echo "<button id='sort-dir' class='sort-$cur_sort_dir' data-dir='$cur_sort_dir'>&nbsp;</button>";
	}

       	function listGalleries() {
		try {
			$page = $_GET['page_url'] ? $_GET['page_url'] : null;

			if (isset($_GET['sort_by'])) {
				$sort_by = $_GET['sort_by'];
				$sort_dir = isset($_GET['sort_dir']) ? $_GET['sort_dir'] : 'asc';
			} elseif ($s = get_option('ps_gallery_sort')) {
				$sort_by = $s['sort_by'];
				$sort_dir = $s['sort_dir'];
			} else {
				$sort_by = 'name';
				$sort_dir= 'asc';
			}

			update_option('ps_gallery_sort', array('sort_by' => $sort_by, 'sort_dir' => $sort_dir)); 

			$dat = $this->psc->gal_qry($page, $sort_by, $sort_dir);

		} catch(Exception $e) {
			$this->render_session_error();
			return;
		}
		$this->render_tabs();

		echo '<div style="margin:1em;">';
		echo '<h3 class="media-title">Find an image or embed a gallery</h3>';

		$this->render_header();

		echo "Sort by: ";

		$this->render_sorter($sort_by, $sort_dir);
	
		$gals = $dat['galleries'];
		echo '<ul class="ps_images">';

		foreach($gals as $gallery) {
			echo "<li>";
			echo '<div class="thumb"><a href="' . $this->iframeURL('&amp;G_ID=' . rawurlencode($gallery['id']) .'&amp;G_NAME='.$gallery['name']) . '"><img border="0" src="'.self::BASE_URL.'/gal-kimg-get/'.$gallery['id'].'/t/100?mcSet=t" alt="No Images"/></a></div>';
			echo "<div class='border'>";
			echo '<div class="gal-button-left"><a href="' . $this->iframeURL('&amp;G_ID=' . rawurlencode($gallery['id']) .'&amp;G_NAME='.$gallery['name']) . '">Browse</a></div>';
			echo '<div class="gal-button-right"><a href="'.$this->iframeURL('&amp;G_ID=' . $gallery['id'] . '&amp;embedGallery=t&amp;G_NAME='.$gallery['name']).'">Embed</a></div>';
			echo "<div class='gal-name'><b>".$gallery['name']."</b></div>";
			echo "</div>";
			echo '</li>';
		}
		echo '</ul>';
#		foreach($dat['galleries'] as $gallery) {
#			//if ($gallery["NUM_IMAGES"] > 0) {
#				echo '<tr>';
#				echo '<td class="cover"><a href="'.$this->iframeURL('&amp;G_ID=' . $gallery['id'] .'&amp;G_NAME='.$gallery['name']).'"><img src="'.self::BASE_URL.'/gal-kimg-get/'.$gallery['id'].'/t/50" /></a></td>';
#				echo '<td class="gallery_name"><a href="'.$this->iframeURL('&amp;G_ID=' . rawurlencode($gallery['id']) .'&amp;G_NAME='.$gallery['name']).'">' . $gallery['name'] . '</a></td>';
#				echo '</tr>';
#			//}
#		}
	
		$this->render_pag((array) $dat['pag']);
		echo '</div>';
	}

	function render_pag($pag) {
		$ppg = 100;
		$totalPages = $pag['pages'];

		if ($totalPages > 1) {
			echo "<div class='pagination'><ul>";
			if (!empty($pag['prev'])) {
				$prevLink = $this->iframeURL('&page_url=' . urlencode($pag['prev']));
				echo "<li class='prev'><a href='" .$prevLink . "'><div class='prev-arrow'></div></a></li>";
			} else {
				echo "<li class='prev disabled'><div class='prev-arrow'>&nbsp;</div></li>" ;
			}
		
			for($i = 1; $i <= $totalPages; $i++) {
				$link = $this->iframeURL('&page_url=' . urlencode($pag['next'].'&ppg=' . $ppg . '&page='. $i));
			
				if ($i == ($pag['page'])) {
					echo "<li class='current'>" . ($i) . '</li>';
				} else {
					echo "<li><a href='" . $link . "'>" . ($i) . "</a></li>";
				}
			}
		
			if (!empty($pag['next'])) {
				$nextLink = $this->iframeURL('&page_url=' . urlencode($pag['next']));
				echo "<li class='next'><a href='" .$nextLink . "'><div class='next-arrow'></div></a></li>";
			} else {
				echo "<li class='next disabled'><div class='next-arrow'>&nbsp;</div></li>";
			}
			echo "</ul></div>";
		}
	}

	function listImages($G_ID, $G_NAME) {
		try {
			$dat = $this->psc->img_qry($G_ID);
		} catch(Exception $e) {
			$this->render_session_error();
			return;
		}
		$this->render_tabs();
		echo '<div style="margin:1em;">';
		echo '<a class="backto" href="' . $this->iframeURL('') . '">&laquo; back to galleries</a>';
		if (count($dat['images'])) {
			echo '<h3 class="media-title">Select an image below or <a href="' . $this->iframeURL('&amp;G_ID=' . $G_ID . '&amp;embedGallery=t&amp;G_NAME='.$G_NAME) . '">embed this gallery as a slideshow</a>.</h3>';
			$this->render_images($dat['images'],  $G_ID, $G_NAME);
			$this->render_pag($dat['pag']);
		} else {
			echo '<h3 class="media-title">There are no images in this gallery.</h3>';
		}
		echo '</div>';
	}

	function searchImages($term) {
		try {
			$dat = $this->psc->img_search($term, $_GET['page_url']);
		} catch(Exception $e) {
			$this->render_session_error();
			return;
		}

		$this->render_tabs();
		echo '<div style="margin:1em;">';
		$this->render_header($term);
		echo '<a class="backto" href="' . $this->iframeURL('') . '">&laquo; back to galleries</a>';
		if (count($dat['images']) == 0) {
			echo '<h2><em>No results found. Please note that images must be marked as searchable to be found here.</em></h2>';
		} else {
			echo '<h3 class="media-title">Select an image</h3>';
			$this->render_images($dat['images']);
			$this->render_pag($dat['pages']);
		}
		echo "</div>";
	}

	function render_images($images, $G_ID = null, $G_NAME = null) {
		echo '<ul class="ps_images ps-img-thumbs">';
	
		foreach($images as $image) {
			$options = '&amp;I_ID=' .$image['id'];
		
			if($G_ID) {
				$options .= '&amp;G_ID=' . $G_ID . '&amp;G_NAME=' . $G_NAME;
			}
		
			echo '<li><a href="' . $this->iframeURL($options) . '">';
			echo '<div class="thumb">';
			echo '<img border="0" src="'.self::BASE_URL.'/img-get/'.$image['id'].'/t/100"/></div>';
            echo '<div class="border">';
			echo '<div class="caption"><b>' . $image['file_name'] . '</b></div>';
			echo '</div></a></li>';
		}
		echo '</ul>';
	}

	function embedImg($I_ID, $G_ID, $G_NAME) {

		$fSrch = ($_GET['searchable'] == 't');
		try {
			if ($fSrch) 
				$this->psc->img_searchable($I_ID);

			$image = $this->psc->img_get($I_ID);

		} catch(Exception $e) {
			$this->render_session_error();
			return;
		}
		
		if (stripos($_SERVER['HTTP_REFERER'], 'recent') != false) {
			$this->render_tabs('recent');
		} else {
			$this->render_tabs();
		}
		
		echo '<div style="margin:1em;">';
		

		$default_width = get_option('photoshelter_default_width', '600');
		$srchOpt = '&amp;searchable=t&amp;I_ID=' . $image['id'];
		
		if ($G_ID) {
			echo '<a class="backto" href="' . $this->iframeURL('&amp;G_ID=' . $G_ID) . '">&laquo; back to gallery</a>';
			$srchOpt .= '&amp;G_ID=' . $G_ID . '&amp;G_NAME=' . $G_NAME;
		}

		echo '<h3 class="media-title">Insert an image</h1>';

		if (!$image['is_public']) {
			$link = $this->iframeURL($srchOpt);
            if (empty($G_ID)) {
                echo "<div class='ps-warning'>Warning: This image is not marked as searchable, which means it must be in an <b>Everyone</b> gallery to be viewable to the public. You may also click <a href='" . $link . "'>here</a> to make this image searchable.</div>";
            } else {
                echo "<div class='ps-warning'>Warning: This image cannot be viewed to the public, as it is neither marked as searchable nor in an Everyone gallery. Click <a href='" . $link . "'>here</a> to make this image searchable.</div>";
            }
		}
		if ($image['is_public'] && $fSrch) {
			echo "<div class='ps-success'>Nice! You have made this image searchable. Now, if you insert it into a blog post, anyone will be able to view it.</div>";
		}
		echo '<div class="embed"><div class="thumb_big">';
		echo '<img src="'.self::BASE_URL.'/img-get/'.$I_ID.'/t/200" />';
		echo '</div><div class="embed_form">';
		echo '<form method="POST" action="'.$this->iframeURL('').'">';
		echo '<p><label>Width:</label><input name="WIDTH" size="4" value="'.$default_width.'" />px (max 1000px)</p>';
		echo '<p><input type="radio" name="F_HTML" value="true" CHECKED onClick="jQuery(\'#buy\').hide();" /><label> as HTML</label></p>';
		echo '<p><input type="radio" name="F_HTML" value="false" onClick="jQuery(\'#buy\').show();"/><label> as Flash object (stronger security)</label></p>';
		echo '<p id="buy" style="display:none">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="F_BAR" value="true"/><label>Disable "Buy" button on priced images</label></p>';
		echo '<p><input type="checkbox" name="F_CAP"><label>Include caption</label><p>';
		echo '<input type="submit" value="Insert image" class="button-primary ps_login_input show" />';
		echo '<input type="hidden" name="I_ID" value="'.$I_ID.'"/>';
		echo '<input type="hidden" name="G_ID" value="'.$G_ID.'"/>';
		echo '<input type="hidden" name="G_NAME" value="'.$G_NAME.'"/>';
		echo '</form></div></div>';
		echo '</div>';
	}

	function insertImg($I_ID, $G_ID, $width, $f_html, $f_bar = false, $g_name, $f_cap = false) {
		update_option('photoshelter_default_width', $width);
		$image = $this->psc->img_widget_get($I_ID);

		$pattern = '/^http:\/\/([^.]*).photoshelter.com/'; 
		preg_match($pattern, (string) $image['channel']->item->link, $label);

		$img_width = (int) $image['channel']->item->children('ps', true)->width;
		$img_height = (int) $image['channel']->item->children('ps', true)->height;
	
		$caption = (string) $image['channel']->item->children('media', true)->description;
		$credit = (string) $image['channel']->item->children('media', true)->credit;	

		$ttl = trim($image['channel']->item->children('ps', true)->iptc_title);
		$hdln = trim($image['channel']->item->children('media', true)->title);

		// get file name
		$url_with_filename = (string) $image['channel']->item->enclosure['url'];
		$file_name = preg_split('/\//', $url_with_filename);
		$file_name = $file_name[count($file_name)-1];
		
		//remove extension and replace with .jpg
		$file_name = preg_split('/\./', $file_name);
		
		if (count($file_name > 1)) {
			array_pop($file_name);
		}
		
		$file_name = implode('.', $file_name);
		$file_name .= '.jpg';
	
		if($G_ID) {
			$photoshelter_link = $label[0] . '/gallery-image/' .$this->dashify($g_name) . '/' . $G_ID . '/' . $I_ID;
		} else {
			$photoshelter_link = $label[0] . '/img-show/' . $I_ID;
		}
	
		//casecase title / headline / credit as image title
		$img_title_alt =  (!empty($hdln)) ? $hdln : 'Photo By: ' . $credit;
		$img_title = (!empty($ttl)) ? $ttl : $img_title_alt;
		$img_title = htmlentities($img_title, ENT_QUOTES, "UTF-8");

		//caption and credit as ALT
		$alt = trim($caption) . ' (' . trim($credit) . ')';
		$alt = htmlentities($alt, ENT_QUOTES, "UTF-8");
		
		if (isset($f_bar) && $f_bar == true) {
			$b = 0;
		} else {
			$b = 1;
		}
		
		$height = floor($img_height * ($width / $img_width));
		
		if ($f_html == 'true') {
			$insert_string = '<a href="'.$photoshelter_link.'"><img src="' . self::BASE_URL . '/img-get/' . $I_ID .'/s/'. $width.'/' . $height . '/' .$file_name . '" title="'.$img_title.'" alt="' .$alt . '" width="' . $width .'"></a>';

			if($f_cap == 'on') {
				$insert_string = '[caption id="ps_captionIns" align="alignnone" width="'.$width.'"]' . $insert_string . $alt . '[/caption]' ;
			}
		} else {
			$height += 20;
			//$insert_string = '[photoshelter-img width="'.$width.'" height="'.$height.'" i_id="'.$I_ID.'" buy="'.$b.'"]';

			if($f_cap == 'on') {
				$insert_string = '[photoshelter-img i_id="'.$I_ID.'" buy="'.$b.'" caption="' . $alt . '" width="' .$width. '" height="' . $height . '"]';
			} else {
				$insert_string = '[photoshelter-img i_id="'.$I_ID.'" buy="'.$b.'" width="' .$width. '" height="' . $height . '"]';
			}
		}

		//make sure not to break the JS sring
		$insert_string = preg_replace('/(?:(?:\r\n|\r|\n)\s*){2}/s', " ", $insert_string);
		$insert_string = preg_replace('/\r\n|\r|\n/s', " ", $insert_string);

		$this->output($insert_string);
	}

	function output($string) {
			echo '<script type="text/javascript">top.send_to_editor(\'' . $string . '\');top.tb_remove();</script>';
	}

	function embedGallery($G_ID, $G_NAME) {
		try {
			$presets = $this->psc->ss_preset_qry();
			$perm = $this->psc->gal_getVis($G_ID);
		} catch (Exception $e) {
			$this->render_session_error();
			return;
		}
		
		$this->render_tabs();
		echo '<div style="margin:1em;">';
		
		$default_width = get_option('photoshelter_default_width', '600');
		
		echo '<a class="backto" href="'.$this->iframeURL('').'">&laquo; back to galleries</a><br/>';
		echo '<h3 class="media-title">' . $G_NAME . '</h3>';
		if ($perm != 'everyone') {
			echo "<div class='ps-warning'>Warning: This gallery is not currently viewable to <b>Everyone</b>, which will prevent proper display in most cases. You may change this setting from the <a href='http://www.photoshelter.com/mem/images'>Image Browser</a> in your PhotoShelter account.</div>";
		}
		echo '<div id="media-items"><h4 class="media-sub-title" style="margin-left: 0px;">Embed Gallery as a Slideshow</h4>';
		echo '<b>Choose a slideshow preset.</b> Presets allow you to control your slideshow’s appearance and the functional elements that are included. To create presets, <a href="http://www.photoshelter.com/login" target="_blank">log into your PhotoShelter account</a> and click the “Embed & Share Slideshow” link from any gallery.</p>';
		echo '<form method="POST" action="'.$this->iframeURL('&embedGallery=t').'" style="margin-left: 0px;">';
		echo '<select name="D_ID">';
		echo '<option value="-1">Default</option>';
		foreach($presets as $preset) {
			echo '<option value="'.$preset['id'].'">' . $preset['name'] . '</option>';
		}
		echo '</select>';
		echo '<input type="hidden" name="G_ID" value="'.$G_ID.'"/>';
		echo '<input type="hidden" name="G_NAME" value="'.$G_NAME.'"/>';
		echo '<input type="submit" value="Embed Gallery Slideshow" class="button-primary ps_login_input show"/>';
		echo '</form>';
		echo '<br/><h4 class="media-sub-title" style="margin-left: 0px;">Embed Gallery with Static Image</h4>';
		echo '<div class="embed"><div class="thumb_big">';
		echo '<img src="'.self::BASE_URL.'/gal-kimg-get/'.$G_ID.'/t/200" />';
		echo '</div><div class="embed_form">';
		echo '<form method="POST" action="'.$this->iframeURL('&embedGalleryStatic=t').'">';
		echo '<p><label>Width:</label><input name="WIDTH" size="4" value="'.$default_width.'" />px (max 1000px)</p>';
		echo '<input type="submit" value="Insert Gallery (Cover Image)" class="button-primary ps_login_input show"/>';
		echo '<input type="hidden" name="G_ID" value="'.$G_ID.'"/>';
		echo '<input type="hidden" name="G_NAME" value="'.$G_NAME.'"/>';
		echo '</form></div></div></div></div>';
	}

	function insertGallery($G_ID, $D_ID, $G_NAME) {
		
		if ($D_ID == '-1') {
			//DEFAULTS
			$pset['width'] = 600;
			$pset['f_fullscreen'] = 't';
			$pset['bgtrans'] = 't';
			$pset['pho_credit'] = "iptc";
			$pset['twoup'] = "f";
			$pset['f_bbar'] = "t";
			$pset['f_bbarbig'] = "f";
			$pset['fsvis'] = "f";
			$pset['f_show_caption'] = "t";
			$pset['crop'] = "f";
			$pset['f_enable_embed_btn'] = "t";
			$pset['f_htmllinks'] = "t";
			$pset['f_l'] = "t";
			$pset['f_send_to_friend_btn'] = "f";
			$pset['f_show_slidenum'] = "t";
			$pset['f_topbar'] = "f";
			$pset['f_show_watermark'] = "t";
			$pset['img_title'] = "casc";
			$pset['linkdest'] = "c";
			$pset['trans'] = "xfade";
			$pset['target'] = "_self";
			$pset['tbs'] = 5000;
			$pset['f_link'] = 't';
			$pset['f_smooth'] = 'f';
			$pset['f_mtrx'] = 't';
			$pset['f_ap'] = 't';
			$pset['f_up'] = 'f';
			$pset['height'] = floor($pset['width']*2/3);
			$pset['btype'] = 'old';
			$pset['bcolor'] = '#CCCCCC';	
		} else {
			try {
				$prst = $this->psc->ss_preset_get($D_ID, $G_ID);
				$pset = (array) $prst->presets;
			} catch (Exception $e) {
				$this->render_session_error();
				return;
			}
		}
		
		$output = "";
		foreach($pset as $key => $value) {
			$output .= $key . '="'.$value.'" ';
		}
		
		$embed_code = '[photoshelter-gallery g_id="'.$G_ID.'" g_name="'.$this->dashify($G_NAME).'" '.$output.']';
	
		$this->output($embed_code);
	}
	
	function insertGalleryImage($G_ID, $G_NAME, $WIDTH = 600) {
		update_option('photoshelter_default_width', $WIDTH);
		$galleryURL = PSIframe::BASE_URL . '/gallery/'.$this->dashify($G_NAME).'/' . $G_ID;
		$keyImg = PSIframe::BASE_URL . '/gal-kimg-get/'.$G_ID.'/s/' . $WIDTH;
		$return = '<a href="' . $galleryURL . '"><img src="' . $keyImg .'"/></a>'; 
		$this->output($return);
	}
	
	function recent_images() {
		try {
			$page_url = (!empty($_GET['page_url'])) ? $_GET['page_url'] : null;
			$dat = $this->psc->img_search(null, $page_url, 'upload_time', 'dsc');
		} catch(Exception $e) {
			$this->render_session_error();
			return;
		}
		$this->render_tabs('recent');
		echo '<div style="margin:1em;">';
		echo '<h3 class="media-title">Select an image</h3>';
		$this->render_images($dat['images']);
		$this->render_pag($dat['pages']);
		echo "</div>";
	}
	
	
	/*!\brief  replaces non-alphas with -'s for SEO friendliness
	 */
	function dashify($string)
	{
		$trA = 	array('\'' => '');

		//downconvert inflected chars from unicode
		//oof, slows conversion by 4x - remove if draining
		$trA["\xE1"] = "a"; $trA["\xC1"] = "A";
		$trA["\xE2"] = "a"; $trA["\xC2"] = "A";
		$trA["\xE0"] = "a"; $trA["\xC0"] = "A";
		$trA["\xE5"] = "a"; $trA["\xC5"] = "A"; 
		$trA["\xE3"] = "a"; $trA["\xC3"] = "A"; 
		$trA["\xC4"] = "A"; $trA["\xE4"] = "a";
		$trA["\xE7"] = "c"; $trA["\xC7"] = "C"; 
		$trA["\xD0"] = "E"; $trA["\xF0"] = "e"; 
		$trA["\xC9"] = "E"; $trA["\xE9"] = "e";
		$trA["\xCA"] = "E"; $trA["\xC8"] = "E"; 
		$trA["\xCB"] = "E"; $trA["\xEB"] = "e";
		$trA["\xCD"] = "I"; $trA["\xED"] = "i"; 
		$trA["\xCE"] = "I"; $trA["\xEE"] = "i";
		$trA["\xCC"] = "I"; $trA["\xEC"] = "i";
		$trA["\xCF"] = "I"; $trA["\xEF"] = "i";
		$trA["\xD1"] = "N"; $trA["\xF1"] = "n";
		$trA["\xD3"] = "O"; $trA["\xE8"] = "e";
		$trA["\xF3"] = "o"; $trA["\xE6"] = "ae";
		$trA["\xD4"] = "O"; $trA["\xF4"] = "o"; 
		$trA["\xF2"] = "o"; $trA["\xD2"] = "O";
		$trA["\xD8"] = "O"; $trA["\xF5"] = "o";
		$trA["\xF8"] = "o"; $trA["\xD5"] = "O"; 
		$trA["\xD6"] = "O"; $trA["\xF6"] = "o"; 
		$trA["\xFE"] = "t"; $trA["\xDE"] = "T";
		$trA["\xDA"] = "U"; $trA["\xFA"] = "u";
		$trA["\xDB"] = "U"; $trA["\xFB"] = "u";
		$trA["\xD9"] = "U"; $trA["\xF9"] = "u"; 
		$trA["\xDC"] = "U"; $trA["\xFC"] = "u";
		$trA["\xDD"] = "Y"; $trA["\xFD"] = "y"; 
		$trA["\xFF"] = "y";

		$string = strtr($string, $trA);
		$string = preg_replace('/[^[:alnum:]]+/', '-', $string);
		$string = trim($string, '-');
		if (!$string) $string = '-'; //in case entire str is dashed
		return rawurlencode($string);
	}
}

?>
