/*global jQuery: true */
jQuery(function ($) {
    "use strict";

    //
    $('.gallery .gallery-thumbnails a').on('click', function () {
        var $this = $(this),
            $gallery = $(this).parents('.gallery'),
            $img = $gallery.find('.gallery-display .gallery-image'),
            $thumbnails = $gallery.find('.gallery-thumbnails'),
            delay = 500;

        $img.fadeOut(function () {
            $img.one('onreadystatechange load', function () { //Set something to run when it finishes loading

                if (this.complete) {
                    $img.fadeIn();
                } //Fade it in when loaded
            })
                .attr('src', $this.data('src'))//Set the source so it begins fetching
                .attr('title', $this.attr('title'));
        });

        // Set thumbnails viewport
        var $current = $thumbnails.find('.current').removeClass('current'),
            $me = $(this).parent('li').addClass('current'),
            index = $thumbnails.find('li').index($me),
            total = $thumbnails.find('li').length;

        if (total - index < 6) {
            index = total > 6 ? total - 6 : 0;
        }
        else {
            if(index > 1) {
                index = index - 1;
            }
            else {
                index = 0;
            }
        }

        $thumbnails.animate({marginLeft: -1 * index * $me.outerWidth() }); //.css('margin-left', pos * -110);


        if (!$('#gallery-meta').hasClass('close')) {
            $('#gallery-meta').html(
                get_descricao_foto() +
                    '<span class="arrow"></span>');
        }

        if ($me.next('li').length) {
            $gallery.find('.gallery-next').show();
        }
        else {
            $gallery.find('.gallery-next').hide();
        }

        if ($me.prev('li').length) {
            $gallery.find('.gallery-prev').show();
        }
        else {
            $gallery.find('.gallery-prev').hide();
        }

        //$('#slider').slider('option', 'value', $me.index());


        var x = parseInt(window.location.hash.substr(1), 10);

        if (x !== $me.index()) {
            // TODO emit change event
            window.location.href = '#' + $me.index();
        }
    });

    // Keyboard navigation
    $(document).on('keydown', function (e) {
        switch (e.keyCode) {
            case 39:
                $('.gallery .gallery-next').click();
                break;
            case 37:
                $('.gallery .gallery-prev').click();
                break;
        }
    });

    $('.gallery .gallery-next').on('click', function () {
        var $thumbnails = $(this).parents('.gallery').find('.gallery-thumbnails');
        if (!$thumbnails.find('.current').next('li').find('a').click().length) {
            $thumbnails.find('li:first').find('a').click();
        }
    });

    $('.gallery .gallery-prev').on('click', function () {
        var $thumbnails = $(this).parents('.gallery').find('.gallery-thumbnails');

        if (!$thumbnails.find('.current').prev('li').find('a').click().length) {
            $thumbnails.find('li:last').find('a').click();
        }
    });


    function get_descricao_foto() {

        var content = $('.gallery .gallery-thumbnails .current a p').html();

        if (!content.length) {
            content = $('.gallery .thumbs .current a img').attr('alt');
        }

        content += '&nbsp;<strong>' + $('.gallery .thumbs .current a').attr('title') + '</strong>';

        return content;
    }

    $('#gallery-meta').on('click', '.arrow', function () {
        var $this = $(this),
            $p = $('#gallery-meta');

        if ($p.hasClass('close')) {

            $this.before(get_descricao_foto());

            $p.removeClass('close');
        }
        else {
            $p.html('<span class="arrow"></span>').addClass('close');
        }
    });


// Initialize galleries
    (function () {
        var x = parseInt(window.location.hash.substr(1), 10);
        if (x > 0) {
            $('.gallery .gallery-thumbnails a:eq(' + x + ')').click();
        }
    })();

});
