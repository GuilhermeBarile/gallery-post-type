<style type="text/css">
    .upload-flash-bypass {
        display: none;
    }

    #media-items a.button {
        display: block;
        margin-bottom: 3px;
        padding-left: 4px;
    }

    #media-mass-editor td.field *{
        width: 100%;
    }

    .mass-edit-item {
        width: 120px; height: 70px;
        float: left;
    }

    .mass-edit-item input {
        display: block; height: 60px;
        margin-right: 2px;
        float: left;
    }

    .mass-edit-item img {
        display: block; float: left;
    }
    #media-items {
        width: 100%;
    }

    #media-items .media-item {
        width: 150px !important;
        height: 150px !important;
        margin: 10px 4px;
        float: left;
        text-align: center;
        position: relative;
    }

    #media-items .media-item > img {
        max-width: 150px;
        max-height: 150px;
    }
    .media-item .progress {
        width: 130px !important;
    }
    .media-item .buttons {
        position: absolute;
        top: 5px;
        right: 5px;
        width: 25px;
        height: 32px;
    }

    #media-editor.loading *,.media-item.loading * {
        display: none;
    }

    #confirm-delete-attachment.loading, #media-editor.loading, .media-item.loading, #media-mass-editor.loading {
        background: url(<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) )?>) no-repeat center center;
    }

    .media-item.loading img {
        display: none;
    }

    .media-item.active {
        width: 300px;
    }

    #file-form {
        clear: left;
    }

    #media-editor {
        padding: 0;
    }

    #media-editor .media-upload-form {
        margin-top: -20px;
    }

    #media-editor .loading {
        padding-top: 150px;
    }

    #media-editor img {
        margin: 0 auto;
        display: block;
    }

    #media-items .wrapper {
        width: 150px;
        height: 150px;
        overflow: hidden;
    }

    #media-items .buttons {
        display: none;
    }

    #media-items .is_thumbnail .button.capa {
        display: none;
    }

    #media-items .is_thumbnail {
        width: 146px;
        height: 146px;
        border: 2px solid blue;
    }

    #media-items .is_thumbnail img {
        width: 146px;
        height: 146px;
    }

    #confirm-delete-attachment.loading * {
        display: none;
    }

</style>




<script type="text/javascript">

    jQuery(function ($) {

        var $mediaItems = $('#media-items'),
            $mediaEditor = $('<div id="media-editor" title="Detalhes da Imagem"></div>').appendTo('body'),
            $confirmDelete = $('<div id="confirm-delete-attachment" title="Excluir imagem"><p>' +
                'Excluir a imagem do disco, ou manter na biblioteca ?' +
                '</p></div>').appendTo('body');;

        $('#fileupload').fileupload({
            formData: asyncupload,
            dataType: 'json',
            sequentialUploads: true,
            add: function (e, data) {
                data.context = $('<div class="media-item"/>').text('Uploading...').appendTo('#media-items');
                data.submit();
            },
            done: function (e, data) {
                data.context.html(data.result.data.media_item_html);
            }
        });


        $mediaItems.sortable({ handler:'.media-item'});

        function remove_attachment(action, id, cb) {
            $('#confirm-delete-attachment').addClass('loading');
            $.ajax({
                url: ajaxurl,
                data: { action: 'galleryremove', delete_action: action, attachment_id: id},
                type: 'POST',
                success: function() {
                    var $parent = $('.buttons[data-id=' + id + ']').parents('.media-item');

                    $parent.fadeOut('fast', function() {
                        $parent.remove();
                    });

                    $('#confirm-delete-attachment').removeClass('loading').dialog('close');
                    if(undefined != cb) {
                        cb();
                    }

                },
                error: function(e) {
                    alert(e.responseText);
                }
            })
        }

        $('#confirm-delete-attachment').dialog({
            autoOpen:false,
            modal:true,
            beforeClose: check_loading,
            width: 600,
            buttons: {
                'Remover e Excluir': function() {
                    if($confirmDelete.hasClass('loading')) {
                        return;
                    }

                    var id = $confirmDelete.data('id');
                    remove_attachment('delete', id);

                },
                'Remover e Manter': function() {
                    if($confirmDelete.hasClass('loading')) {
                        return;
                    }

                    var id = $confirmDelete.data('id');
                    remove_attachment('remove', id);
                },
                'Cancelar': function() {
                    $confirmDelete.dialog('close');
                }
            }
        });

        function check_loading() {
            if($(this).hasClass('loading')) {
                return false;
            }
        }

        $('#media-editor').dialog({
            autoOpen:false,
            width:640,
            height:545,
            modal:true,
            beforeClose: check_loading,
            buttons:{
                'Salvar':function () {
                    if($mediaEditor.hasClass('loading')) {
                        return;
                    }
                    $mediaEditor.addClass('loading');
                    $.ajax($mediaEditor.data('url'), {
                        data:$('#media-single-form').serialize(),
                        type:'POST',
                        success:function (data) {
                            $mediaEditor.removeClass('loading');
                            $mediaEditor.dialog('close');
                        },
                        error:function (data) {
                            $('#media-editor').removeClass('loading');
                            alert('Erro atualizando anexo, tente novamente');
                        }
                    });
                },
                'Fechar':function () {
                    $('#media-editor').dialog('close');
                }
            }
        });

        // Edit image
        $mediaItems.on('click', '.button.editar', function () {
            //media.php?attachment_id=37994&action=edit
            var id = $(this).parent('.buttons').data('id'),
                url = '<?php echo admin_url('media.php') ?>?attachment_id=' + id + '&action=edit';
            $mediaEditor.data('url', url)
                .dialog('open');
            $mediaEditor.addClass('loading')
                .load(url + ' #media-single-form', function () {

                    $mediaEditor.removeClass('loading').find('.submit').remove();
                    $mediaEditor.find('#imgedit-open-btn-' + id).remove();
                    //$('#media-editor tr.post_title span').text('Créditos');
                    // $('#media-editor tr.image_alt').hide();
                    //$('#media-editor input[name=_wp_original_http_referer]').val('upload.php?do_not_redirect');
                });
            return false;
        });

        // Set post thumbnail
        $mediaItems.on('click', '.button.capa', function () {
            var post_id = asyncupload.post_id,
                id = $(this).parent('.buttons').data('id'),
                $media_item = $(this).parents('.media-item');

            $media_item.addClass('loading');
            $.ajax({
                type:'POST',
                url:ajaxurl,
                data:{ action:'gallerythumb', post_id:post_id, thumbnail_id:id },
                success:function (response) {
                    $('#media-items div').removeClass('is_thumbnail');
                    $('#buttons-' + id).parent('div').addClass('is_thumbnail');
                    $media_item.removeClass('loading');
                },
                error: function(e) {
                    alert(e.responseText);
                    $media_item.removeClass('loading');
                }
            });

            return false;
        });

        $mediaItems.on('click', '.button.del', function () {
            var id = $(this).parent('.buttons').data('id');

            $('#confirm-delete-attachment').removeClass('loading').data('id', id).dialog('open');

            return false;
        });


        $mediaItems.on('mouseenter', '.media-item',
            function () {
                $(this).find('.buttons').show('fast');
            }).on('mouseleave', '.media-item', function () {
                $(this).find('.buttons').hide();
            });



        $('#media-mass-editor').dialog({
            autoOpen: false,
            width: 640,
            height: 545,
            modal: true,
            buttons: {
                /*
                 action: 'gallerymassedit',
                 id: jQuery('#media-mass-editor').data('id')
                 */
                'Salvar': function() {

                    if(confirm('Confirma edição das fotos')) {
                        $.post(ajaxurl + '?action=gallerymassedit&id=' + jQuery('#media-mass-editor').data('id'), $('#media-mass-editor form').serialize(), function(data) {
                            $('#media-mass-editor').dialog('close');
                        }).error(function() {
                                alert('Erro gravando dados');
                            });
                    }
                },
                'Fechar': function() {
                    $('#media-mass-editor').dialog('close');
                }
            }
        });


        $('#media-mass-editor').on('click', '#sel-todos', function() {
            //alert($('#mass-select-all').attr('checked'));
            $('#media-mass-editor .mass-select').attr('checked', true);
            return false;
        });

        $('#media-mass-editor').on('click', '#sel-nenhum', function() {
            //alert($('#mass-select-all').attr('checked'));
            $('#media-mass-editor .mass-select').attr('checked', false);
            return false;
        });

        $('#media-mass-editor').on('click', '#sel-inverter', function() {
            //alert($('#mass-select-all').attr('checked'));
            $('#media-mass-editor .mass-select').each(function(i, e) {
                $(this).attr('checked', !$(this).is(':checked') );
            });

            return false;
        });
    });

function edicao_em_massa() {

    (function($) {
        var data = {
            action: 'gallerymassedit',
            id: jQuery('#media-mass-editor').data('id')
        };

        $('#media-mass-editor').html('').addClass('loading');
        // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
        $.get(ajaxurl, data, function(response) {
            $('#media-mass-editor').removeClass('loading').html(response);

        });

        jQuery('#media-mass-editor').dialog('open');
    })(jQuery);


    return false;
}

</script>
<?php
// Use nonce for verification
wp_nonce_field( plugin_basename( dirname(__FILE__) ), 'gallery_posttype' );
?>
<div id="media-mass-editor" title="Edição em massa" data-id="<?php echo $_GET['post'] ?>">

</div>
<div style="text-align: right;">

    <?php /* if(!empty($_GET['post'])): ?>
    <a href="#" onclick="return edicao_em_massa();">Edição em massa</a>
        <?php else: ?>
    Publique a galeria para habilitar a edição em massa
    <?php endif; */ ?>
</div>
<div id="media-items">

    <?php foreach ($attachments as $attachment): ?>
        <div class="media-item">
            <?php echo gallery_item($attachment, $thumbnail_id); ?>
        </div>
        <?php endforeach; ?>
</div>
<div style="clear: both; height: 50px;"></div>
<input id="fileupload" type="file" name="async-upload" data-url="<?php echo admin_url('async-upload.php'); ?>?gallery_posttype=1" multiple>
