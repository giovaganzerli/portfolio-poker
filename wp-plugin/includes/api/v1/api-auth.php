<?php

use \Firebase\JWT\JWT;

/**
 * Get the user and password in the request body and generate a JWT
 *
 * @param [type] $request [description]
 *
 * @return [type] [description]
 */
function generate_token($request) {
    $secret_key_auth = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : false;
    $secret_key_session = defined('JWT_AUTH_SESSION_SECRET_KEY') ? JWT_AUTH_SESSION_SECRET_KEY : false;
    $username = $request['username'];
	$password = $request['password'];

    /** First thing, check the secret key if not exist return a error*/
    if (!$secret_key_session || !$secret_key_auth) {
        return new WP_Error(
            'jwt_auth_bad_config',
            __('JWT is not configurated properly, please contact the admin', 'wp-api-jwt-auth'),
            array(
                'status' => 403,
            )
        );
    }
    /** Try to authenticate the user with the passed credentials*/
    $user = wp_authenticate($username, $password);

    /** If the authentication fails return a error*/
    if (is_wp_error($user)) {
        $error_code = $user->get_error_code();
        return new WP_Error(
            '[jwt_auth] ' . $error_code,
            $user->get_error_message($error_code),
            array(
                'status' => 403,
            )
        );
    }

    /** Valid credentials, the user exists create the according Token */
    $issuedAt = time();
    $notBefore = apply_filters('jwt_auth_not_before', $issuedAt, $issuedAt);
    $expire_session = apply_filters('jwt_auth_expire', $issuedAt + (DAY_IN_SECONDS * 7), $issuedAt);
    $expire_auth = apply_filters('jwt_auth_expire', $issuedAt + (DAY_IN_SECONDS * 30), $issuedAt);

    $token_session = array(
        'iss' => get_bloginfo('url'),
        'iat' => $issuedAt,
        'nbf' => $notBefore,
        'exp' => $expire_session,
        'data' => array(
            'user' => array(
                'id' => $user->data->ID
            )
        )
    );
    $token_session = JWT::encode(apply_filters('jwt_auth_token_before_sign', $token_session, $user), $secret_key_session);
    
    $token_auth = array(
        'iss' => get_bloginfo('url'),
        'iat' => $issuedAt,
        'nbf' => $notBefore,
        'exp' => $expire_auth,
        'data' => array(
            'ts' => $token_session,
            'user' => array(
                'id' => $user->data->ID
            )
        )
    );
    $token_auth = JWT::encode(apply_filters('jwt_auth_token_before_sign', $token_auth, $user), $secret_key_auth);
    
    update_field('auth-session_token', $token_session, 'user_'. $user->data->ID);

    /** The token is signed, now create the object with no sensible user data to the client*/
    $data = array(
        'token_auth' => $token_auth,
        'token_session' => $token_session,
        'user_id' => $user->data->ID,
        'user_role' => $user->roles[0],
        'user_email' => $user->data->user_email,
        'user_nicename' => $user->data->user_nicename,
        'user_display_name' => $user->data->display_name,
        'user_zone' => get_field('zone', 'user_'. $user->data->ID),
        'user_codice_agente' => get_field('codice_agente', 'user_'. $user->data->ID),
	    'exp_auth' => $issuedAt + (DAY_IN_SECONDS * 7)
    );

    /** Let the user modify the data before send it back */
    return apply_filters('jwt_auth_token_before_dispatch', $data, $user);
}

/**
 * Main validation function, this function try to get the Autentication
 * headers and decoded.
 *
 * @param bool $output
 *
 * @return WP_Error | Object | Array
 */
function validate_token($output = true) {
    /*
     * Looking for the HTTP_AUTHORIZATION header, if not present just
     * return the user.
     */
    $auth = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : false;

    /* Double check for different auth header string (server dependent) */
    if (!$auth) {
        $auth = isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : false;
    }

    if (!$auth) {
        return new WP_Error(
            'jwt_auth_no_auth_header',
            'Authorization header not found.',
            array(
                'status' => 403,
            )
        );
    }

    /*
     * The HTTP_AUTHORIZATION is present verify the format
     * if the format is wrong return the user.
     */
    list($token) = sscanf($auth, 'Bearer %s');
    if (!$token) {
        return new WP_Error(
            'jwt_auth_bad_auth_header',
            'Authorization header malformed.',
            array(
                'status' => 403,
            )
        );
    }

    /** Get the Secret Key */
    $secret_key = defined('JWT_AUTH_SESSION_SECRET_KEY') ? JWT_AUTH_SESSION_SECRET_KEY : false;
    if (!$secret_key) {
        return new WP_Error(
            'jwt_auth_bad_config',
            'JWT is not configurated properly, please contact the admin',
            array(
                'status' => 403,
            )
        );
    }
    /** Try to decode the token */
    try {
        $token = JWT::decode($token, $secret_key, array('HS256'));
        /** The Token is decoded now validate the iss */
        if ($token->iss != get_bloginfo('url')) {
            /** The iss do not match, return error */
            return new WP_Error(
                'jwt_auth_bad_iss',
                'The iss do not match with this server',
                array(
                    'status' => 403,
                )
            );
        }
        // So far so good, validate the user id in the token
        if (!isset($token->data->user->id)) {
            // No user id in the token, abort!!
            return new WP_Error(
                'jwt_auth_bad_request',
                'User ID not found in the token',
                array(
                    'status' => 403,
                )
            );
        }
        /** Everything looks good return the decoded token if the $output is false */
        if (!$output) {
            return $token;
        }
        /** If the output is true return an answer to the request to show it */
        return array(
            'code' => 'jwt_auth_valid_session_token',
            'data' => array(
                'status' => 200,
            )
        );
    } catch (Exception $e) {
        return new WP_Error(
            'jwt_auth_invalid_session_token',
            $e->getMessage(),
            array(
                'status' => 403,
            )
        );
    }
}

function refresh_token($request) {
    /*
     * Looking for the HTTP_AUTHORIZATION header, if not present just
     * return the user.
     */
    $auth = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : false;

    /* Double check for different auth header string (server dependent) */
    if (!$auth) {
        $auth = isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : false;
    }

    if (!$auth) {
        return new WP_Error(
            'jwt_auth_no_auth_header',
            'Authorization header not found.',
            array(
                'status' => 403,
            )
        );
    }
    
    /*
     * The HTTP_AUTHORIZATION is present verify the format
     * if the format is wrong return the user.
     */
    list($token_auth) = sscanf($auth, 'Bearer %s');
    $token_session = $request['token'];
    if (!$token_auth && !$token_session) {
        return new WP_Error(
            'jwt_auth_bad_auth_header',
            'Authorization header malformed.',
            array(
                'status' => 403,
            )
        );
    }

    /** Get the Secret Key */
    $secret_key_auth = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : false;
    $secret_key_session = defined('JWT_AUTH_SESSION_SECRET_KEY') ? JWT_AUTH_SESSION_SECRET_KEY : false;
    if (!$secret_key_auth || !$secret_key_session) {
        return new WP_Error(
            'jwt_auth_bad_config',
            'JWT is not configurated properly, please contact the admin',
            array(
                'status' => 403,
            )
        );
    }
    /** Try to decode the token */
    try {
        $token_session = JWT::decode($token_session, $secret_key_session, array('HS256'));
        /** The token is valid **/
        return new WP_Error(
            'jwt_auth_valid_session_token',
            'You are trying to refresh a valid token',
            array(
                'status' => 403,
            )
        );
    } catch (Exception $e) {
        if($e->getMessage() === 'Expired token') {
            
            try {
                
                $token_auth = JWT::decode($token_auth, $secret_key_auth, array('HS256'));
                /** The Token is decoded now validate the iss */
                if ($token_auth->iss != get_bloginfo('url')) {
                    /** The iss do not match, return error */
                    return new WP_Error(
                        'jwt_auth_bad_iss',
                        'The iss do not match with this server',
                        array(
                            'status' => 403,
                        )
                    );
                }
                // So far so good, validate the user id in the token
                if (!isset($token_auth->data->user->id)) {
                    // No user id in the token, abort!!
                    return new WP_Error(
                        'jwt_auth_bad_request',
                        'User ID not found in the token',
                        array(
                            'status' => 403,
                        )
                    );
                }
                /** Controlla l'esistenza dell'utente **/
                $user = get_user_by('id', $token_auth->data->user->id);
                if(!$user) {
                    /** L'utente non esiste **/
                    return new WP_Error(
                        'jwt_auth_invalid_user',
                        'Invalid user',
                        array(
                            'status' => 403,
                        )
                    );
                }
                /** Confronta il token session con il token auth **/
                if($token_auth->data->ts !== $token_session) {
                    /** I token non corrispondono **/
                    return new WP_Error(
                        'jwt_auth_invalid_session_token',
                        'Invalid token',
                        array(
                            'status' => 403,
                        )
                    );
                }
                /** Confronta il token con quello salvato sul server **/
                if($token_session !== get_field('auth-session_token', 'user_'. $token_auth->data->user->id)) {
                    /** Il token non corrisponde **/
                    return new WP_Error(
                        'jwt_auth_invalid_session_token',
                        'Invalid token',
                        array(
                            'status' => 403,
                        )
                    );
                }
                
                $issuedAt = time();
                $notBefore = apply_filters('jwt_auth_not_before', $issuedAt, $issuedAt);
                $expire_session = apply_filters('jwt_auth_expire', $issuedAt + (DAY_IN_SECONDS * 7), $issuedAt);
                $expire_auth = apply_filters('jwt_auth_expire', $issuedAt + (DAY_IN_SECONDS * 30), $issuedAt);

	            $token_session = array(
		            'iss' => get_bloginfo('url'),
		            'iat' => $issuedAt,
		            'nbf' => $notBefore,
		            'exp' => $expire_session,
		            'data' => array(
			            'user' => array(
				            'id' => $user->data->ID
			            )
		            )
	            );
	            $token_session = JWT::encode(apply_filters('jwt_auth_token_before_sign', $token_session, $user), $secret_key_session);

                $token_auth = array(
                    'iss' => get_bloginfo('url'),
                    'iat' => $issuedAt,
                    'nbf' => $notBefore,
                    'exp' => $expire_auth,
                    'data' => array(
	                    'ts' => $token_session,
                        'user' => array(
                            'id' => $user->data->ID
                        )
                    )
                );
                $token_auth = JWT::encode(apply_filters('jwt_auth_token_before_sign', $token_auth, $user), $secret_key_auth);

                update_field('auth-session_token', $token_session, 'user_'. $user->data->ID);

                /** The token is signed, now create the object with no sensible user data to the client*/
                $data = array(
                    'token_auth' => $token_auth,
                    'token_session' => $token_session,
                    'user_email' => $user->data->user_email,
                    'user_nicename' => $user->data->user_nicename,
                    'user_display_name' => $user->data->display_name,
                    'exp_auth' => $issuedAt + (DAY_IN_SECONDS * 7)
                );

                /** Let the user modify the data before send it back */
                return apply_filters('jwt_auth_token_before_dispatch', $data, $user);
                
            } catch (Exception $e) {
                return new WP_Error(
                    'jwt_auth_invalid_auth_token',
                    $e->getMessage(),
                    array(
                        'status' => 403,
                    )
                );
            }
        } else {
            return new WP_Error(
                'jwt_auth_invalid_session_token',
                $e->getMessage(),
                array(
                    'status' => 403,
                )
            );
        }
    }
}