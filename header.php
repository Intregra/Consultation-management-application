<?php 
// token for secure AJAX
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['sec_tok']))
	$_SESSION['sec_tok'] = md5(rand(1000,9999));
?>

<!DOCTYPE html>
<html lang="<?php echo $lang->lang; ?>">
<head>
	<meta charset="UTF-8">
	<title><?php echo $title; ?></title>
	<meta name="description" content="<?php echo $lang->other->metaDescription; ?>">
	<meta name="author" content="Pavel Balajka">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="css/jquery-ui.min.css">
	<link rel="stylesheet" href="css/style.css">
	<link rel="shortcut icon" href="assets/favicon.png"/>
	<script src="js/jquery-3.2.1.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script>
		var lang = <?php echo json_encode($lang); ?>;
		var sec_tok = '<?php echo $_SESSION['sec_tok']; ?>';
		var home_url = '<?php echo HOME_URL; ?>';
	</script>
</head>

<body>
<?php 
	$lfiles = scandir('lang');
	$opts = '';
	// foreach ($lfiles as $value) {
	// 	if (preg_match('/^...\.json$/i', $value) == 1)
	// 		$opts .= '<option value="' . $value . ($fname == $value ? '" selected>' : '">') . substr($value, 0, 3) . '</option>';
	// }
	// if (!empty($opts))
	// 	echo '<select id="lang_sel" class="look-like-button">' . $opts . '</select>';
	$chosen = $fname;
	foreach ($lfiles as $value) {
		if (preg_match('/^...\.json$/i', $value) == 1) {
			$seloption = substr($value, 0, 3);
			$opts .= '<a href="' . $seloption . '">' . $seloption . '</a>';
		}
	}
	if (!empty($opts))
		echo '<div id="lang_sel" class="dropdown"><button type="button" class="dropbtn">' . $chosen . '</button><div class="dropcont">' . $opts . '</div></div>';
?>