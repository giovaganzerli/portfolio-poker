<?php

add_action( 'rest_api_init', 'add_custom_header');
function add_custom_header() {
    remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
    add_filter( 'rest_pre_serve_request', function( $value ) {

        header( 'Access-Control-Allow-Origin: *');
        header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE' );
        header( 'Access-Control-Allow-Credentials: true' );
        
        return $value;
    });
}

add_action( 'rest_api_init', 'add_custom_endpoints');
function add_custom_endpoints() {
    
	$namespace = "poker-plugin/v1";

	$endpoints = array(
		array('url' => '/auth/token', 'method' => 'POST', 'callback' => 'generate_token'),
		array('url' => '/auth/token/validate', 'method' => 'POST', 'callback' => 'validate_token'),
		array('url' => '/auth/token/reset', 'method' => 'POST', 'callback' => 'refresh_token'),
		array('url' => '/consegne/update', 'method' => 'POST', 'callback' => 'update_consegne'),
		array('url' => '/consegne/ddt', 'method' => 'GET', 'callback' => 'get_ddt_consegne'),
		array('url' => '/scadenze/update', 'method' => 'POST', 'callback' => 'update_scadenze'),
		array('url' => '/scadenze/ddt', 'method' => 'GET', 'callback' => 'get_ddt_scadenze')
    );

	//registro tutti gli endpoints
	foreach($endpoints as $e){
		register_rest_route(
			$namespace, //namespace
			$e["url"], //url api, come la chiamo da javascript
			array(
				'methods' => $e["method"],
				'callback' => $e["callback"], //nome funzione php
                'permission_callback' => function($request) {
                    
                    $route = $request->get_route();
                    $method = $request->get_method();
                    $return = false;
                    
                    if(strpos('/poker-plugin/v1/auth/token', $route) !== -1) $return = true;
                    elseif($method == 'GET') $return = true;
                    else $return = is_user_logged_in();
                    
                    return $return;
                }
			)
		);
	}
}
