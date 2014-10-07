<?php
	/* class Facebook
	 * /src/facebook.class.php
	 */
	require_once 'facebookrequest.class.php';
	
	class Facebook {
		// Array $app. An array of information about the application.
		private $app = Array("id" => null, "secret" => null);
		
		// String $token. The current access token.
		private $token = null;
		
		// Login Dialog. Set a few important variables for using the Login Dialog.
		private $loginDialogVars = Array("base_url" => "https://www.facebook.com/dialog/oauth");
		
		// Graph API. Set a few important variables for using the Graph API.
		private $graphApiVars = Array("base_url" => "https://graph.facebook.com/v2.1");
		
		// Options. Set a few important variables for using sessions. These shouldn't be modified here, but using the Facebook::options() function.
		public static $options = Array("session_prefix" => "fb_");
		
		// function __construct(). Creates a new Facebook object.
		public function __construct($app_id, $app_secret) {
			// Store App ID in Facebook::app["id"].
			if(!is_int($app_id) || !is_numeric($app_id)) throw new Exception("Facebook::__construct(): \$app_id must be an integer.");
			else $this->app["id"] = (int)$app_id;
			
			// Store App Secret in Facebook::app["secret"].
			if(!is_string($app_secret)) throw new Exception("Facebook::__construct(): \$app_secret must be a string.");
			else $this->app["secret"] = $app_secret;
		}
		
		// function graphApi(). Makes a new request to the Graph API.
		public function graphApi($method, $url, $params = Array()) {
			// Everything here is done by the FacebookRequest class.
			return new FacebookRequest($this, $method, $url, $params);
		}
		
		// function getAccessTokenFromCode(). Exchanges the code for an access token.
		public function getAccessTokenFromCode($redirect_url, $code = null) {
			// Check if redirect_url is a url. The redirect_url should be exactly the same as the redirect_url used in the login dialog. (So really, this should just be the same as the current url.)
			if(!filter_var($redirect_url, FILTER_VALIDATE_URL)) throw new Exception("Facebook::getAccessTokenFromCode(): \$redirect_url must be a valid url.");
			
			// Check if code is a string or null.
			if(is_string($code)) $code = trim($code);
			elseif(($code == null) && isset($_GET["code"])) $code = trim($_GET["code"]);
			else throw new Exception("Facebook::getAccessTokenFromCode(): \$code must be a string.");
			
			// Example request: GET /oauth/access_token?client_id={app_id}&client_secret={app_secret}&redirect_uri={redirect_url}&code={code}
			$request = $this->graphApi("GET", "/oauth/access_token", Array(
				"client_id"				=> $this->app["id"],
				"client_secret"			=> $this->app["secret"],
				"redirect_uri"			=> $redirect_url,
				"code"					=> $code
			));
			
			$request->execute();
			parse_str(trim($request->response()), $response);
			
			$this->token = $response["access_token"];
		}
		
		// function verifyAccessToken(). Verifies an access token.
		public function verifyAccessToken($access_token = null) {
			// Check if access_token is string.
			if(!is_string($access_token)) $access_token = $this->token;
			
			// Example request: GET /debug_token?input_token={access_token}&access_token={app_id}|{app_secret}
			$request = $this->graphApi("GET", "/debug_token", Array(
				"input_token"			=> $access_token,
				"access_token"			=> $this->app["id"] . "|" . $this->app["secret"]
			));
			
			$request->execute();
			$response = $request->responseObject();
			
			$this->token = $response["access_token"];
		}
		
		// function loginURL(). Returns the URL for the login dialog.
		public function loginURL($redirect_url, $permissions = Array(), $rerequest = false) {
			// Check if redirect_url is a url. The redirect_url should go to a PHP script on the same domain that runs Facebook::getAccessTokenFromCode().
			if(!filter_var($redirect_url, FILTER_VALIDATE_URL)) throw new Exception("Facebook::loginURL(): \$redirect_url must be a valid url.");
			
			// Check if permissions is an array.
			if(!is_array($permissions)) $permissions = Array();
			
			// Example Login Dialog URL to request a user's email address and friends who also use the application: https://www.facebook.com/dialog/oauth?client_id={app_id}&redirect_uri={redirect_url}&response_type=code&scope=email,user_friends
			
			$url_params = Array(
				"client_id"				=> $this->app["id"],
				"redirect_uri"			=> $redirect_url,
				"response_type"			=> "code",
				"scope"					=> implode(",", $permissions)
			);
			
			if($rerequest == true) $url_params["auth_type"] = "rerequest";
			
			$url = $this->loginDialogVars["base_url"] . "?" . http_build_query($url_params);
			return $url;
		}
		
		// function loginButton(). Returns the URL for the login dialog.
		public function loginButton($button_text, $redirect_url, $permissions = Array(), $rerequest = false) {
			// Check if button_text is a string.
			if(!is_string($button_text)) throw new Exception("Facebook::loginButton(): \$button_text must be a string.");
			
			// Get a Login Dialog URL using the Facebook::loginURL() function.
			$url = $this->loginURL($redirect_url, $permissions, $rerequest);
			
			// Build the html tag.
			$button = "<a href=\"";
			$button .= $url;
			$button .= "\" style=\"background-color:rgb(47,71,122);display:block;min-width:80px;width:calc(100%-20px);padding:10px;text-align:center;color:white;font-family:arial;text-decoration:none;\">";
			$button .= htmlentities($button_text);
			$button .= "</a>";
			
			return $button;
		}
		
		// function loginRedirect(). Redirects to the login dialog.
		public function loginRedirect($redirect_url, $permissions = Array(), $rerequest = false) {
			// Get a Login Dialog URL using the Facebook::loginURL() function.
			$url = $this->loginURL($redirect_url, $permissions, $rerequest);
			
			// Redirect to the Login Dialog.
			header("Location: " . $url, true, 303);
			exit();
		}
		
		// function userProfile(). Fetches the current user's profile.
		public function userProfile($fields = Array()) {
			// Check if fields is an array.
			if(!is_array($fields)) $fields = Array();
			
			$request = $this->graphApi("GET", "/me", Array("fields" => implode(",", $fields)));
			
			$request->execute();
			return $request->responseObject();
		}
		
		// function profilePicture(). Fetches the current user's profile.
		public function profilePicture($width = 50, $height = 50) {
			// Check if width and height are integers.
			if(!is_integer($width) && !is_numeric($width)) $width = 50;
			if(!is_integer($height) && !is_numeric($height)) $height = 50;
			
			$request = $this->graphApi("GET", "/me", Array("fields" => "id,picture.width(" . $width . ").height(" . $height . ")"));
			
			$request->execute();
			$response = $request->responseObject();
			$picture = $response->picture->data;
			
			// Build an <img> tag.
			$picture->tag = "<img src=\"";
			$picture->tag .= $picture->url;
			$picture->tag .= "\" style=\"width:";
			$picture->tag .= $picture->width;
			$picture->tag .= "px;height:";
			$picture->tag .= $picture->height;
			$picture->tag .= "px;\" />";
			
			return $picture;
		}
		
		// function permissions(). Fetches the permissions and returns them in an array.
		public function permissions($rearrange = true) {
			$request = $this->graphApi("GET", "/me/permissions");
			
			$request->execute();
			$response = $request->responseObject();
			
			if($rearrange == false) {
				return $response;
			} else {
				$permissions = new stdClass();
				foreach($response->data as $p) {
					$status = $p->status;
					if($status == "granted") $granted = true; else $granted = false;
					$permissions->{$p->permission} = new stdClass(); // Array("granted" => $granted, "status" => $p->status);
					$permissions->{$p->permission}->granted = $granted;
					$permissions->{$p->permission}->status = $p->status;
				}
				
				return $permissions;
			}
		}
		
		// function permission(). Checks if the permission has been granted. Returns true if true, false if false.
		public function permission($permission) {
			$permissions = $this->permissions();
			
			if(isset($permissions->{$permission}) && ($permissions->{$permission}->granted == true)) {
				return true;
			} else {
				return false;
			}
		}
		
		// function ids(). Fetches the user ids for other apps the user has authorised and are linked to the same business.
		public function ids($rearrange = true) {
			$request = $this->graphApi("GET", "/me/ids_for_business");
			
			$request->execute();
			$response = $request->responseObject();
			
			if($rearrange == false) {
				return $response;
			} else {
				$ids = new stdClass();
				foreach($response->data as $id) {
					$ids->{$id->app->id} = new stdClass(); // Array("app_name" => $id->app->name, "app_namespace" => $id->app->namespace, "app_id" => $id->app->id, "user_id" => $id->id);
					$ids->{$id->app->id}->app_name = $id->app->name;
					$ids->{$id->app->id}->app_namespace = $id->app->namespace;
					$ids->{$id->app->id}->app_id = $id->app->id;
					$ids->{$id->app->id}->user_id = $id->id;
				}
				
				return $ids;
			}
		}
		
		// function deauth(). De-authorises the application, or removes one permission. Once this is called, the user will have to authorise the application again using the Facebook Login Dialog.
		public function deauth($permission = null) {
			$request = $this->graphApi("DELETE", "/me/permissions" . (is_string($permission) ? "/" . $permission : ""));
			
			$request->execute();
			$response = $request->responseObject();
			
			if($response->success == true) return true;
			else return false;
		}
		
		// function pages(). Fetches a list of all the pages the user manages. Requires the manage_pages permission.
		public function pages($rearrange = true) {
			$permissions = $this->permissions(); if(!isset($permissions->manage_pages) || ($permissions->manage_pages->status == "declined"))
				throw new Exception("Facebook::pages(): User has declined the manage_pages permission.");
			
			$request = $this->graphApi("GET", "/me/accounts");
			
			$request->execute();
			$response = $request->responseObject();
			
			if($rearrange == false) {
				return $response;
			} else {
				$pages = new stdClass();
				foreach($response->data as $page) {
					$pages->{$page->id} = new stdClass();
					$pages->{$page->id}->id = $page->id;
					$pages->{$page->id}->name = $page->name;
					$pages->{$page->id}->access_token = $page->access_token;
					$pages->{$page->id}->permissions = $page->perms;
					$pages->{$page->id}->category = $page->category;
					$pages->{$page->id}->category_list = $page->category_list;
				}
				
				return $pages;
			}
		}
		
		// function post(). Posts something to the user's timeline. Requires the publish_actions permission.
		public function post($post2 = Array()) {
			$permissions = $this->permissions(); if(!isset($permissions->publish_actions) || ($permissions->publish_actions->status == "declined"))
				throw new Exception("Facebook::post(): User has declined the publish_actions permission.");
			
			$post = Array();
			if(isset($post2["message"])) $post["message"] = $post2["message"];
			if(isset($post2["link"])) $post["link"] = $post2["link"];
			if(isset($post2["place"])) $post["place"] = $post2["place"];
			if(isset($post2["place"]) && isset($post2["tags"])) $post["tags"] = $post2["tags"];
			
			$request = $this->graphApi("POST", "/me/feed", $post);
			
			$request->execute();
			$response = $request->responseObject();
			
			if(isset($response->id)) {
				return true;
			} else {
				return false;
			}
		}
		
		// function accessToken(). Returns / sets the current access token.
		public function accessToken($token = null) {
			if(is_string($token)) $this->token = $token;
			else return $this->token;
		}
		
		// function options(). Returns / sets an option.
		public function options($name, $value = null) {
			if($value != null) $this->options[$name] = $value;
			else return $this->options[$name];
		}
	}
	
