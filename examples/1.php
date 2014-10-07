<?php
	// Start a session and load the Facebook library.
	session_start();
	require_once 'src/facebook.class.php';
	
	// Create a new Facebook object.
	$facebook = new Facebook(0000000000000000, "0000000000000000000000000000000000000000");
	
	// Fetch / delete / restore access token.
	if(isset($_GET["code"])) {
		$facebook->getAccessTokenFromCode("https://example.com/facebook-login/1.php");
		$_SESSION["fb_token"] = $facebook->accessToken();
	} elseif(isset($_GET["PATH_INFO"])) {
		if(isset($_SESSION["fb_token"])) unset($_SESSION["fb_token"]);
	} else {
		// Restore the access token if it exists.
		if(isset($_SESSION["fb_token"])) $facebook->accessToken($_SESSION["fb_token"]);
	}
	
	// Try fetching the user's data. If an error is thrown, show a link to the login dialog.
	try {
		$profile = $facebook->userProfile();
		
		// ---------------------------------
		// The user is logged in, you can do whatever you like here.
		// In this example we just print the profile data, along with the profile picture and permissions.
		echo "<pre>" . print_r($profile, true) . "</pre><br /><br />\n\n";
		
		// Profile picture
		echo "<pre>" . print_r($facebook->profilePicture(), true) . "</pre><br /><br />\n\n";
		
		// Permissions
		echo "<pre>" . print_r($facebook->permissions(), true) . "</pre><br /><br />\n\n";
	} catch(Exception $e) {
		echo $facebook->loginButton("Login with Facebook", "https://example.com/facebook-login/1.php", Array("email"));
	}
	
