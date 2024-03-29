<?php
/*
Plugin Name: Reverse Proxy
Plugin URI: https://github.com/dannysummerlin/yourls-reverse-proxy
Description: Proxies an HTTP call for another API endpoint whenever you put /proxy/ in front of the URL
Version: 1.0
Author: Danny Summerlin
*/

if( !defined( 'YOURLS_ABSPATH' ) ) die();

function reverseProxy_useReverseProxy($keyword) {
	if(str_starts_with(str_replace('/','',$keyword),'proxy')) {
		// need to verify that GET parameters are kept
		$method = $_SERVER['REQUEST_METHOD'];
	// Headers are blank for now, but there are two places we might get them from:
	// 1. the request itself - easy enough to scan for HTTP_ and X_ headers and pass them along
	// 2. the short URL setup - tougher because then I need to make an extra setup feature, so v2
		$headers = array();
		$data = array();
		$options = array();
		$contentType = 'application/json';
		if(isset($_SERVER['CONTENT_TYPE']) && !stristr($_SERVER['CONTENT_TYPE'], 'plain'))
			$contentType = $_SERVER['CONTENT_TYPE'];
		if($method === 'POST') {
			$requestData = file_get_contents('php://input');
			try {
				$requestJSON = json_decode($requestData);
				if(isset($requestJSON->{'$content'})) {
					$data = $requestJSON->{'$content'};
					$contentType = $requestJSON->{'$content-type'};
				} else
					$data = $requestData;
			} catch(Exception $e) {
				$data = $requestData;
			}
		}
		$headers[] = 'Content-type: '.$contentType;
		$endpoint = yourls_get_keyword_longurl(str_replace('proxy_', '', $keyword));
		if(isset($endpoint)) {
			$curl = curl_init();
			curl_setopt_array($curl, array(
				CURLOPT_URL => $endpoint,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST => $method,
				CURLOPT_HTTPHEADER => $headers,
				CURLOPT_POSTFIELDS => $data
			));
			$response = curl_exec($curl);
			$err = curl_error($curl);
			curl_close($curl);
			$out = isset($response) ? $response : $err;
		// temporarily allow all for CORS
			header('Access-Control-Allow-Origin: *');
			header('Content-type: '. curl_getinfo($curl, CURLINFO_CONTENT_TYPE));
			yourls_status_header(curl_getinfo($curl, CURLINFO_RESPONSE_CODE));
			if(isset($out->body)) {
				echo $out->body;
			} else
				echo $out;
			die();
		} else
			return $keyword;
	} else
		return $keyword;
}
yourls_add_filter('get_request', 'reverseProxy_useReverseProxy', 99);
