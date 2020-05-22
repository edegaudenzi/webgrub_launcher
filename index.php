<?php
/**
* WebGrub Launcher
* Grub-like Standalone Websites Disambiguation Page
* A completely useless but cool standalone PHP web page in a shape of a developer puppy: 
* the old-school, beloved-by-many, GRUB interface.
*/

// Define couple of labels to store the version of this scritp and the base url.
define('VERSION', '1.0.0');
define('COUNTDOWN', 10);
define('BASE_URL', 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . "://{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['PHP_SELF']));

/**
* HTML template for the GRUB entries.
* Note: if the entry is not a folder but a file containing a URL, then parse it slightly differently.
* @param string $strFilename - File (or folder) name of a sibling of this script.
* @param bool $boolSelected - OPTIONAL, DEFALT false, mark this entry as 'selected'.
* @return string - Boostrap ready HTML of the GRUB entry
*/
function itemTemplate($strFilename, $boolSelected = false) {
	$boolIsDir      = is_dir($strFilename);
	$strDescription = ($boolIsDir ? $_SERVER['HTTP_HOST'] : 'external').' website';
	$strSelected    = $boolSelected ? 'selected' : '';
	$strURI         = $boolIsDir ? BASE_URL."/{$strFilename}" : extract_link_from_file($strFilename);
	return <<<T
<div class="col-12 item {$strSelected}" onclick="setSelected(this)">
	<a href="{$strURI}">{$strFilename} ({$strDescription})</a>
</div>
T;
}

/**
* Search within the first 1K of file content for the first occurrence of a HTTP link and return.
* @param string $strFilename - File (or folder) name of a sibling of this script.
* @return string|false - The URL or boolean false if the processed item is not a file.
*/
function extract_link_from_file($strFilename) {
	if (!is_file($strFilename)) {
		return false;
	}
	$strFileContent = file_get_contents(
		$strFilename, 
		$use_include_path = FALSE, 
		$context = null,
		$offset = 0, 
		$maxlen = 1024
	);
	preg_match('/https*:\/{2}.+/m', $strFileContent, $arrURLs);
	$strURL = reset($arrURLs);
	return $strURL;
}

// Collect, filter and key-reinitialise all the files and folder that can be listed.
$arrFilenames = array_values(array_filter(scandir($directory = '.'), function($strFilename){
	return (is_dir($strFilename) && !in_array($strFilename, ['.', '..']) || !empty(extract_link_from_file($strFilename)));
}));

?>

<!DOCTYPE html>
<html>
<head>
	<title><?=$_SERVER['HTTP_HOST']?> - WebGrub Launcher version <?=VERSION?></title>
	
	<link href="https://fonts.googleapis.com/css2?family=VT323&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
	<style type="text/css">
		body {
			background-color: black;
			color: white;
			font-family: 'VT323', monospace;
			font-size: 18px;
			user-select: none;
		}

		a, a:hover, a:visited {
			color: white;
			font-size: 1.5em;
		}

		h1 {
			color: lightgray;
			font-size: 1.5em;
		}

		.row.title { 
			height: 4em;
		}

		.row.content {
			border: 3px solid white;
		}

		.selected.item {
			background-color: white;
		}

		.selected.item * {
			color: black;
		}
	</style>
</head>
<body>
	<div class="container">
		<div class="row title">
			<div class="col-md-12 text-center">
				<h1>WebGrub Launcher version <?=VERSION?><h1>
			</div>
		</div>
		<div class="row content">
			<?php
			foreach ($arrFilenames as $i => $strFilename) {
				echo itemTemplate($strFilename, $selected = empty($i));
			}
			?>
		</div>
		<br>
		<div class="row footer">
			<div class="offset-md-1 col-md-10">
				<h1>Please press &#8679; and &#8681; to select which entry is highlighted.<br>
				Press enter to boot the selected WEBSITE.</h1>
			</div>
			<div class="col-md-12">
				<h1 id="countdown">The highlighted entry will be executed automatically in <span id="counter"><?=COUNTDOWN?></span>s</h1>
			</div>
		</div>
	</div>
</body>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
<script type="text/javascript">
// Set the last selected item, if exists
setSelected(
	readLastSelectedItem()
);

// Bind keyboard keys
document.onkeydown = (e) => {
	switch(e.which) {
	    case 38: // up
	    	moveBackward($('.selected.item:first'));
		    break;
	    case 40: // down
	    	moveForward($('.selected.item:first'));
		    break;
		case 13: // enter
			try {
				$('.selected.item:first a:first')[0].click();
			} catch(e) {}
		    break;	    
	    default: 
	}
	e.preventDefault();
};

// declare globals and start, stop the countdown
var intCounter        = <?=COUNTDOWN?>;
var intervalCountdown = setInterval(countdown, 1000);
function countdown(action) {
	if (intervalCountdown == null) {
		return;
	} else if (action == "stop") {
		clearInterval(intervalCountdown);
		$('#countdown:first').remove();
		intervalCountdown = null;
	} else if (--intCounter == 0) {
		$(document).trigger(
			$.Event("keydown", {which: 13})
		);
	}
	$('#counter:first').text(intCounter);
}

// move the item selection to the previous sibling
function moveBackward(thisSelector) {
	if ($(thisSelector).prev().length != 0) {
		setSelected($(thisSelector).prev());
	}
}

// move the item selection to the next sibling
function moveForward(thisSelector) {
	if ($(thisSelector).next().length != 0) {
		setSelected($(thisSelector).next());
	}
}

// Read the jq object of the last selected item from the local Storage. 0-Length jqobj otherwise.
function readLastSelectedItem() {
	return $('[href="' + localStorage.getItem('href_last_selected_item') + '"]:first').closest('.item');
}

// select an item
function setSelected(thisSelector) {
	if (typeof thisSelector === 'undefined' || thisSelector.length === 0) {
		return;
	}
	countdown('stop');
	storeLastSelectedItem(thisSelector);
	$('.selected.item').removeClass('selected');
	$(thisSelector).addClass('selected');
}

// Store the last selected item in the localStorage
function storeLastSelectedItem(thisSelector) {
	localStorage.setItem('href_last_selected_item',
		$(thisSelector).find('a:first').attr('href')
	);
}

</script>
</html>

