<?php

use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfReader;

/*** FTP FILE VIEWER OPTIONS ***/

$abs_dir = (substr(ABSPATH, 0, -1));
$uploads_dir = ABSPATH."wp-content/uploads/app";

// STYLING (light or dark)
$color	= "light";

// ADD SPECIFIC FILES YOU WANT TO IGNORE HERE
$GLOBALS['ignore_file_list'] = array(".htaccess", "Thumbs.db", ".DS_Store", "index.php");

// ADD SPECIFIC FILE EXTENSIONS YOU WANT TO IGNORE HERE, EXAMPLE: array('psd','jpg','jpeg')
$GLOBALS['ignore_ext_list'] = array();

// SORT BY
$GLOBALS['sort_by'] = "name_asc"; // options: name_asc, name_desc, date_asc, date_desc

// ICON URL
$GLOBALS['icon_url'] = '/wp-content/plugins/poker-plugin/admin/assets/images/filetype_icon.png';

// TOGGLE SUB FOLDERS, SET TO false IF YOU WANT OFF
$GLOBALS['toggle_sub_folders'] = true;

// FORCE DOWNLOAD ATTRIBUTE
$GLOBALS['force_download'] = false;

// IGNORE EMPTY FOLDERS
$GLOBALS['ignore_empty_folders'] = false;

/*** FTP FILE VIEWER OPTIONS ***/

$import_result = array(
    'status' => false,
    'message' => '',
    'data' => []
);

function reArrayFiles(&$file_post) {

    $file_ary = array();
    $file_count = count($file_post['name']);
    $file_keys = array_keys($file_post);

    for ($i=0; $i<$file_count; $i++) {
        foreach ($file_keys as $key) {
            $file_ary[$i][$key] = $file_post[$key][$i];
        }
    }

    return $file_ary;
}

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

if(isset($_GET['delete'])) {
    
    $file_name = $_GET['delete'];
    $file_url = $_GET['folder'];
    $file_path = $abs_dir. $file_url ."/". $file_name;
    
    if(file_exists($file_path)) {
        
        $folder = str_replace('/wp-content/uploads/app/', '', $file_url);
        $loop = false;

        if($folder == 'consegne/originali' || $folder == 'consegne/firmati' || $folder == 'consegne/firme') {
            $loop = new WP_Query( array(
                'post_type' => 'consegne',
                'posts_per_page' => -1,
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => 'allegato-ddt',
                        'value' => $file_url .'/'. $file_name,
                        'type' => 'CHAR',
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'allegato-ddt_firmato',
                        'value' => $file_url .'/'. $file_name,
                        'type' => 'CHAR',
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'allegato-firma',
                        'value' => $file_url .'/'. $file_name,
                        'type' => 'CHAR',
                        'compare' => 'LIKE'
                    )
                )
            ) );
        } elseif($folder == 'scadenze/originali' || $folder == 'scadenze/firme') {
            $loop = new WP_Query( array(
                'post_type' => 'scadenze',
                'posts_per_page' => -1,
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => 'allegato-ddt',
                        'value' => $file_url .'/'. $file_name,
                        'type' => 'CHAR',
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'allegato-firma',
                        'value' => $file_url .'/'. $file_name,
                        'type' => 'CHAR',
                        'compare' => 'LIKE'
                    )
                )
            ) );
        }
        if($loop && $loop->have_posts()) {
            
            while($loop->have_posts()) : $loop->the_post();
            
                $removed = false;

                if(get_field('allegato-ddt') == $file_url .'/'. $file_name) {
                    $removed = unlink($file_path);
                    update_field('allegato-ddt', '');
                }
                if(get_field('allegato-ddt_firmato') == $file_url .'/'. $file_name) {
                    $removed = unlink($file_path);
                    update_field('allegato-ddt_firmato', '');
                }
                if(get_field('allegato-firma') == $file_url .'/'. $file_name) {
                    $removed = unlink($file_path);
                    update_field('allegato-firma', '');
                }
                
                if($removed) {
                    $import_result['status'] = true;
                    $import_result['message'] = 'File eliminato con successo.';
                    
                    echo '<meta http-equiv="refresh" content="0; url='. home_url() .'/wp-admin/admin.php?page=poker_plugin-importa_documenti' .'">';
                    
                } else {
                    $import_result['status'] = false;
                    $import_result['message'] = 'Impossibile eliminare il file.';
                }

            endwhile;
            
        } else {
            
            if(unlink($file_path)) {
                
                $import_result['status'] = true;
                $import_result['message'] = 'File eliminato con successo.';

                echo '<meta http-equiv="refresh" content="0; url='. home_url() .'/wp-admin/admin.php?page=poker_plugin-importa_documenti' .'">';
            }
        }
    }   
}

if(isset($_POST['import'])) {
    
    set_time_limit(0);
    
    $files = reArrayFiles($_FILES['import-file']);
    $files_path = $_POST['import-file_path'];
    $files_post = $_POST['import-file_post'];
    $files_post_id = 0;
    $files_post_type = '';
    $files_field = $_POST['import-file_field'];
    
    if($files_post == '*') {
        
        if(strpos($files_path, 'consegne') !== false) $files_post_type = 'consegne';
        elseif(strpos($files_path, 'scadenze') !== false) $files_post_type = 'scadenze';
        
    } elseif($files_post != '') {
        
        $files_post_id = intval($files_post);
        
        $item = get_post($files_post_id);
        $files_post_type = $item->post_type;
    }
    
    if(!empty($_FILES['import-file']) && !empty($_POST['import-file_path'])) {    
        
        foreach ($files as $file) {
            
            $errors = array();
            
            $file_name = strtolower(preg_replace('/\\.[^.\\s]{3,4}$/', '', $file['name']));
            $file_size = $file['size'];
            $file_tmp = $file['tmp_name'];
            $file_type = $file['type'];
            
            $tmp = explode('.', $file['name']);
            $file_ext = strtolower(end($tmp));

            $extensions = array('jpeg', 'jpg', 'png', 'pdf', 'doc', 'docx', 'csv', 'xml', 'xls');

            if(in_array($file_ext, $extensions) === false) {
                $errors[] = 'extension';
                $import_result['message'] = 'impossibile caricare il file. Estensione non supportata.';
            }

            if($file_size > 2097152) {
                $errors[] = 'size';
                $import_result['message'] = 'impossibile caricare il file. File troppo grande (MAX 2MB).';
            }

            if(empty($errors) == true) {
                
                $import_result['status'] = true;
                
                // UPDATE FIELDS
                
                if($files_post == '*') {
                    
                    $check = false;
                    $file_name_array = explode('-', $file_name);
                    
                    if($files_post_type == 'consegne') {
                        if(!$file_name_array[0] || $file_name_array[0] == ' ') $file_name_array[0] = 'X';
                        if(!$file_name_array[1] || $file_name_array[1] == ' ') $file_name_array[1] = '0';
                        if(!$file_name_array[2] || $file_name_array[2] == ' ') $file_name_array[2] = 'X';
                        if(!$file_name_array[3] || $file_name_array[3] == ' ') $file_name_array[3] = '0';
                    } elseif($files_post_type = 'scadenze') {
                        if(!$file_name_array[0] || $file_name_array[0] == ' ') $file_name_array[0] = 'A';
                        if(!$file_name_array[1] || $file_name_array[1] == ' ') $file_name_array[1] = '0';
                        if(!$file_name_array[2] || $file_name_array[2] == ' ') $file_name_array[2] = 'X';
                        if(!$file_name_array[3] || $file_name_array[3] == ' ') $file_name_array[3] = '0';
                        
                        if(strpos($file_name_array[3], '_') !== false) {
                            $file_name_array[3] = explode('_', $file_name_array[3]);
                            $file_name_array[3] = $file_name_array[3][0];
                        }
                    }
                    
                    if((($files_post_type == 'consegne' && 
                         count($file_name_array) == 4 &&
                         ctype_alpha($file_name_array[0]) && ctype_digit($file_name_array[1])
                         && ctype_digit($file_name_array[3])) ||
                        ($files_post_type == 'scadenze' &&
                         count($file_name_array) == 4 &&
                         ctype_digit($file_name_array[1]) && ctype_alpha($file_name_array[2]) && 
                         ctype_digit($file_name_array[3])))) {
                        
                        if($files_post_type) {
                            
                            if($files_post_type == 'consegne') {
                                
                                $metaquery_args = array(
                                    'relations' => 'AND',
                                    array(
                                        'key' => 'documento-tipo',
                                        'value' => $file_name_array[0],
                                        'type' => 'CHAR',
                                        'compare' => 'LIKE',
                                    ),
                                    array(
                                        'key' => 'documento-anno',
                                        'value' => intval($file_name_array[1]),
                                        'type' => 'NUMERIC',
                                        'compare' => 'LIKE',
                                    ),
                                    array(
                                        'key' => 'documento-serie',
                                        'value' => $file_name_array[2],
                                        'type' => 'CHAR',
                                        'compare' => 'LIKE',
                                    ),
                                    array(
                                        'key' => 'documento-numero',
                                        'value' => intval($file_name_array[3]),
                                        'type' => 'NUMERIC',
                                        'compare' => 'LIKE',
                                    )
                                );
                                    
                            } else {
                                
                                $metaquery_args = array(
                                    'relations' => 'AND',
                                    array(
                                        'key' => 'documento-tipo',
                                        'value' => $file_name_array[0],
                                        'type' => 'CHAR',
                                        'compare' => 'LIKE',
                                    ),
                                    array(
                                        'key' => 'partita-anno',
                                        'value' => intval($file_name_array[1]),
                                        'type' => 'NUMERIC',
                                        'compare' => 'LIKE',
                                    ),
                                    array(
                                        'key' => 'partita-serie',
                                        'value' => $file_name_array[2],
                                        'type' => 'CHAR',
                                        'compare' => 'LIKE',
                                    ),
                                    array(
                                        'key' => 'partita-numero',
                                        'value' => intval($file_name_array[3]),
                                        'type' => 'NUMERIC',
                                        'compare' => 'LIKE',
                                    )
                                );
                            }
                            
                            $loop = new WP_Query( array(
                                'post_type' => $files_post_type,
                                'posts_per_page' => 1,
                                'meta_query' => $metaquery_args
                            ) );
                            if($loop->have_posts()) {
                                
                                $check = true;
                                
                                while($loop->have_posts()) : $loop->the_post();
                                
                                    $files_post_id = get_the_ID();
                                
                                endwhile;
                                
                                wp_reset_query();
                                
                            } else {
                        
                                $check = false;
                                
                                $import_result['message'] .= 'documento non trovato / ';
                            }
                            
                        } else {
                            
                            $check = false;
                        
                            $import_result['message'] .= 'tipo di documento non trovato / ';
                        }
                        
                    } else {
                        
                        $check = false;
                        
                        $import_result['message'] .= 'formato nome file non corretto / ';
                    }
                }
                
                if(($files_post != '*' && $files_post) || ($files_post == '*' && $check)) {
                
                    move_uploaded_file($file_tmp, $files_path .'/'. $file_name .'.'. $file_ext);

                    update_field($files_field, str_replace($abs_dir, '', $files_path .'/'. $file_name .'.'. $file_ext), $files_post_id);

                    $item = get_post($files_post_id);
                    $itemFirma = get_field('allegato-firma', $item->ID);
                    $itemDDT = get_field('allegato-ddt', $item->ID);

                    if($item->post_type == 'consegne' && $itemFirma && $itemDDT) {

                        $firmaPath = "{$uploads_dir}/{$item->post_type}/firme/{$item->post_name}.png";
                        $originalePath = "{$uploads_dir}/{$item->post_type}/originali/{$item->post_name}.pdf";
                        $firmatoPath = "{$uploads_dir}/{$item->post_type}/firmati/{$item->post_name}.pdf";

                        //$firmaPath = cropPNG($firmaPath);

                        $pdf = new Fpdi();

                        $pdfOriginal = $pdf->setSourceFile($originalePath);
                        for($pageN = 1; $pageN <= $pdfOriginal; $pageN++) {
                            $pdf->addPage();
                            $pdf->useTemplate($pdf->importPage($pageN), 10, 10, 200);
                            
                            $pdf->SetFont('Arial', '', 12);
                            
                            $pdf->Cell(40, 40, $pdf->Image($firmaPath, $pdf->GetX() + 140, $pdf->GetY() + 245, 33.78), 0, 0, 'L', false);
                        }

                        $pdf->Output($firmatoPath, 'F');

                        update_field('allegato-ddt_firmato', "/wp-content/uploads/app/{$item->post_type}/firmati/{$item->post_name}.pdf", $item->ID);
                    }
                
                } elseif($files_post != '*' && !$files_post) {
                    
                    move_uploaded_file($file_tmp, $files_path .'/'. $file_name .'.'. $file_ext);
                }
            }
        }
        
        if(!$import_result['status']) {
            
            $import_result['message'] = 'Impossibile caricare i file selezionati. Riprovare.';
            
        } elseif($import_result['status'] && $import_result['message']) {
            
            $import_result['status'] = true;
            
            if($files_post == '*') {
                $import_result['message'] = substr($import_result['message'], 0, -3);
                $import_result['message'] .= '.';
            }
            
            $import_result['message'] = 'Attenzione, l\'importazione potrebbe non essere completa a causa di alcuni errori: '. $import_result['message'];
            
        } else {
            
            $import_result['status'] = true;
            $import_result['message'] = 'Importazione completata con successo.';
        }

    } else {

        $import_result['status'] = false;
        $import_result['message'] = 'Selezionare un file e un percorso per avviare l\'importazione.';
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
    
    form {
        margin: 20px 0px;
    }
    
    form .select:after {
        opacity: 0;
    }
    
    form .select select {
        border: .075rem solid #ddd;
    }
    
    form .file {
        overflow: hidden;
    }
    
    form .file input[type="file"] {
        position: relative;
        z-index: 0;
        left: -80px;
        top: 3px;
        padding: 6.5px 0px;
        opacity: 1;
    }
    
    form .file .file-custom {
        background: linear-gradient(to left, transparent 100%, #f1f1f1 100px);
    }
    
    form .file .file-custom:after {
        content: '';
    }
    
    form input[type="submit"],
    form input[type="submit"] {
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
    
    .fileviewer-wrap {
        padding: 0px;
        border-radius: 5px;
        text-align: left;
        background: #eee;
    }
    
    .fileviewer-wrap a {
        color: #399ae5;
        text-decoration: none;
    }

    .fileviewer-wrap a:hover {
        color: #206ba4;
        text-decoration: none;
    }

    .fileviewer-wrap .note {
        padding: 0 5px 25px 0;
        font-size: 80%;
        color: #666;
        line-height: 18px;
    }

    .fileviewer-wrap .block {
        clear: both;
        min-height: 50px;
        border-top: solid 1px #ECE9E9;
    }

    .fileviewer-wrap .block:first-child {
        border: none;
    }

    .fileviewer-wrap .block .img {
        width: 50px;
        height: 50px;
        display: block;
        float: left;
        margin-right: 10px;
        background: transparent url(<?= $GLOBALS['icon_url']; ?>) no-repeat 0 0;
    }

    .fileviewer-wrap .block .file {
        height: auto;
        padding-bottom: 5px;
    }

    .fileviewer-wrap .block .data {
        line-height: 1.3em;
        color: #666;
    }

    .fileviewer-wrap .block a {
        display: flex;
        align-items: center;
        padding: 20px;
        transition: all 0.35s;
    }
    
    .fileviewer-wrap .block a .actions {
        margin-left: auto;
    }
    
    .fileviewer-wrap .block a .actions .remove {
        color: red;
        padding: 20px;
        margin-right: -20px;
    }

    .fileviewer-wrap .block a:hover,
    .fileviewer-wrap .block a.open {
        text-decoration: none;
        background: #efefef;
    }

    .fileviewer-wrap .bold {
        font-weight: 900;
    }

    .fileviewer-wrap .upper {
        text-transform: uppercase;
    }

    .fileviewer-wrap .fs-1 {
        font-size: 1em;
    }

    .fileviewer-wrap .fs-1-1 {
        font-size: 1.1em;
    }

    .fileviewer-wrap .fs-1-2 {
        font-size: 1.2em;
    }

    .fileviewer-wrap .fs-1-3 {
        font-size: 1.3em;
    }

    .fileviewer-wrap .fs-0-9 {
        font-size: 0.9em;
    }

    .fileviewer-wrap .fs-0-8 {
        font-size: 0.8em;
    }

    .fileviewer-wrap .fs-0-7 {
        font-size: 0.7em;
    }

    .fileviewer-wrap .jpg, .fileviewer-wrap .jpeg, .fileviewer-wrap .gif, .fileviewer-wrap .png {
        background-position: -50px 0 !important;
    }

    .fileviewer-wrap .pdf {
        background-position: -100px 0 !important;
    }

    .fileviewer-wrap .txt, .fileviewer-wrap .rtf {
        background-position: -150px 0 !important;
    }

    .fileviewer-wrap .xls, .fileviewer-wrap .xlsx {
        background-position: -200px 0 !important;
    }

    .fileviewer-wrap .ppt, .fileviewer-wrap .pptx {
        background-position: -250px 0 !important;
    }

    .fileviewer-wrap .doc, .fileviewer-wrap .docx {
        background-position: -300px 0 !important;
    }

    .fileviewer-wrap .zip, .fileviewer-wrap .rar, .fileviewer-wrap .tar, .fileviewer-wrap .gzip {
        background-position: -350px 0 !important;
    }

    .fileviewer-wrap .swf {
        background-position: -400px 0 !important;
    }

    .fileviewer-wrap .fla {
        background-position: -450px 0 !important;
    }

    .fileviewer-wrap .mp3 {
        background-position: -500px 0 !important;
    }

    .fileviewer-wrap .wav {
        background-position: -550px 0 !important;
    }

    .fileviewer-wrap .mp4 {
        background-position: -600px 0 !important;
    }

    .fileviewer-wrap .mov, .fileviewer-wrap .aiff, .fileviewer-wrap .m2v, .fileviewer-wrap .avi, .fileviewer-wrap .pict, .fileviewer-wrap .qif {
        background-position: -650px 0 !important;
    }

    .fileviewer-wrap .wmv, .fileviewer-wrap .avi, .fileviewer-wrap .mpg {
        background-position: -700px 0 !important;
    }

    .fileviewer-wrap .flv, .fileviewer-wrap .f2v {
        background-position: -750px 0 !important;
    }

    .fileviewer-wrap .psd {
        background-position: -800px 0 !important;
    }

    .fileviewer-wrap .ai {
        background-position: -850px 0 !important;
    }

    .fileviewer-wrap .html, .fileviewer-wrap .xhtml, .fileviewer-wrap .dhtml, .fileviewer-wrap .php, .fileviewer-wrap .asp, .fileviewer-wrap .css, .fileviewer-wrap .js, .fileviewer-wrap .inc {
        background-position: -900px 0 !important;
    }

    .fileviewer-wrap .dir {
        background-position: -950px 0 !important;
    }

    .fileviewer-wrap .sub {
        margin-left: 42px;
        border-left: solid 5px #3498DB;
        display: none;
        margin-bottom: 30px;
    }
     
</style>

<div class="wrap">
    
    <h1 class="wp-heading-inline">Strumenti / Importa Documenti</h1>
    
    <hr class="wp-header-end">

    <?php if($import_result['message']) { ?>
        <div class="message <?= ($import_result['status']) ? 'updated' : 'error' ?>">
            <p><?= $import_result['message'] ?></p>
        </div>
    <?php } ?>
    
    <form method="post" action="<?= $_SERVER['REQUEST_URI']; ?>" enctype="multipart/form-data">
       
        <input type="text" name="import-file_field" value="" hidden="hidden">
        <input type="text" name="import-file_post" value="" hidden="hidden">
        
        <div class="select select-path">
            <select aria-label="Seleziona un percorso" name="import-file_path" required>
                <option value="" selected="selected">Seleziona un percorso</option>
                <option value="<?= $uploads_dir ?>">/</option>
                <?php
                if (is_dir($uploads_dir)) {
                    if ($dh = opendir($uploads_dir)) {
                        while (($file = readdir($dh)) !== false) {
                            if(filetype($uploads_dir .'/'. $file) == 'dir' && $file != '.' && $file != '..') { ?>
                                <option value="<?= $uploads_dir .'/'. $file ?>">/<?= $file ?></option>
                                <?php
                                $subdh = opendir($uploads_dir .'/'. $file .'/');
                                while (($subfile = readdir($subdh)) !== false) {
                                    if(filetype($uploads_dir .'/'. $file .'/'. $subfile) == 'dir' && $subfile != '.' && $subfile != '..') { ?>
                                        <option value="<?= $uploads_dir .'/'. $file .'/'. $subfile ?>">/<?= $file ?>/<?= $subfile ?></option>
                                    <?php
                                    }
                                }
                            }
                        }
                        closedir($dh);
                    }
                }
                ?>
            </select>
        </div>
        
        <div class="select select-path_sub select-consegne-ddt" data-field="allegato-ddt" style="display:none">
            <select aria-label="Seleziona un percorso">
                <option value="" selected="selected">Seleziona una consegna</option>
                <option value="">Nessuna consegna</option>
                <option value="*">Importazione multipla</option>
                <?php
                /*
                $loop = new WP_Query( array(
                    'post_type' => 'consegne',
                    'posts_per_page' => 100
                ) );
                while($loop->have_posts()) : $loop->the_post(); ?>
                    <option value="<?php echo get_the_ID() ?>"><?php the_title() ?></option>
                <?php endwhile; */ ?>
            </select>
        </div>
        
        <div class="select select-path_sub select-consegne-ddt_firmato" data-field="allegato-ddt_firmato" style="display:none">
            <select aria-label="Seleziona un percorso">
                <option value="" selected="selected">Seleziona una consegna</option>
                <option value="">Nessuna consegna</option>
                <option value="*">Importazione multipla</option>
                <?php
                /*
                $loop = new WP_Query( array(
                    'post_type' => 'consegne',
                    'posts_per_page' => -1
                ) );
                while($loop->have_posts()) : $loop->the_post(); ?>
                    <option value="<?= get_the_ID() ?>"><?php the_title() ?></option>
                <?php endwhile; */ ?>
            </select>
        </div>
        
        <div class="select select-path_sub select-consegne-firma" data-field="allegato-firma" style="display:none">
            <select aria-label="Seleziona un percorso">
                <option value="" selected="selected">Seleziona una consegna</option>
                <option value="">Nessuna consegna</option>
                <option value="*">Importazione multipla</option>
                <?php
                /*
                $loop = new WP_Query( array(
                    'post_type' => 'consegne',
                    'posts_per_page' => -1
                ) );
                while($loop->have_posts()) : $loop->the_post(); ?>
                    <option value="<?= get_the_ID() ?>"><?php the_title() ?></option>
                <?php endwhile; */ ?>
            </select>
        </div>
        
        <div class="select select-path_sub select-scadenze-ddt" data-field="allegato-ddt" style="display:none">
            <select aria-label="Seleziona un percorso">
                <option value="" selected="selected">Seleziona una scadenza</option>
                <option value="">Nessuna scadenza</option>
                <option value="*">Importazione multipla</option>
                <?php
                /*
                $loop = new WP_Query( array(
                    'post_type' => 'scadenze',
                    'posts_per_page' => -1
                ) );
                while($loop->have_posts()) : $loop->the_post(); ?>
                    <option value="<?= get_the_ID() ?>"><?php the_title() ?></option>
                <?php endwhile; */ ?>
            </select>
        </div>
        
        <div class="select select-path_sub select-scadenze-firma" data-field="allegato-firma" style="display:none">
            <select aria-label="Seleziona un percorso">
                <option value="" selected="selected">Seleziona una scadenza</option>
                <option value="">Nessuna scadenza</option>
                <option value="*">Importazione multipla</option>
                <?php
                /*
                $loop = new WP_Query( array(
                    'post_type' => 'scadenze',
                    'posts_per_page' => -1
                ) );
                while($loop->have_posts()) : $loop->the_post(); ?>
                    <option value="<?= get_the_ID() ?>"><?php the_title() ?></option>
                <?php endwhile; */ ?>
            </select>
        </div>
       
        <label class="file" style="display:none">
            <input type="file" id="file" name="import-file[]">
            <span class="file-custom"></span>
        </label>
        
        <input type="submit" name="import" value="Importa" style="display:none">
        
    </form>
    
    <div class="fileviewer-wrap">
        <?php
        
        // FUNCTIONS TO MAKE THE MAGIC HAPPEN, BEST TO LEAVE THESE ALONE

        function ext($filename) {
            return substr( strrchr( $filename, '.' ), 1 );
        }

        function display_size($bytes, $precision = 2) {
            $units = array('B', 'KB', 'MB', 'GB', 'TB');
            $bytes = max($bytes, 0); 
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
            $pow = min($pow, count($units) - 1); 
            $bytes /= (1 << (10 * $pow)); 
            return round($bytes, $precision) . '<span class="fs-0-8 bold">' . $units[$pow] . "</span>";
        }

        function count_dir_files( $dir) {
	        $abs_dir = (substr(ABSPATH, 0, -1));
            $fi = new FilesystemIterator($abs_dir.$dir, FilesystemIterator::SKIP_DOTS);
            return iterator_count($fi);
        }

        function get_directory_size($path) {
            $bytestotal = 0;
            $path = realpath($path);
            if($path!==false && $path!='' && file_exists($path)) {
                foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
                    $bytestotal += $object->getSize();
                }
            }

            return display_size($bytestotal);
        }


        // SHOW THE MEDIA BLOCK
        function display_block( $file ) {
	        $abs_dir = (substr(ABSPATH, 0, -1));
            $file_ext = ext($file);
            if( !$file_ext && is_dir($abs_dir.$file)) $file_ext = "dir";
            if(in_array($file, $GLOBALS['ignore_file_list'])) return;
            if(in_array($file_ext, $GLOBALS['ignore_ext_list'])) return;

            $download_att = ($GLOBALS['force_download'] && $file_ext != "dir" ) ? " download='" . basename($file) . "'" : "";

            $rtn = "<div class=\"block\">";
            $rtn .= "<a href=\"$file\" target=\"_blank\" class=\"$file_ext\"{$download_att}>";
            $rtn .= "	<div class=\"img $file_ext\"></div>";
            $rtn .= "	<div class=\"name\">";

            if ($file_ext === "dir") {
                $rtn .= "		<div class=\"file fs-1-2 bold\">" . basename($file) . "</div>";
                $rtn .= "		<div class=\"data upper size fs-0-7\"><span class=\"bold\">" . count_dir_files($file) . "</span> elementi</div>";
                $rtn .= "		<div class=\"data upper size fs-0-7\"><span class=\"bold\">Dimensione:</span> " . get_directory_size($file) . "</div>";

            }
            else {
                $rtn .= "		<div class=\"file fs-1-2 bold\">" . basename($file) . "</div>";
                $rtn .= "		<div class=\"data upper size fs-0-7\"><span class=\"bold\">Dimensione:</span> " . display_size(filesize($abs_dir.$file)) . "</div>";
                $rtn .= "		<div class=\"data upper modified fs-0-7\"><span class=\"bold\">Ultima modifica:</span> " . date('d/m/Y - H:i:s', filemtime($abs_dir.$file)) . "</div>";
            }

            $rtn .= "	</div>";
            
            if ($file_ext !== "dir") {
                $rtn .= "<div class=\"actions\"><span class=\"remove\">ELIMINA</span></div>";
            }
            
            $rtn .= "	</a>";
            $rtn .= "</div>";
            return $rtn;
        }


        // RECURSIVE FUNCTION TO BUILD THE BLOCKS
        function build_blocks( $items, $folder ) {
	        $abs_dir = (substr(ABSPATH, 0, -1));
            $objects = array();
            $objects['directories'] = array();
            $objects['files'] = array();

            foreach($items as $c => $item) {
                if( $item == ".." || $item == ".") continue;

                // IGNORE FILE
                if(in_array($item, $GLOBALS['ignore_file_list'])) continue;

                if( $folder && $item ) {
                    $item = "$folder/$item";
                }

                $file_ext = ext($item);

                // IGNORE EXT
                if(in_array($file_ext, $GLOBALS['ignore_ext_list'])) continue;

                // DIRECTORIES
                if( is_dir($abs_dir.$item) ) {
                    $objects['directories'][] = $item; 
                    continue;
                }

                // FILE DATE
                //$file_time = date("U", filemtime($item));
                $file_time = '';

                // FILES
                if( $item ) {
                    $objects['files'][$file_time . "-" . $item] = $item;
                }
            }

            foreach($objects['directories'] as $c => $file) {
                
                $sub_items = (array) scandir( $abs_dir.$file );

                if( $GLOBALS['ignore_empty_folders'] ) {
                    $has_sub_items = false;
                    foreach( $sub_items as $sub_item ) {
                        $sub_fileExt = ext( $sub_item );
                        if( $sub_item == ".." || $sub_item == ".") continue;
                        if(in_array($sub_item, $GLOBALS['ignore_file_list'])) continue;
                        if(in_array($sub_fileExt, $GLOBALS['ignore_ext_list'])) continue;

                        $has_sub_items = true;
                        break;	
                    }

                    if( $has_sub_items ) echo display_block( $file );
                } else {
                    echo display_block( $file );
                }

                if( $GLOBALS['toggle_sub_folders'] ) {
                    if( $sub_items ) {
                        echo "<div class='sub' data-folder=\"$file\">";
                        build_blocks( $sub_items, $file );
                        echo "</div>";
                    }
                }
            }

            // SORT BEFORE LOOP
            if( $GLOBALS['sort_by'] == "date_asc" ) ksort($objects['files']);
            elseif( $GLOBALS['sort_by'] == "date_desc" ) krsort($objects['files']);
            elseif( $GLOBALS['sort_by'] == "name_asc" ) natsort($objects['files']);
            elseif( $GLOBALS['sort_by'] == "name_desc" ) arsort($objects['files']);

            foreach($objects['files'] as $t => $file) {
                $fileExt = ext($file);
                if(in_array($file, $GLOBALS['ignore_file_list'])) continue;
                if(in_array($fileExt, $GLOBALS['ignore_ext_list'])) continue;
                echo display_block( $file );
            }
        }

        // GET THE BLOCKS STARTED, FALSE TO INDICATE MAIN FOLDER
        $items = scandir( $uploads_dir );
        build_blocks( $items, '/wp-content/uploads/app' );
        ?>

        <?php
        if($GLOBALS['toggle_sub_folders']) { ?>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    
                    $("select").prop('selectedIndex', 0);
                    
                    $(".fileviewer-wrap .block a.dir").click(function(e) {
                        
                        e.preventDefault();
                        
                        $(this).toggleClass('open');
                        $('.sub[data-folder="' + $(this).attr('href') + '"]').slideToggle();
                    });
                    
                    $(".fileviewer-wrap .block a .actions .remove").click(function(e) {
                        
                        e.preventDefault();
                        
                        let itemName = $(this).closest('a').find('.file').text(),
                            itemUrl = $(this).closest('.sub').data('folder');
                        
                        if(!itemUrl) itemUrl = '/wp-content/uploads/app/';
                        
                        window.location.href = window.location.href +'&delete='+ itemName +'&folder='+ itemUrl;
                    });
                    
                    $("select[name='import-file_path']").on('change', function() {
                        
                        let option = $("option:selected", this).text();
                        
                        if(option) {
                            $("label.file, input[type='submit'][name='import']").show();
                        }
                        
                        if(option == '/scadenze/firme' || option == '/consegne/firme') {
                            $("#file").attr('accept', '.png');
                            $("#file").removeAttr('multiple');
                        } else if(option == '/scadenze/originali' || option == '/scadenze/firmati' || option == '/consegne/originali' || option == '/scadenze/firmati') {
                            $("#file").attr('accept', '.pdf');
                            $("#file").removeAttr('multiple');
                        } else {
                            $("#file").removeAttr('accept');
                            $("#file").attr('multiple', true);
                        }
                        
                        option = option.split('/');

                        $(".select-path_sub").hide();
                        $(".select-path_sub select").prop('selectedIndex', 0);

                        if(option.length == 3) {

                            let query = 'select-'+ option[1] +'-';

                            if(option[2] == 'originali') query += 'ddt';
                            else if(option[2] == 'firmati') query += 'ddt_firmato';
                            else if(option[2] == 'firme') query += 'firma';

                            $("input[name='import-file_field']").attr('value', $(".select-path_sub."+ query).data('field'));
                            $(".select-path_sub."+ query).show();
                        }
                    });
                    
                    $(".select-path_sub select").on('change', function() {
                        
                        let option = $("option:selected", this).val();
                        
                        if(!option || option == '*') {
                            $("#file").attr('multiple', true);
                        } else {
                            $("#file").removeAttr('multiple');
                        }
                        
                        $("input[name='import-file_post']").attr('value', option);
                    });
                });
            </script>
        <?php } ?>
    </div>
    
</div>
