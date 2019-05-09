<?php
//Get the correct ID for the plugin.
		$thisfile = basename(__FILE__, ".php");

//Initiat Hooks
		add_action('plugins-sidebar', 'createSideMenu', array($thisfile, 'Custom Title'));
		add_action('edit-extras','pageset',array());
		add_action('changedata-save', 'pagesetsav', array());

//Define and Initiat Plugin
		register_plugin(
			$thisfile,	// ID of plugin, should be filename minus php
			'Custom Title',	# Title of plugin
			'1.1',	// Version of plugin
			'ePirat',	// Author of plugin
			'http://epirat.de',	// Author URL
			'This plugin adds the ability to use a custom title tag.',	// Plugin Description
			'plugins',	// Page type of plugin
			'custom_adm'	// Function that displays content
		);

//Initiat Addministration Page
function custom_adm() {
if (isset($_POST['text']) && (!empty($_POST['text']))){
	if (!file_exists(GSDATAOTHERPATH."customtitle/custom.txt")){
		mkdir(GSDATAOTHERPATH."customtitle/");	
	}
	file_put_contents(GSDATAOTHERPATH."customtitle/custom.txt", $_POST['text']);
}
if (file_exists(GSDATAOTHERPATH."customtitle/custom.txt")){
	$text = file_get_contents(GSDATAOTHERPATH."customtitle/custom.txt");
} else {
	$text = "";
}
?>
<h2>Custom Title's Administration</h2>
	<h3>Change title tag:</h3>
	<form method="post" action="">
	<input type="text" name="text" size="80" value="<?php echo($text);?>"/>
	<input type="submit" name="save" value="Save" />
	</form>
	<br /><br />
	<h3>Information:</h3>
	<p>You can use the following variables:</p>
	<ul>
		<li><code>%sitename%</code> – Name of the website (as defined in GetSimple setup)</li>
		<li><code>%pagetitle%</code> – Title of the current page (as defined in the editor - the one display in the page body)</li>
		<li><code>%parenttitle%</code> – Title of the parent's page (if it exists)</li>
		<li><code>%menutitle%</code> – Title of the current page's menu item (if it has one)</li>
	</ul>
	<br />
	<h3>Usage:</h3>
	<p>To activate this plugin, replace in your theme <code>&lt;title&gt;…&lt;/title&gt;</code> with <br /> <code>&lt;title&gt;&lt;?php echo(get_custom_title_tag()); ?&gt;&lt;/title&gt;</code></p>
<?php
}


function get_custom_title_tag() {
	global $data_index;

	if ( (isset($data_index->customtitle)) && (!empty($data_index->customtitle)) ) {
		$title = $data_index->customtitle;
	} else { 
		if (!file_exists(GSDATAOTHERPATH."customtitle/custom.txt")){
			mkdir(GSDATAOTHERPATH."customtitle/");	
			file_put_contents(GSDATAOTHERPATH."customtitle/custom.txt", "%pagetitle% - %sitename%");
		}
		if (file_exists(GSDATAOTHERPATH."customtitle/custom.txt")){	
			$title = file_get_contents(GSDATAOTHERPATH."customtitle/custom.txt");
		}
	}

	$parent = $data_index->parent;
	$file = GSDATAPAGESPATH . $parent .'.xml';
	if (file_exists($file)) {
		$parent = getXML($file);
		$parent = $parent->title;
	} else {
		$parent = "";
	}
	$file = GSDATAOTHERPATH . 'website.xml';
	if (file_exists($file)) {
		$pagename = getXML($file);
		$pagename = $pagename->SITENAME;
	} else {
		$pagename = "";
	}
	if ($rir = str_replace("%sitename%", $pagename, $title)){
		$title = $rir;
	}
	if ($rir = str_replace("%pagetitle%", $data_index->title, $title)){
		$title = $rir;
	}
	if ($rir = str_replace("%parenttitle%", $parent, $title)){
		$title = $rir;
	}
	if ($rir = str_replace("%menutitle%", $data_index->menu, $title)){
		$title = $rir;
	}
	return $title;
}

function pageset(){
	global $data_edit;
	$data = '';
	if (isset($data_edit->customtitle)) {
		$data = $data_edit->customtitle;
	} 
	echo '<tr><td><b>Custom page title:</b> <input type="text" name="customtitle" value="'.$data.'"/>';
	echo '</td>';
	echo '</tr>';
}

function pagesetsav() {
	global $xml;
	if (isset($_POST['customtitle'])) {
		$note = $xml->addChild('customtitle');
		$note->addCData($_POST['customtitle']);
	}
}

?>