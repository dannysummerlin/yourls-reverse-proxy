<?php
/*
Plugin Name: Reverse Proxy
Plugin URI: https://github.com/dannysummerlin/yourls-reverse-proxy
Description: Proxies an HTTP call for another API endpoint whenever you put /proxy/ in front of the URL
Version: 1.0
Author: Danny Summerlin
*/

if( !defined( 'YOURLS_ABSPATH' ) ) die();

yourls_add_action('loader_failed','reverseProxy_checkForReverseProxy');
function reverseProxy_checkForReverseProxy($args) {
	if(str_starts_with('/proxy', strtolower($args[0]))) {
		yourls_add_action('pre_redirect', 'reverseProxy_useReverseProxy');
		include( YOURLS_ABSPATH.'/yourls-go.php' );
		exit;
	}
}
function reverseProxy_useReverseProxy($url, $statusCode) {
	// need to verify that GET parameters are kept
	$method = $_SERVER['REQUEST_METHOD'];
	$headers = null; // blank for now
	// likely should check for headers from request - $headerStringValue = $_SERVER['HTTP_XXXXXX_XXXX'];
	$response = yourls_http_request($method, $url, $headers, ($method === 'POST') ? file_get_contents('php://input') : null);
	yourls_status_header($response->status_code);
	if(isset($response->body)) {
		echo $response->body;
	}
}
