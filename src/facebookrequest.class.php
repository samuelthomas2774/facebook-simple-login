<?php
	/* class FacebookRequest
	 * /src/facebookrequest.class.php
	 */
	class FacebookRequest {
		// Facebook $facebook. The Facebook object used to make this request.
		private $facebook = null;
		
		// Array $request. An array of information about the request.
		private $request = Array("method" => null, "url" => null, "params" => Array());
		
		// Array $response. An array of information about the response. This is filled when the request is executed.
		private $response = Array();
		
		// CURL $curl. A curl handler for the request.
		private $curl = null;
		
		// Graph API. Set a few important variables for using the Graph API.
		private $graphApiVars = Array("base_url" => "https://graph.facebook.com/v2.1");
		
		// function __construct(). Creates a new FacebookRequest object.
		public function __construct($facebook, $method, $url, $params = Array()) {
			// Store facebook object in FacebookRequest::facebook.
			if(!$facebook instanceof Facebook) throw new Exception("FacebookRequest::_construct(): \$facebook must be a Facebook instance.");
			else $this->facebook = $facebook;
			
			// Store method in FacebookRequest::request["method"].
			if(($method != "GET") && ($method != "POST") && ($method != "DELETE")) throw new Exception("FacebookRequest::_construct(): \$method must be either GET, POST or DELETE.");
			else $this->request["method"] = $method;
			
			// Store url in FacebookRequest::request["url"].
			if(!is_string($url)) throw new Exception("FacebookRequest::_construct(): \$url must be a string.");
			else $this->request["url"] = $url;
			
			// Store params in FacebookRequest::request["params"].
			if(!is_array($params)) $this->request["params"] = Array(); // Do not throw an exception here. This variable is not required and has a default value, so just use that if the input cannot be used.
			else $this->request["params"] = $params;
		}
		
		// function execute(). Executes the request.
		public function execute() {
			$this->curl = curl_init();
			
			if(!isset($this->request["params"]["access_token"])) {
				$this->request["params"]["access_token"] = $this->facebook->accessToken();
			}
			
			if(($this->request["method"] == "GET") || ($this->request["method"] == "DELETE")) {
				if(strpos($this->request["url"], "?") !== false) $url = $this->request["url"] . "&" . http_build_query($this->request["params"]);
				else $url = $this->request["url"] . "?" . http_build_query($this->request["params"]);
			} else {
				$url = $this->request["url"];
			}
			
			curl_setopt($this->curl, CURLOPT_URL, $this->graphApiVars["base_url"] . $url);
			curl_setopt($this->curl, CURLOPT_HEADER, false);
			curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
			
			if($this->request["method"] == "GET") {
				
			} elseif($this->request["method"] == "POST") {
				curl_setopt($this->curl, CURLOPT_POST, true);
				curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->request["params"]);
			} elseif($this->request["method"] == "DELETE") {
				curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "DELETE");
			}
			
			$curl_response = curl_exec($this->curl);
			$this->response["headers"] = Array();
			$this->response["body"] = $curl_response;
			
			// Check for errors
			$response = $this->responseObject();
			if(isset($response->error)) {
				throw new Exception($response->error->type . ": " . $response->error->message . " (" . $response->error->code . ")");
			}
		}
		
		// function response(). Returns the response as a string.
		public function response() {
			return $this->response["body"];
		}
		
		// function responseHeaders(). Returns the response headers as an array.
		public function responseHeaders() {
			return $this->response["headers"];
		}
		
		// function responseObject(). Returns the response as an object.
		public function responseObject() {
			$json = json_decode($this->response["body"], false);
			if($json == false) return new stdClass();
			else return $json;
		}
		
		// function responseArray(). Returns the response as an object.
		public function responseArray() {
			$json = json_decode($this->response["body"], true);
			if($json == false) return Array();
			else return $json;
		}
		
		// function close().
		public function close() {
			if($this->curl != null) {
				curl_close($this->curl);
				$this->curl = null;
			}
		}
		
		// function __destruct().
		public function __destruct() {
			$this->close();
		}
	}
	
