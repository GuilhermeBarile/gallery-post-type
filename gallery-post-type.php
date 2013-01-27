<?php
/*
Plugin Name: Gallery Post Type
Plugin URI: https://github.com/guigouz/gallery-post-type
Description: Implements a "gallery" custom post type and its editor
Version: 1.0
Author: Guilherme Barile
Author URI: https://guigouz.github.com
License: GPLv2 or later
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
    echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
    exit;
}


add_image_size('gallery-admin_thumbnail', 100, 100, true);

// TODO allow themes to set the default image sizes
add_image_size('gallery-image', 980, 600, false);
add_image_size('gallery-thumbnail', 100, 60, true);

add_action('init', 'gallery_posttype_init');
add_action('admin_init', 'gallery_posttype_admin_init');


// Shortcode that allows embedding
add_shortcode('gallery', 'gallery_posttype_shortcode');

function gallery_posttype_init() {

    // TODO make rewrite and archive urls configurable

    register_post_type('gallery', array(
        'exclude_from_search' => false,
        'public' => true,
        'show_ui' => true,
        'capability_type' => 'post',
        'hierarchical' => false,
        'rewrite' => array('slug' => 'gallery', 'hierarchical' => true),
        'supports' => array('title'),
        'has_archive' => 'galleries',
        'labels' => array(
            'name' => __('Galleries', 'gallery-posttype'),
            'singular_name' => __('Album', 'gallery-posttype'),
            'add_new' => __('New Gallery', 'gallery-posttype'),
            'search_items' => __('Search Albums', 'gallery-posttype'),
            'all_items' => __('All', 'gallery-posttype'),
            'edit_item' => __('Edit', 'gallery-posttype'),
            'upload_item' => __('Upload', 'gallery-posttype'),
            'add_new_item' => __('New Gallery', 'gallery-posttype')
        ),
        'taxonomies' => array('post_tag', 'post_category')
    ));


    if(isset($_REQUEST['gallery_posttype'])) {
        add_filter('wp_prepare_attachment_for_js', 'gallery_posttype_ajax_attachment');
    }
}

function gallery_posttype_admin_init() {
    add_action('save_post', 'gallery_posttype_save');


    add_action('wp_ajax_gallerythumb', 'gallery_posttype_update_thumbnail');
    add_action('wp_ajax_galleryremove', 'gallery_posttype_remove_item');


    add_meta_box('gallery', 'Galeria', 'gallery_posttype_metabox', 'gallery', 'normal', 'default');

}



function gallery_posttype_ajax_attachment($att) {

    $att['media_item_html'] = gallery_item($att['id']);

    return $att;
}



// TODO
function gallery_posttype_embed_box() {
    echo "<p>Use the code below to embed this gallery on other posts</p>";
    echo "<div style='background-color: white; border: 1px dotted black; padding: 2px;'>[gallery]{$_GET['post']}[/gallery]</div>";
}

function gallery_posttype_metabox() {
    global $post;
    $attachments = get_post_attachments();
//    if ($attachments) {
//        foreach ( $attachments as $attachment ) {
//            echo apply_filters( 'the_title' , $attachment->post_title );
//            the_attachment_link( $attachment->ID , false );
//        }
//    }

    if (!current_user_can('upload_files'))
        wp_die(__('You do not have permission to upload files.'));

    wp_enqueue_script('jquery-iframe-transport', plugins_url('js/jquery.iframe-transport.js', __FILE__));
    wp_enqueue_script('jquery-fileupload', plugins_url('js/jquery.fileupload.js', __FILE__));
    wp_localize_script('jquery-fileupload', 'asyncupload', array(
            // URL to wp-admin/admin-ajax.php to process the request
            'action' => 'upload-attachment',
            'post_id' => $_GET['post'],
            // generate a nonce with a unique ID "myajax-post-comment-nonce"
            // so that you can check it later when an AJAX request is sent
            '_ajax_nonce' => wp_create_nonce('media-form')
        )
    );

    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('jquery-ui-dialog');
    wp_register_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.1/themes/smoothness/jquery-ui.css', true);
    wp_enqueue_style("jquery-style");

    //$_REQUEST['post_id'] = $post->ID;
    //$type = "gallery";
//    wp_enqueue_script('plupload-handlers');
    wp_enqueue_script('set-post-thumbnail');
    wp_localize_script('set-post-thumbnail', 'MyAjax', array(
            // URL to wp-admin/admin-ajax.php to process the request
            'ajaxurl' => admin_url('admin-ajax.php'),

            // generate a nonce with a unique ID "myajax-post-comment-nonce"
            // so that you can check it later when an AJAX request is sent
            'postCommentNonce' => wp_create_nonce('myajax-post-comment-nonce'),
        )
    );

//    wp_enqueue_script('thickbox');
//    wp_enqueue_script('wp-ajax-response');
//    wp_enqueue_script('image-edit');
//    wp_enqueue_style('imgareaselect');
//    $form_class = 'media-upload-form type-form validate';
//
//    if (get_user_setting('uploader'))
//        $form_class .= ' html-uploader';
    $thumbnail_id = get_post_meta($post->ID, '_thumbnail_id', true);
    include dirname(__FILE__) . '/galeria-box.php';
}

function gallery_posttype_remove_item() {

    $attachment_id = $_POST['attachment_id'];
    $action = $_POST['delete_action'];

    switch ($action) {
        case 'remove':
            if (wp_update_post(array('ID' => $attachment_id, 'post_parent' => 0))) {
                die(0);
            }
            else {
                header('HTTP/1.1 500 Internal Server Error');
                die('Erro removendo anexo');
            }
            break;
        case 'delete':
//            if(!current_user_can('delete_attachments')) {
//                header('HTTP/1.1 403 Forbidden');
//                die('Você não tem permissão para excluir anexos');
//            }
            if (wp_delete_attachment($attachment_id)) {
                die(0);
            }
            else {
                header('HTTP/1.1 500 Internal Server Error');
                die('Erro excluindo anexo');
            }
            break;
        default:
            return -1;
    }
}

function gallery_posttype_save($post_id) {
    global $wpdb;



    // verify if this is an auto save routine.
    // If it is our form has not been submitted, so we dont want to do anything
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return;

    // verify this came from the our screen and with proper authorization,
    // because save_post can be triggered at other times
    if ( !wp_verify_nonce( $_POST['gallery_posttype'], plugin_basename( dirname(__FILE__) ) ) )
        return;

    if ( !current_user_can( 'edit_post', $post_id ) )
        return;


    // Check permissions
    if ( 'gallery' == $_POST['post_type'] )
    {
        $thumbnail = get_post_meta($post_id, '_thumbnail_id', true);

        for ($i = 0; $i < count($_POST['menu_order']); $i++) {
            if(!$thumbnail) {
                update_post_meta($post_id, '_thumbnail_id', $_POST['menu_order'][$i]);
                $thumbnail = $_POST['menu_order'][$i];
            }

            //error_log('updating '.$i.' '.$_POST['menu_order'][$i]);
            $wpdb->update($wpdb->posts, array('menu_order' => $i), array('ID' => $_POST['menu_order'][$i]), array('%d', '%d'), array('%d'));
        }
    }

}

function gallery_posttype_update_thumbnail() {
    $post_id = $_POST['post_id'];
    $thumbnail_id = $_POST['thumbnail_id'];

    update_post_meta($post_id, '_thumbnail_id', $thumbnail_id);
    die(0);
}





function fotos_videos_admin_init() {
    add_meta_box('galeria_content', 'Resumo', 'galeria_content', 'galeria', 'normal', 'high');

    if (isset($_GET['post'])) {
        add_meta_box('galeria_embed', 'Incorporar', 'galeria_embed_box', 'galeria', 'side', 'high');
    }
    add_filter('async_upload_gallery', 'gallery_item');
    add_filter('upload_post_params', 'gallery_post_params');
    add_filter('wp_redirect', 'galeria_ajax_redirect');
    add_action('wp_ajax_gallerymassedit', 'galeria_massedit');
    add_action('publish_galeria', 'gallery_save');
    add_action('pending_galeria', 'gallery_save');
    add_action('draft_galeria', 'gallery_save');

    add_action('publish_video', 'video_save');
    //add_action('pending_video', 'video_save');
    //add_action('draft_video', 'video_save');
    add_meta_box('url_video', 'URL do Vídeo', 'video_box', 'video', 'normal', 'high');
    add_meta_box('galeria_pub_id', 'Publicidade', 'post_publicidade_box', 'galeria', 'side', 'default');
}


function galeria_massedit() {
    global $wpdb;

    $id = $_GET['id'];
    if (!$id) {
        throw new Exception('ID Inválido');
    }

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {


        $atts = get_post_attachments($id);

        echo "<form id=\"mass-edit-form\">";
        echo "<table width='100%'><thead><tr><td colspan='2'><div style='height: 20px;'><b>Escolha as imagens</b>
        <div style='float: right; white-space: nowrap; font-size: 12px; '>
            Selecionar: <a href='#' id='sel-todos'>Todos</a> <a href='#' id='sel-nenhum'>Nenhum</a> <a href='#' id='sel-inverter'>Inverter</a>

        </div>

        </div>";
        foreach ($atts as $att) {
            $image_attributes = wp_get_attachment_image_src($att->ID, 'galeria-thumbnail');
            echo '<div class="mass-edit-item">
            <label><input type="checkbox" class="mass-select" name="do_update[]" value="' . $att->ID . '"/>
            <img src="' . $image_attributes[0] . '"/></label>
            </div>';
        }

        echo '<div style="clear: both;"></div>';
        echo "</td></tr></thead>";
//        // FORM

        echo <<<HTML
        <tbody>
        <tr>
            <td>Créditos</td>
            <td class="field"><input type="text" name="e[post_title]" onfocus="if(this.value == '') { this.value = 'Créditos: '; }"/></td>
        </tr>
        <tr>
            <td>Legenda</td>
            <td class="field"><textarea name="e[post_excerpt]"></textarea></td>
        </tr>
        <tr>
            <td>Descrição</td>
            <td class="field"><textarea name="e[post_content]"></textarea></td>
        </tr>

        </tbody>

HTML;

        echo "</table></form>";
        echo '<p class="help-block">Deixe em branco os campos que não devem ser alterados.</p>';
        exit;
    } else {

        $cond = array();

        if(empty($_POST['do_update'])) {
            exit('');
        }

        foreach(array('post_content', 'post_title', 'post_excerpt') as $f) {
            if(!empty($_POST['e'][$f]) && trim($_POST['e'][$f]) != '') {
                $cond[] = sprintf("`%s` = '%s'", $f,  $wpdb->escape($_POST['e'][$f]));
            }
        }


        $query = sprintf("UPDATE `%s` set %s where id IN (%s)", $wpdb->posts, implode(',', $cond), implode(',', $_POST['do_update']));


        $r = $wpdb->query($query);

        if($r !== FALSE) {
            exit('Ok');
        }
        else {
            header('HTTP/1.1 500 Server error');
            exit('Erro atualizando banco de dados');
        }
    }
}

function galeria_content() {
    global $post;

    echo '<style type="text/css">#galeria_content textarea { width: 99%; }</style>';
    echo '<textarea name="content" cols="40" rows="3">'
        . esc_attr($post->post_content)
        . '</textarea>';
}

function get_post_attachments($post_id = null) {
    if(!$post_id) {
        $post_id = get_the_ID();
    }
    $args = array('post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'orderby' => 'menu_order', 'order' => 'asc', 'post_parent' => $post_id);
    return get_posts($args);
}

function galeria_ajax_redirect($location, $status = null) {

    if (strpos('upload.php?do_not_redirect', $location) !== FALSE) {
        return NULL;
    }
    else {
        return $location;
    }
}

function gallery_post_params($post_params) {
    global $post;

    if ($post->post_type == 'galeria') {
        $post_params['type'] = 'gallery';
        $post_params['post_id'] = $post->ID;
        $post_params['short'] = 0;
    }

    return $post_params;

}


function gallery_item($attachment, $thumbnail = false) {
    if (is_numeric($attachment)) {
        $attachment = get_post($attachment);
        if ($attachment->post_type != 'attachment') {
            throw new Exception('Invalid attachment');
        }
    }
    $_t = '';
    if ($attachment->ID == $thumbnail) {
        $_t = 'is_thumbnail';
    }
    return "
    <div class='wrapper $_t'>
        <div id='buttons-{$attachment->ID}' class='buttons' data-id='{$attachment->ID}'>
            <a href='#' title='Editar' class='button editar'><span class='ui-icon ui-icon-pencil'></span></a>
            <a href='#' title='Definir como capa' class='button capa'><span class='ui-icon ui-icon-star'></span></a>
            <a href='#' title='Excluir' class='button del'><span class='ui-icon ui-icon-trash'></span></a>
        </div>"
        //."<a rel='{$attachment->ID}' href='#'>"
        //. wp_get_attachment_url( $attachment->ID)."'>"
        . wp_get_attachment_image($attachment->ID, 'thumbnail')
        //. "</a>"
        . "<input type='hidden' name='menu_order[]' value='{$attachment->ID}'/>
    </div>";
}

function video_box() {
    global $post;
    echo "<input type=\"text\" style=\"width: 98%;\" name=\"content\" value=\"" . esc_attr($post->post_content) . "\"/>";
}


function shortcode_galeria($atts, $content = null, $code = "", $context = null) {
    // $atts    ::= array of attributes
    // $content ::= text within enclosing form of shortcode element
    // $code    ::= the shortcode found, when == callback name
    // $context ::= the current context
    // examples: [my-shortcode]
    //           [my-shortcode/]
    //           [my-shortcode foo='bar']
    //           [my-shortcode foo='bar'/]
    //           [my-shortcode]content[/my-shortcode]
    //           [my-shortcode foo='bar']content[/my-shortcode]


    return get_embed_galeria($content);
}

function embed_galeria($post_id = null) {
    echo get_embed_galeria($post_id);
}

function get_embed_galeria($post_id = null) {
	global $tipo_galeria;
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    $pub_id = get_post_meta($post_id, 'pub_id', true);
    $attachments = get_post_attachments($post_id);
    $ret = '<div class="gallery">';
    if (count($attachments)) {
        if ($pub_id) {
            $ret .= '<div id="publicidade">
        <img alt="Publicidade" title="Publicidade" src="' . get_bloginfo('template_url') . '/img/fake/pub.jpg">
        <p>Publicidade <a title="Fechar Publicidade" id="fechar" href="#">Fechar Publicidade</a></p>
        </div>';
        }
		
		$btn = $tipo_galeria == 'full' ? '' : '<a href="#" title="Fullscreen" class="btn-full ir">Fullscreen</a>';
		
        $src = wp_get_attachment_image_src($attachments[0]->ID, 'galeria');
        $ret .= '<div class="slide">
        <img id="gallery-image" src="' . $src[0] .'" title="'.esc_attr($attachments[0]->post_title).'"/>
        <p id="gallery-meta" class="">
            '.(empty($attachments[0]->post_excerpt) ? $attachments[0]->post_content : $attachments[0]->post_excerpt).'
            <strong>'.$attachments[0]->post_title.'</strong>
                <span class="arrow"></span>
            </p>
            <span id="gallery-prev" class="seta ant">&lt;</span>
            <span id="gallery-next" class="seta prox">&gt;</span>
			'.$btn.'
        </div>';

        $ret .= '<div id="thumbs-container">
            <ul class="thumbs">';

        $i = 0;
        foreach ($attachments as $att) {
            $src = wp_get_attachment_image_src($att->ID, 'galeria');
            $thumb = wp_get_attachment_image_src($att->ID, 'galeria-thumbnail');

            $url = $src[0];
            $thumbnail = $thumb[0];

            $ret .= '<li ' . ($i == 0 ? 'class="current"' : '') . '>
                    <a href="#' . $i . '" data-src="' . $url . '" title="' . esc_attr($att->post_title) . '">
                    <img src="' . $thumbnail . '" alt="' . esc_attr((empty($att->post_content) ? $att->post_excerpt : $att->post_content)) . '"/>
                    <p>' . (empty($att->post_excerpt) ? $att->post_content : $att->post_excerpt) . '</p></a>
                    </li>';
            $i++;
        }
        $ret .= '</ul>
        </div>';

        $ret .= '<div class="slider">
            <div id="slider">

            </div>
            <!--span>110/140</span-->
        </div>';


    }
    $ret .= '</div>';

    return $ret;
}

