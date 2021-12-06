<?php

$import_params = array(
    'row' => [
        'current' => 0,
        'step' => 20
    ],
    'data' => '',
    'files' => ''
);

$import_result = array(
    'status' => false,
    'message' => '',
    'data' => []
);

$import_params['data'] = (isset($_POST) && !empty($_POST)) ? $_POST : [];
$import_params['data']['files'] = (isset($_FILES) && !empty($_FILES)) ? $_FILES : false;

if($import_params['data'] && isset($import_params['data']['import'])) {
    
    set_time_limit(0);
    
    $importStep = intval($import_params['data']['import-step']);
    if($importStep == 1) {
        if(!empty($import_params['data']['files']['import-file']['name'])) {

            // File extension
            $extension = pathinfo($import_params['data']['files']['import-file']['name'], PATHINFO_EXTENSION);

            // If file extension is 'csv'
            if($extension == 'csv') {

                // Open file in read mode
                $csvFile = fopen($import_params['data']['files']['import-file']['tmp_name'], 'r');

                $csvFields = fgetcsv($csvFile, 9999, '|');

                // Read file
                while(($csvData = fgetcsv($csvFile, 9999, '|')) !== FALSE) {

                    $csvData = array_map("utf8_encode", $csvData);
                    $csvRow = array();

                    // Skip row if length != N
                    if( !(count($csvData) == 29) ) continue;

                    // Assign value to variables
                    for($i = 0; $i < count($csvData); $i++) {
                        
                        if($csvFields[$i] == 'tm_datdoc') {
                            $csvData[$i] = explode(' ', trim($csvData[$i]));
                            $csvData[$i] = $csvData[$i][0];
                        }
                        
                        if($csvFields[$i] != 'Campo 7') {
                            $csvRow[$csvFields[$i]] = trim($csvData[$i]);
                        }
                    }

                    array_push($import_result['data'], $csvRow);
                }

                $import_result['status'] = true;
                $import_result['message'] = 'Sono stati individuati '. count($import_result['data']) .' elementi da importare.';

            } else {

                $import_result['status'] = false;
                $import_result['message'] = 'Impossibile importare il file selezionato. Estensione non supportata (.'. strtoupper($extension) .').';
            }

        } else {

            $import_result['status'] = false;
            $import_result['message'] = 'Selezionare un file per avviare l\'importazione.';
        }
        
    } elseif($importStep == 2) {
        
        $import_result['data'] = json_decode(base64_decode($import_params['data']['import-data']), true);
        
        if($import_result['data']) {
            
            $newItems = 0;
            $updateItems = 0;
            $toUpdateItems = 0;
            $errorItems = 0;
            
            foreach($import_result['data'] as $data) {
                
                $data['tm_serie'] = ($data['tm_serie']) ? $data['tm_serie'] : 'X';
                $data['tm_tipork'] = ($data['tm_tipork']) ? $data['tm_tipork'] : '0';
                $data['tm_anno'] = ($data['tm_anno']) ? $data['tm_anno'] : '0';
                $data['tm_serie'] = ($data['tm_serie']) ? $data['tm_serie'] : 'X';
                $data['tm_numdoc'] = ($data['tm_numdoc']) ? $data['tm_numdoc'] : '0';
                
                if($data['tm_datdoc']) {
                    
                    $data['tm_datdoc'] = explode(' ', $data['tm_datdoc']);
                    $data['tm_datdoc'] = $data['tm_datdoc'][0];
                    $data['tm_datdoc'] = DateTime::createFromFormat('d/m/Y', $data['tm_datdoc']);
                    
                } else {
                    
                    $data['tm_datdoc'] = DateTime::createFromFormat('d/m/Y', date());
                }

                $postTitle = $data['tm_tipork'] .'-'. $data['tm_anno'] .'-'. $data['tm_serie'] .'-'. $data['tm_numdoc'] .'-'. $data['tm_datdoc']->format('Ymd');

                $currentPostData = new WP_Query(array(
                    'title' => $postTitle,
                    'post_type' => 'consegne',
                    'post_status' => 'publish',
                    'posts_per_page' => 1
                ) );
                
                if(!$currentPostData->have_posts()) {
                    
                    $postData = array(
                        'ID' => ($currentPostData->have_posts()) ? $currentPostData->posts[0]->ID : 0,
                        'post_title' => $postTitle,
                        'post_type' => 'consegne',
                        'post_name' => $postTitle,
                        'post_status' => 'publish'
                    );
                    $postID = wp_insert_post($postData);

                    if(is_wp_error($postID)) {

                        $errorItems++;

                    } else {

                        if(!$currentPostData->have_posts()) $newItems++;
                        else $updateItems++;

                        $cliente = get_term_by('slug', $data['tm_conto'], 'clienti');
                        if(!$cliente) {
                            $cliente = wp_insert_term(preg_replace("/[^A-Za-z0-9 ]/", '', $data['an_descr1']), 'clienti', array(
                                'slug' => $data['tm_conto'],
                                'description'=> $data['an_descr2']
                            ) );
                            $cliente = get_term_by('id', $cliente['term_id'], 'clienti');
                        }
                        update_term_meta($cliente->term_id, 'slug', $data['tm_conto']);
                        update_term_meta($cliente->term_id, 'name', preg_replace("/[^A-Za-z0-9 ]/", '', $data['an_descr1']));
                        update_term_meta($cliente->term_id, 'description', $data['an_descr2']);
                        update_field('indirizzo', $data['an_indir'], 'clienti_'. $cliente->term_id);
                        update_field('citta', $data['an_citta'], 'clienti_'. $cliente->term_id);
                        update_field('cap', intval($data['an_cap']), 'clienti_'. $cliente->term_id);
                        update_field('provincia', $data['an_prov'], 'clienti_'. $cliente->term_id);

                        $zona = get_term_by('slug', $data['an_zona'], 'zone');
                        if(!$zona) {
                            $zona = wp_insert_term($data['an_zona'], 'zone', array(
                                'slug' => $data['an_zona'],
                                'description'=> $data['tb_deszone']
                            ) );
                            $zona = get_term_by('id', $zona['term_id'], 'zone');
                        }
                        update_term_meta($zona->term_id, 'name', $data['an_zona']);
                        update_term_meta($zona->term_id, 'slug', $data['an_zona']);
                        update_term_meta($zona->term_id, 'description', $data['tb_deszone']);
                        update_field('zona', array($zona->term_id), 'clienti_'. $cliente->term_id);

                        wp_set_post_terms($postID, array($cliente->term_id), 'clienti', false);
                        update_field('codice_cliente', array($cliente->term_id), $postID);

                        wp_set_post_terms($postID, array($zona->term_id), 'zone', false);
                        update_field('destinatario-zona', array($zona->term_id), $postID);

                        update_field('documento-tipo', $data['tm_tipork'], $postID);
                        update_field('documento-tipo_codice', intval($data['tm_tipobf']), $postID);
                        update_field('documento-anno', intval($data['tm_anno']), $postID);
                        update_field('documento-serie', $data['tm_serie'], $postID);
                        update_field('documento-numero', intval($data['tm_numdoc']), $postID);
                        update_field('documento-data', $data['tm_datdoc']->format('Ymd'), $postID);

                        update_field('codice_pagamento', intval($data['tm_codpaga']), $postID);
                        if((intval($data['tm_vettor']) < 10 || intval($data['tm_vettor']) > 29) &&
                           (intval($data['tm_vettor']) < 40 || intval($data['tm_vettor']) > 98) &&
                           (intval($data['tm_vettor']) < 100 || intval($data['tm_vettor']) > 102)) {
                            update_field('codice_spedizione', intval($data['tm_vettor']), $postID);
                        } else {
                            update_field('codice_spedizione', 0, $postID);
                        }
                        update_field('numero_colli', intval($data['tm_totcoll']), $postID);
                        update_field('note', ($data['tm_testonotacons']) ? $data['tm_testonotacons'] : '', $postID);
                        update_field('note_corriere', '', $postID);
                        update_field('firma-data', '', $postID);
                        update_field('importo', number_format(floatval(str_replace(',', '.', $data['tm_totodoc'])), 2), $postID);
                        update_field('importo_riscosso', 0, $postID);
                        update_field('stato', '0', $postID);

                        update_field('destinatario-codice', intval($data['tm_coddest']), $postID);
                        if(intval($data['tm_coddest'])) {
                            update_field('destinatario-nome', $data['dd_nomdest'], $postID);
                            update_field('destinatario-indirizzo', $data['dd_indest'], $postID);
                            update_field('destinatario-citta', $data['dd_locdest'], $postID);
                            update_field('destinatario-cap', $data['dd_capdest'], $postID);
                            update_field('destinatario-provincia', $data['dd_prodest'], $postID);
                        }

                        update_field('allegato-ddt', '', $postID);
                        update_field('allegato-ddt_firmato', '', $postID);
                        update_field('allegato-firma', '', $postID);
                    }
                    
                } else {
                    
                    $toUpdateItems++;
                }
            }
            
            $import_result['status'] = true;
            $import_result['message'] = 'Importazione completata con successo! ('. count($import_result['data']) .' elementi - '. $errorItems .' errori, '. $newItems .' inserimenti, '. $updateItems .' aggiornamenti, '. $toUpdateItems .' da aggiornare)';
            
        } else {
            
            $import_result['status'] = false;
            $import_result['message'] = 'Impossibile importare gli elementi. Riprovare.';
        }
    }
}
?>

<style type="text/css">
    *,
    *:before,
    *:after {
        -webkit-box-sizing: border-box;
        -moz-box-sizing: border-box;
        box-sizing: border-box;
    }
    
    #form-step_1 {
        margin: 20px 0px;
    }
    
    #form-step_1 .file {
        overflow: hidden;
    }
    
    #form-step_1 .file input[type="file"] {
        position: relative;
        z-index: 0;
        left: -80px;
        top: 3px;
        padding: 6.5px 0px;
        opacity: 1;
    }
    
    #form-step_1 .file .file-custom {
        background: linear-gradient(to left, transparent 251px, #f1f1f1 100px);
    }
    
    #form-step_1 .file .file-custom:after {
        content: '';
    }
    
    #form-step_1 input[type="submit"],
    #form-step_2 input[type="submit"] {
        padding: 10px;
        text-decoration: none;
        border: 1px solid #0071a1;
        border-radius: 2px;
        text-shadow: none;
        font-weight: 600;
        font-size: 13px;
        line-height: normal;
        color: #0071a1;
        background: #f3f5f6;
        cursor: pointer;
        margin: 0px 0px 0px 10px;
    }
    
    #form-step_2 input[type="submit"] {
        margin: 0;
    }
    
    #form-step_2 .table-wrap {
        width: 100%;
        max-height: 65vh;
        overflow: auto;
    }
    
    #form-step_2 .table-wrap table thead {
        position: sticky;
        z-index: 10;
        top: 0px;
        background-color: #eee;
        box-shadow: 0px 0px 2px -1px #000;
    }
    
    #form-step_2 .table-wrap table thead tr th {
        padding: 10px;
    }
    
    #form-step_2 .table-wrap table thead tr th:first-of-type {
        position: sticky;
        left: 0px;
        background-color: #eee;
        box-shadow: 0px 0px 2px -1px #000;
    }
    
    #form-step_2 .table-wrap table tbody tr td {
        padding: 10px;
    }
    
    #form-step_2 .table-wrap table tbody tr td:first-of-type {
        position: sticky;
        left: 0px;
        background-color: #eee;
        box-shadow: 0px 0px 2px -1px #000;
    }
     
</style>

<div class="wrap">
    
    <h1 class="wp-heading-inline">Strumenti / Importa Consegne</h1>
    
    <hr class="wp-header-end">
    
    <?php if($import_result['message']) { ?>
        <div class="message <?= ($import_result['status']) ? 'updated' : 'error' ?>">
            <p><?= $import_result['message'] ?></p>
        </div>
    <?php } ?>
    
    <form id="form-step_1" method="post" action="<?= $_SERVER['REQUEST_URI']; ?>" enctype="multipart/form-data">
        
        <input type="number" name="import-step" value="1" hidden="hidden">
       
        <label class="file">
            <input type="file" id="file" name="import-file">
            <span class="file-custom"></span>
        </label>
        
        <input type="submit" name="import" value="Importa">
        
    </form>
    
    <form id="form-step_2" method="post" action="<?= $_SERVER['REQUEST_URI']; ?>">
       
        <input type="number" name="import-step" value="2" hidden="hidden">
        <input type="text" name="import-data" value="<?= base64_encode(json_encode($import_result['data'])); ?>" hidden="hidden">
       
        <div class="table-wrap">
            <table width='100%' border='1' style='border-collapse: collapse;'>
                <thead>
                    <?php if($import_result['data']) { ?>
                        <tr>
                            <?php foreach($import_result['data'][0] as $key => $value) { ?>
                                <th><?= $key ?></th>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                </thead>
                <tbody>
                    <?php if(!$import_result['data']) { ?>
                        <tr><td colspan='5'>Seleziona e importa un file per visualizzare l'anteprima.</td></tr>
                    <?php } else { ?>
                        <?php foreach($import_result['data'] as $row) { ?>
                            <tr>
                                <?php foreach($row as $key => $value) { ?>
                                    <td><?= $value ?></td>
                                <?php } ?>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        
        <?php if($import_result['data']) { ?>
            <p>
                <input type="submit" name="import" value="Conferma e Importa >">
            </p>
        <?php } ?>
        
    </form>
</div>