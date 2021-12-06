<?php

function update_scadenze($request) {
    
    function cropPNG($firmaPath) {
    
        // Get the image
        $firmaOrig = imagecreatefrompng($firmaPath);

        // Get the width and height
        $width = imagesx($firmaOrig);
        $height = imagesy($firmaOrig);

        // Find the size of the borders
        $top = 0;
        $bottom = 0;
        $left = 0;
        $right = 0;

        $bgcolor = 0xFFFFFF; // Use this if you only want to crop out white space
        $bgcolor = imagecolorat( $firmaOrig, $top, $left ); // This works with any color, including transparent backgrounds

        //top
        for(; $top < $height; ++$top) {
            for($x = 0; $x < $width; ++$x) {
                if(imagecolorat($firmaOrig, $x, $top) != $bgcolor) {
                break 2; //out of the 'top' loop
                }
            }
        }

        //bottom
        for(; $bottom < $height; ++$bottom) {
            for($x = 0; $x < $width; ++$x) {
                if(imagecolorat($firmaOrig, $x, $height - $bottom-1) != $bgcolor) {
                break 2; //out of the 'bottom' loop
                }
            }
        }

        //left
        for(; $left < $width; ++$left) {
            for($y = 0; $y < $height; ++$y) {
                if(imagecolorat($firmaOrig, $left, $y) != $bgcolor) {
                    break 2; //out of the 'left' loop
                }
          }
        }

        //right
        for(; $right < $width; ++$right) {
            for($y = 0; $y < $height; ++$y) {
                if(imagecolorat($firmaOrig, $width - $right-1, $y) != $bgcolor) {
                    break 2; //out of the 'right' loop
                }
            }
        }

        //copy the contents, excluding the border
        $firmaCropped = imagecreate($width-($left+$right), $height-($top+$bottom));
        imagecopy($firmaCropped, $firmaOrig, 0, 0, $left, $top, imagesx($firmaCropped), imagesy($firmaCropped));

        //finally, output the image
        imagepng($firmaCropped, $firmaPath);

        return $firmaPath;
    }
    
    $data = (!is_array($request['data'])) ? json_decode($request['data'], true) : $request['data'];
    
    $return = array(
        'status' => false,
        'message' => '',
        'items' => array(
            'update' => 0,
            'error' => 0
        )
    );
    
    if($data && count($data)) {
        
        foreach($data as $item) {
            
            $postData = array(
                'ID' => $item['id'],
                'post_title' => $item['title']['rendered'],
                'post_type' => 'scadenze',
                'post_name' => $item['slug'],
                'post_status' => 'publish'
            );
            $postID = wp_update_post($postData);

            if(is_wp_error($postID)) {
                
                $return['items']['error']++;
                
            } else {
                
                $return['items']['update']++;
                    
                // UPDATE CLIENTI TAXONOMY
                foreach($item['acf']['codice_cliente'] as $currCliente) {
                    
                    $cliente = get_term_by('slug', $currCliente['slug'], 'clienti');
                    if(!$cliente) {
                        $currCliente['term_id'] = wp_insert_term($currCliente['name'], 'clienti', array(
                            'slug' => $currCliente['slug'], 
                            'description'=> $currCliente['description']
                        ) );
                    }
                    update_term_meta($currCliente['term_id'], 'slug', $currCliente['slug']);
                    update_term_meta($currCliente['term_id'], 'name', $currCliente['name']);
                    update_term_meta($currCliente['term_id'], 'description', $currCliente['description']);
                    
                    if($currCliente['acf'] && count($currCliente['acf'])) {
                        foreach($currCliente['acf'] as $key => $value) {
                            update_field($key, $value, 'clienti_'. $currCliente['term_id']);
                        }
                    }
                    
                    wp_set_post_terms($postID, array($currCliente['term_id']), 'clienti', false);
                }
                
                foreach($item['acf'] as $key => $value) {
                    
                    if($key != 'codice_cliente' && strpos($key, 'allegato-') === false && $key != 'agente-codice') {

                        if(strpos($key, 'data') !== false) {
                            if($key == 'firma-data') {
                                $item['acf'][$key] = str_replace(' - ', ' ', $item['acf'][$key] .':00');
                                $item['acf'][$key] = DateTime::createFromFormat('d/m/Y H:i:s', $item['acf'][$key]);
                                $item['acf'][$key] = $item['acf'][$key]->format('Ymd H:i');
                            } else {
                                $item['acf'][$key] = DateTime::createFromFormat('d/m/Y', $value);
                                $item['acf'][$key] = $item['acf'][$key]->format('Ymd');
                            }
                            $value = $item['acf'][$key];
                        }

                        if(strpos($key, 'note') !== false && is_string($value) && !$value) {
                            $item['acf'][$key] = '0';
                            $value = $item['acf'][$key];
                        }

                        if(strpos($key, 'note') !== false && !is_string($value) && is_numeric($value) && !$value) {
                            $item['acf'][$key] = '0';
                            $value = $item['acf'][$key];
                        }
                        
                        update_field($key, $value, $postID);
                    }

	                if($key == 'allegato-firma') {

		                $firma = str_replace('data:image/png;base64,', '', $item['acf']['allegato-firma']);

		                if ( base64_encode(base64_decode($firma, true)) === $firma) {
			                $uploads_dir = ABSPATH."httpdocs/wp-content/uploads/app";

			                $firmaPath = "{$uploads_dir}/{$item['type']}/firme/{$item['slug']}.png";
			                $originalePath = "{$uploads_dir}/{$item['type']}/originali/{$item['slug']}.pdf";
			                $firmatoPath = "{$uploads_dir}/{$item['type']}/firmati/{$item['slug']}.pdf";

			                $firmaFile = fopen($firmaPath, 'w+');

			                $imageUploaded = fwrite($firmaFile, base64_decode($firma));

			                if($imageUploaded) {
				                update_field($key, "/wp-content/uploads/app/{$item['type']}/firme/{$item['slug']}.png", $postID);
			                }

			                fclose($firmaFile);
		                }
	                }
                }
            }
            
            $return['status'] = true;
            $return['message'] = 'Importazione completata con successo! ('. count($data) .' elementi - '. $return['items']['error'] .' errori, '. $return['items']['update'] .' aggiornamenti)';
        }

    } else {

        $return['status'] = false;
        $return['message'] = 'Impossibile importare gli elementi. Riprovare.';
    }
    
    $response = new WP_REST_Response($return);
    $response->set_status(200);

    return $response;
}

function get_ddt_scadenze($request) {
    
    $post = get_post($request['id']);
    
    $return = array(
        'status' => false,
        'message' => '',
        'data' => array(
            'ddt' => array(
                'url' => '',
                'name' => '',
                'ext' => '',
                'content' => ''
            )
        )
    );
    
    if($post) {
	    $abs_dir = (substr(ABSPATH, 0, -1));
        $return['status'] = true;
        $return['data']['ddt']['url'] = get_field('allegato-ddt', $post->ID);
        
        $return['data']['ddt']['name'] = basename($abs_dir. $return['data']['ddt']['url']);
        $return['data']['ddt']['name'] = preg_replace('/\\.[^.\\s]{3,4}$/', '', $return['data']['ddt']['name']);
        
        $return['data']['ddt']['ext'] = basename($abs_dir. $return['data']['ddt']['url']);
        $return['data']['ddt']['ext'] = strtolower(explode('.', $return['data']['ddt']['ext'])[1]);
        
        $return['data']['ddt']['content'] = chunk_split(base64_encode(file_get_contents($abs_dir. $return['data']['ddt']['url'])));
        
    } else {
        
        $return['status'] = false;
        $return['message'] = 'ID post non trovato';
    }
    
    $response = new WP_REST_Response($return);
    $response->set_status(200);

    return $response;
}