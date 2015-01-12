<script language="javascript">
	var PS_WP_ROOT = '<?php echo get_bloginfo('wpurl'); ?>';
</script>

<style type="text/css">
<?php include( WP_PLUGIN_DIR . '/photoshelter-official-plugin/style.css');?>
</style>

<?php
require_once( WP_PLUGIN_DIR . '/photoshelter-official-plugin/photoshelter-psiframe.php');
echo "<script type='text/javascript' src='https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js'></script>";
echo "<script type='text/javascript' src='" . get_bloginfo('wpurl') . "/wp-content/plugins/photoshelter-official-plugin/main.js'></script>";

global $psc;
$iframe = new PSIFrame($psc);

if (!empty($_GET['page_url'])) {
	$qA = parse_url($_GET['page_url']);
	parse_str($qA['query'], $page);
	$_GET = array_merge($_GET, $page);
}

if (($_GET["G_ID"] || $_GET['gallery_id']) && !$_GET['I_ID'] && !$_GET['embedGallery']) {
	$iframe->listImages($_GET['G_ID'], $_GET['G_NAME']);
} else if ($_GET["I_ID"]) {
	$iframe->embedImg($_GET["I_ID"], $_GET['G_ID'], $_GET['G_NAME']);
} else if ($_POST['I_ID']){
	$iframe->insertImg($_POST['I_ID'], $_POST['G_ID'], $_POST['WIDTH'], $_POST['F_HTML'], $_POST['F_BAR'], $_POST['G_NAME'], $_POST['F_CAP']);
} else if ($_GET['G_ID'] && $_GET['embedGallery']){
	$iframe->embedGallery($_GET['G_ID'], $_GET['G_NAME']);
} else if ($_GET['embedGallery'] && $_POST['G_ID']) {
	$iframe->insertGallery($_POST['G_ID'], $_POST['D_ID'], $_POST['G_NAME']);
} else if ($_GET['embedGalleryStatic']) { 
	$iframe->insertGalleryImage($_POST['G_ID'], $_POST['G_NAME'], $_POST["WIDTH"]);
} else if ($_POST["I_DSC"] || $_GET['I_DSC'] || $_GET['terms']) {
	$term = $_POST["I_DSC"] ? $_POST["I_DSC"] : $_GET["I_DSC"];
	$iframe->searchImages($term);
} else if ($_GET['recent']) {
	$iframe->recent_images();
} else {
	$iframe->listGalleries();
}
?>
