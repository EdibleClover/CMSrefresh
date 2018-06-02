<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); 




/*Main Class*/

class CMS {
	
	public $Type;

	public $Version;
	/*
		Bool for the type to make switches more 
	
	*/

	const TempDir = './SLtemp/';
	
	
	function __construct(){   //This stuff gets run when I instantiate versioncheck
		$this->Type = $this->SetType(); 
		
		$this->Version = $this->SetVersion();

	}
	// gets the type of CMS we're working with, Sets vars as true or false and sets TYPE
	///This is a bit much, would probably be better to just include something
	public function SetType(){  //todo: add Mangeto
		if (file_exists('./wp-config.php')){
			return 'WordPress';
		}
		elseif (file_exists('./modules') && file_exists('./administrator')) {
			return 'Joomla';
		} 
		elseif (file_exists('./core') && file_exists('./sites')){
			return 'Drupal';
		}
		else{
			return 'I\'m not able to determine the CMS version!';
		}
	}
	
	public function SetVersion() {  
		switch($this->Type){
			case 'WordPress':
				if(file_exists('./wp-includes/version.php'))
				{
					include('./wp-includes/version.php');
					return $wp_version;
				}
				else
				{
					return 'Uh Oh, the Version file appears to be missing or damaged';
				}
				break;
			case 'Joomla':
				$A = './libraries/cms/version/version.php';   //For Joomla < 3.8.x
				$B = './libraries/src/Version.php';  // For Joomla >= 3.8.x  
				//Possible add another control
				if(file_exists($B)){
					return JVersionHelper1($B);
				}
				elseif(file_exists($A)){
					return JVersionHelper2($A);
				}
				else{
					return 'There seems to be a problem with the Version Files';
				}
				break;
			case 'Drupal':
				if(file_exists('./core/lib/Drupal.php'))
				{
					DrupalVersionHelper();
					return Drupal::VERSION;	
				}
				break;
		}
	}	
}
/*

Helper Functions

*/
/*
Unknown Version *.5.3
*/
function DrupalVersionHelper(){
	include_once('./core/lib/Drupal.php');
	return new Drupal;
}
/* For Joomla >= 3.8.x  */
function JVersionHelper1($Path){
	//Have to have this defined to access that file
	define('JPATH_PLATFORM','');
	require_once($Path);
	//namespace 
	$v = new Joomla\CMS\Version;
	return $v::MAJOR_VERSION . '.' . $v::MINOR_VERSION . '.' . $v::PATCH_VERSION;
}
/* For Joomla < 3.8.x */
function JVersionHelper2($Path){
			define('_JEXEC','');  //Another need for older Joomla Versions
			define('JPATH_PLATFORM','');  ///Need this defined or my include dies
			include_once($Path);
			new JVersion;
			
			if(defined('RELEASE')) {  //Check if release is defined as a constant. If not get version with helper function instead
				return JVersion::RELEASE . '.' . JVersion::DEV_LEVEL;
			}
			else {
					$x = new JVersion;
					return $x->getShortVersion();
			}	
}



/*



*/
class Refresh extends CMS {	

	public $DownLoadURL;
	public $Core; 
	public $NewCoreFilesZip;
	public $UndoFilesArray;
	
	
	function __construct(){
		parent::__construct();  
		
		$this->NewCoreFilesZip = $this::TempDir . $this->Type . $this->Version . '.zip'; 
		
		$this->DownLoadURL = $this->SetDL();  
		
		$this->Core = $this->SetCore();
	}
	/* Set the DownLoad URL */

	public function SetDL(){
		switch($this->Type) {
			case 'WordPress':
					return 'https://wordpress.org/wordpress-' . $this->Version . '.zip';
					break;
			case 'Joomla':
					return 'https://github.com/joomla/joomla-cms/archive/' . $this->Version . '.zip';
					break;
			case 'Drupal':
					return '';
					break;
		}
	}
	public function SetCore(){
		switch($this->Type) {
			case 'WordPress':
			/*Easier to turn this into a double level array so that we can split directories and actual files, easier to work with later*/
					return 
					[
						'Files' => 
						['index.php','wp-activate.php','wp-blog-header.php','wp-comments-post.php','wp-cron.php',
						'wp-links-opml.php','wp-load.php','wp-login.php','wp-mail.php','wp-settings.php','wp-signup.php',
						'wp-trackback.php','xmlrpc.php'], 
						
						'Dirs' => 
						['wp-includes','wp-admin']
						
					];
					break;
			case 'Joomla':
					/* These will more than likely need to be modified But this cover all of the directories/ files when Joomla is unpacked */
					return 
					[
					'Files' =>
						
					['.appveyor.yml', '.drone.yml', '.gitignore', '.hound.yml', '.php_cs', '.travis.yml', 'Jenkinsfile', 'LICENSE.txt', 'README.md', 'README.txt', 'RoboFile.dist.ini',
					'RoboFile.php', 'SECURITY.md', 'appveyor-phpunit.xml', 'build.xml', 'codeception.yml', 'composer.json', 'composer.lock', 'htaccess.txt', 'index.php',
					'jenkins-phpunit.xml', 'karma.conf.js', 'phpunit.xml.dist', 'robots.txt.dist', 'travisci-phpunit.xml', 'web.config.txt'
					],
					'Dirs' =>
					[
					"administrator", "bin", "build", "cache", "cli", "components", "images", "includes", "installation", "language", "layouts", "libraries", "logs",
					"media", "modules", "plugins", "templates", "tests", "tmp"
					]
					];
					break;
			case 'Drupal':
					/* ToDo */
					break;
		}	
	}
	public function BackUpCore(){
		
		/*
		To Do:
		Create zip archive within temp dir consisting of all the core files that are to be replaced.
		This is exclusive to Joomla and Drupal, WP can simply be renamed.
		*/	
		
		$undo = [];

		/*  Going to add these all to an array so that they can easily be undone  */	
		

					$z = $this->Core;
					/*Rename Files*/
					foreach($z['Files'] as $file){
						$nFile = $file.'.sl';
						rename($file, $nFile);
						array_push($undo, $nFile);
					}
					echo 'Files Renamed'; 
					/*Rename Directories*/
					foreach($z['Dirs'] as $dir){
						$nDir = $dir.'.sl';
						rename($dir, $nDir);
						array_push($undo, $nDir);
					}
					echo 'Directories Renamed';
					$undo = $this->UndoFilesArray;

	}
	/*Download and unzip a file*/
	public function DownLoadCore() {
		/*Create  temp Directory*/
		if(!file_exists($this::TempDir)){  mkdir($this::TempDir); }
		/* Set the name and location of downloaded file */
		$File= $this->NewCoreFilesZip;
		/*Set the URL*/
		file_put_contents($File, fopen($this->DownLoadURL, 'r'));
			
	}

	public function UndoCoreBackup(){
		
	}
	
	/* Unzipping archives is a pain in the ass, Turns out that unzipping will 'overwrite' the directory but wont actually remove any of its contents. */
	public function UnZip(){
		$ZipFile = $this->NewCoreFilesZip;
		$zip = new ZipArchive();

		if ($zip->open($ZipFile) === TRUE) {
			

				
		$zip->extractTo('../');

			
			$zip->close();
			echo 'Success!!!';
		} else {
			echo 'failed';
		}
	}	

	public function CleanUp(){
		foreach($this->Core['Dirs'] as $dir){
			DeleteRec('./' . $dir .'.sl');
		}
		foreach($this->Core['Files'] as $file){
			unlink('./'. $file. '.sl');
		}
		
		/*Remove Temp*/
		DeleteRec($this::TempDir);
		echo '<br>Completed CleanUP<br>';
	}
}

/*
CleanUp Helper Function for removing stuff recursively
*/
function DeleteRec($dir){
	$files = array_diff(scandir($dir), array('.', '..')); 

	foreach ($files as $file) { 
		(is_dir("$dir/$file")) ? DeleteRec("$dir/$file") : unlink("$dir/$file"); 
	}

	return rmdir($dir); 
}
/*		


###	RUN	###		


*/
	$cms = new CMS;

	$Refresh = new Refresh;
/*	Take POST requests	*/
if($_SERVER['REQUEST_METHOD'] == 'POST'){ 

	
	//var_dump($Refresh);
	//echo $Refresh->Type;
	//echo $Refresh->DownLoadURL;
	//echo $Refresh->Core;
	//echo $Refresh->NewCoreFilesZip;
		//var_dump($_POST);

	if(isset($_POST['BackUpReplace'])) {
		$Refresh->DownLoadCore();
		$Refresh->BackUpCore();
		$Refresh->UnZip();
	}elseif (isset($_POST['CleanUp'])){

		$Refresh->CleanUp();
	}
	
}else{ 	
	
	
	
	
/*




*/


?>
<!doctype html>

<head>
<style>
.container{
		border-radius: 25px;
		padding:15px;
		background: linear-gradient(to bottom left, #05cffa, #e6e6e6);
		height:auto;
}
.heading{
	border-radius: 25px;
	padding:15px;
	background:green;
	background: linear-gradient(to bottom right, #026d19, #09e538);
}
#row1{}
#row2{
	padding:15px;
	height:100px;
}
#row3{
	height:50px;
}

#b1{
	margin:10px;
	border-radius: 25px;
	background: linear-gradient(to bottom, #c41a00, #2d0600);
}
#b2{
	margin:10px;
	border-radius: 25px;
	background: linear-gradient(to bottom, #efb339, #442f04);
}

#row3{
	
}

</style>


<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<script
			  src="https://code.jquery.com/jquery-3.3.1.min.js"
			  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
			  crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
</head>
<body>
<div class="container">
    <div class="row1">
		<div id="AboutCMS" class="col-lg-12">
			<div class = 'heading'>
				<h3><?php echo $cms->Type; ?></h3>
					
				<h6>Version:  <?php echo $cms->Version; ?></h6>
			</div>
		</div>
	</div>
	

	<div id = 'row2' class="row">



	   <div id='b1' class="col-sm">BackUp And Replace Core
	   </div>
	   
	   
	   <div id='b2' class="col-sm">Clean Up
	   </div>
			

	</div>
	
	
	<div id = 'row3' class="row">
	</div>

</div>



</body>
<script>
/* Simple POSTing for communicating with the API created in PHP */

$('#b1').click(function(){
	let SendData= {BackUpReplace:''};
	PostIt(SendData);
})


$('#b2').click(function(){
	let SendData= {CleanUp:''};
	PostIt(SendData);
})





function PostIt(SendData){
	let Post = $.post( "Refresh.php", SendData, function( ReturnData ) {
		$('#row3').append(ReturnData);
		//TODO
	})
	.done(function() {
		
		
	});
};	
</script>
</html>


<?php  } //heres the end of the else statement?>