<?php

use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfReader;

function update_consegne($request) {

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
                'post_type' => 'consegne',
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
                
                // UPDATE ZONE TAXONOMY
                foreach($item['acf']['destinatario-zona'] as $currZona) {

                    $zona = get_term_by('slug', $currZona['slug'], 'zone');
                    if(!$zona) {
                        $currZona['term_id'] = wp_insert_term($currZona['name'], 'zone', array(
                            'slug' => $currZona['slug'], 
                            'description'=> $currZona['description']
                        ) );
                    }
                    update_term_meta($currZona['term_id'], 'slug', $currZona['slug']);
                    update_term_meta($currZona['term_id'], 'name', $currZona['name']);
                    update_term_meta($currZona['term_id'], 'description', $currZona['description']);

                    if($currZona['acf'] && count($currZona['acf'])) {
                        foreach($currZona['acf'] as $key => $value) {
                            update_field($key, $value, 'zone_'. $currZona['term_id']);
                        }
                    }

                    wp_set_post_terms($postID, array($currZona['term_id']), 'zone', false);
                }
                
                foreach($item['acf'] as $key => $value) {

                    if($key != 'codice_cliente' && $key != 'destinatario-zona' && strpos($key, 'allegato-') === false && $key != 'corriere') {

                        if(strpos($key, 'data') !== false) {
                            if($key == 'firma-data') {
                                if($item['acf'][$key]) {
                                    $item['acf'][$key] = str_replace(' - ', ' ', $item['acf'][$key] .':00');
                                    $item['acf'][$key] = DateTime::createFromFormat('d/m/Y H:i:s', $item['acf'][$key]);
                                    $item['acf'][$key] = $item['acf'][$key]->format('Ymd H:i');
                                } else {
                                    $value = $item['acf'][$key];
                                }
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

                        if($key == 'stato'){
	                        $value = $item['acf'][$key]['value'];
                        }
                        
                        update_field($key, $value, $postID);
                    }

	                if($key == 'allegato-firma') {
		                //TODO controllare perchè non faceva la firma
		                $firma = str_replace('data:image/png;base64,', '', $value);

		                if ( base64_encode(base64_decode($firma, true)) === $firma) {
			                $uploads_dir = ABSPATH."wp-content/uploads/app";

			                $firmaPath = "{$uploads_dir}/{$item['type']}/firme/{$item['slug']}.png";
			                $originalePath = "{$uploads_dir}/{$item['type']}/originali/{$item['slug']}.pdf";
			                $firmatoPath = "{$uploads_dir}/{$item['type']}/firmati/{$item['slug']}.pdf";

			                $firmaFile = fopen($firmaPath, 'w+');

			                $imageUploaded = fwrite($firmaFile, base64_decode($firma));
			                if($imageUploaded) {
				                update_field($key, "/wp-content/uploads/app/{$item['type']}/firme/{$item['slug']}.png", $postID);
			                }

			                fclose($firmaFile);

			                $firmaPath = cropPNG($firmaPath);

			                if($imageUploaded && $item['acf']['allegato-ddt']) {

				                $pdf = new Fpdi();

				                $pdfOriginal = $pdf->setSourceFile($originalePath);
				                for($pageN = 1; $pageN <= $pdfOriginal; $pageN++) {
					                $pdf->addPage();
					                $pdf->useTemplate($pdf->importPage($pageN), 10, 10, 200);

					                $pdf->SetFont('Arial', '', 12);

					                $pdf->Cell(40, 40, $pdf->Image($firmaPath, $pdf->GetX() + 140, $pdf->GetY() + 245, 33.78), 0, 0, 'L', false);
				                }

				                $pdf->Output($firmatoPath, 'F');

				                update_field('allegato-ddt_firmato', "/wp-content/uploads/app/{$item['type']}/firmati/{$item['slug']}.pdf", $postID);
			                }
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

function get_ddt_consegne($request) {
    
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

	    $abs_dir = (substr(ABSPATH, 0, -1));
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