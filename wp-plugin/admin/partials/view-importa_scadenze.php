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
                    if( !(count($csvData) == 27) ) continue;

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
            
            $newUsers = 0;
            $errorUsers = 0;
            $countUsers = 0;
            
            foreach($import_result['data'] as $data) {
                
                $data['sc_annpar'] = ($data['sc_annpar']) ? $data['sc_annpar'] : '0';
                $data['sc_alfpar'] = ($data['sc_alfpar']) ? $data['sc_alfpar'] : 'X';
                $data['sc_numpar'] = ($data['sc_numpar']) ? $data['sc_numpar'] : '0';
                $data['sc_alfdoc'] = ($data['sc_alfdoc']) ? $data['sc_alfdoc'] : '0';
                
                if($data['sc_datsca']) {
                    
                    $data['sc_datsca'] = explode(' ', $data['sc_datsca']);
                    $data['sc_datsca'] = $data['sc_datsca'][0];
                    $data['sc_datsca'] = DateTime::createFromFormat('d/m/Y', $data['sc_datsca']);
                    
                } else {
                    
                    $data['sc_datsca'] = DateTime::createFromFormat('d/m/Y', date());
                }
                
                if($data['sc_datdoc']) {
                    
                    $data['sc_datdoc'] = explode(' ', $data['sc_datdoc']);
                    $data['sc_datdoc'] = $data['sc_datdoc'][0];
                    $data['sc_datdoc'] = DateTime::createFromFormat('d/m/Y', $data['sc_datdoc']);
                    
                } else {
                    
                    $data['sc_datdoc'] = DateTime::createFromFormat('d/m/Y', date());
                }
                
                if($data['sc_flsaldato'] == 'N') $data['sc_flsaldato'] = '0';
                if($data['sc_flsaldato'] == 'O') $data['sc_flsaldato'] = '1';
                if($data['sc_flsaldato'] == 'S') $data['sc_flsaldato'] = '2';

                $postTitle = 'A-'. $data['sc_annpar'] .'-'. $data['sc_alfpar'] .'-'. $data['sc_numpar'] .'-'. $data['sc_datsca']->format('Ymd');

                $currentPostData = new WP_Query(array(
                    'title' => $postTitle,
                    'post_type' => 'scadenze',
                    'post_status' => 'publish',
                    'posts_per_page' => 1
                ) );
                
                if(!$currentPostData->have_posts()) {
                    
                    $postData = array(
                        'ID' => ($currentPostData->have_posts()) ? $currentPostData->posts[0]->ID : 0,
                        'post_title' => $postTitle,
                        'post_type'  => 'scadenze',
                        'post_name'  => $postTitle,
                        'post_status' => 'publish'
                    );
                    $postID = wp_insert_post($postData);

                    if(is_wp_error($postID)) {

                        $errorItems++;

                    } else {

                        if(!$currentPostData->have_posts()) $newItems++;
                        else $updateItems++;

                        $cliente = get_term_by('slug', $data['sc_conto'], 'clienti');
                        if(!$cliente) {
                            $cliente = wp_insert_term(preg_replace("/[^A-Za-z0-9 ]/", '', $data['an_descr1']), 'clienti', array(
                                'slug' => $data['sc_conto'], 
                                'description'=> $data['an_descr2']
                            ) );
                            $cliente = get_term_by('id', $cliente['term_id'], 'clienti');
                        }
                        update_term_meta($cliente->term_id, 'slug', $data['sc_conto']);
                        update_term_meta($cliente->term_id, 'name', preg_replace("/[^A-Za-z0-9 ]/", '', $data['an_descr1']));
                        update_term_meta($cliente->term_id, 'description', $data['an_descr2']);
                        update_field('indirizzo', $data['an_indir'], 'clienti_'. $cliente->term_id);
                        update_field('citta', $data['an_citta'], 'clienti_'. $cliente->term_id);
                        update_field('cap', intval($data['an_cap']), 'clienti_'. $cliente->term_id);
                        update_field('provincia', $data['an_prov'], 'clienti_'. $cliente->term_id);

                        wp_set_post_terms($postID, array($cliente->term_id), 'clienti', false);
                        update_field('codice_cliente', array($cliente->term_id), $postID);

                        update_field('partita-anno', intval($data['sc_annpar']), $postID);
                        update_field('partita-serie', $data['sc_alfpar'], $postID);
                        update_field('partita-numero', intval($data['sc_numpar']), $postID);
                        update_field('partita-numero_rata', intval($data['sc_numrata']), $postID);

                        update_field('documento-tipo', 'A', $postID);
                        update_field('documento-numero', intval($data['sc_numdoc']), $postID);
                        update_field('documento-serie', $data['sc_alfdoc'], $postID);
                        update_field('documento-data', $data['sc_datdoc']->format('Ymd'), $postID);

                        update_field('causale', intval($data['sc_causale']), $postID);
                        update_field('data', $data['sc_datsca']->format('Ymd'), $postID);
                        update_field('firma-data', '', $postID);
                        update_field('importo', number_format(floatval(str_replace(',', '.', $data['sc_importo'])), 2), $postID);
                        update_field('importo_riscosso', 0, $postID);
                        update_field('note', $data['sc_descr'], $postID);
                        update_field('pagamento-modo', ($data['sc_darave'] == 'D') ? 1 : 0, $postID);
                        update_field('insoluto', $data['sc_insolu'], $postID);
                        update_field('pagamento-tipo', intval($data['sc_tippaga']), $postID);
                        update_field('pagamento-codice', intval($data['sc_codpaga']), $postID);
                        update_field('stato', $data['sc_flsaldato'], $postID);

                        $user = new WP_User_Query(array(
                            'role' => 'agente',
                            'meta_query' => array(
                                array(
                                    'key'     => 'agente-codice',
                                    'value'   => $data['an_agente'],
                                    'compare' => 'LIKE'
                                )
                            )
                        ));
                        $user = $user->get_results();
                        if(!empty($user)) {

                            $userID = $user[0]->ID;

                        } else {

                            $countUsers++;

                            $userID = wp_insert_user(array(
                                'user_login' => 'agente-'. $data['an_agente'],
                                'user_email' => 'agente-'. $data['an_agente'] .'@pokersrl.it',
                                'user_pass' => 'agente-'. $data['an_agente'],
                                'role' => 'agente'
                            ));

                            if(!is_wp_error($userID)) {

                                update_field('agente-codice', $data['an_agente'], 'user_'. $userID);
                                update_field('zone', array(), 'user_'. $userID);
                                update_field('auth-session_token', '', 'user_'. $userID);

                                $newUsers++;

                            } else {

                                $errorUsers++;
                            }
                        }

                        update_field('agente-codice', $userID, $postID);
                        update_field('agente-note', $data['sc_notepart'], $postID);

                        update_field('allegato-ddt', '', $postID);
                        update_field('allegato-firme', '', $postID);

                        wp_update_post(array( 'ID' => $postID, 'post_author' => $userID ));
                    }
                    
                } else {
                    
                    $toUpdateItems++;
                }
            }
            
            $import_result['status'] = true;
            $import_result['message'] = 'Importazione completata con successo! ('. count($import_result['data']) .' elementi - '. $errorItems .' errori, '. $newItems .' inserimenti, '. $updateItems .' aggiornamenti, '. $toUpdateItems .' da aggiornare / '. $countUsers .' utenti - '. $newUsers .' inseriti, '. $errorUsers .' errori)';
            
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
    
    <h1 class="wp-heading-inline">Strumenti / Importa Scadenze</h1>
    
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
                        <tr><td colspan='5'>Seleziona e importa un file per viasualizzare l'anteprima.</td></tr>
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