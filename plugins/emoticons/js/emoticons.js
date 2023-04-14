function change_emoticon_list(id, type) {
    var input = $("#search-emoticon-" + id);
    if (type == 0) {
        input.fadeIn().focus();
    } else {
        input.fadeOut();
    }
    $(".emoticon-box ."+id+"-list").hide('fast', function() {
        $("#"+id+"-list-" + type).show();
    });

    return false;
}
function add_emoticon(s, t) {
    var v = $(t).val() + " " + s + " ";
    $(t).val(v).focus();
    $(t).trigger('change');
    $('.emoticon-box').fadeOut();//we need to hide
    return false;
}

$(function () {

    $(document).on('click', '.emoticon-button', function() {
        //let get where the emoticon container is
        var e = $(this).next();
        if (e.length > 0 && e.hasClass('emoticon-box')) {

        } else {
            e = $(this).prev();
        }

        $('body').click(function(ev) {
            if(!$(ev.target).closest('.emoticon-box').length) {
                //alert($(e.target).attr('class'))
                if (!$(ev.target).hasClass('emoticon-button')) {
                    $('.emoticon-box').fadeOut();
                }
               
            }
        });

        if (e.css('display') == 'none') {
            if (e.html() == '') {
                $.ajax({
                    url : baseUrl + 'emoticon/load?target=' + $(this).data('target') + '&action=' + $(this).data('action') + '&csrf_token=' + requestToken,
                    success : function(data) {
                        e.html(data);
                        reloadInits();
                    }
                })
            }
            e.fadeIn();
        } else {
            e.fadeOut();
        }

        return false;
    });


    $(document).on("click", '.add-emoticon', function() {
        if ($(this).data('action') == 1) return false;
       return add_emoticon($(this).data('symbol'), $(this).data('target'))
    });

    $(document).on('click', ".emoticon-box .switch", function() {
       return change_emoticon_list($(this).data('id'), $(this).data('type'))
    });



    $(document).on("keyup", ".search-emoticon", function() {
        var id = $(this).data('id');
        var term = $(this).val();
        var c = $("#" + id + '-list-0');
        var a = $("." + id + '-list');
        if (term.length > 0) {
            c.show().html('');
            $.ajax({
                url : baseUrl + 'emoticon/search?csrf_token=' + requestToken,
                data : {term:term, target : $(this).data('target')},
                success : function(d){
                    c.html(d);
                    if (d) {
                        a.hide();
                        c.show();
                    }
                }
            })
        }
    });
})


window.emoticon = {
    init: function() {
        this.attachEvents();
        this.gif.init();
    },

    attachEvents: function() {

    },

    gif: {
        init: function() {
            this.attachEvents();
        },

        attachEvents: function() {
            $(document).on('click', '.gif-selector', function(e) {
                e.preventDefault();
                if($(e.target).hasClass('gif-selector') || $(e.target).hasClass('gif-icon')) {
                    var gifBox = $(this).find('.gif-box');
                    var gifSearchResult = $(this).find('.gif-search-result');
                    gifBox.fadeIn();
                    window.emoticon.gif.loadTenorGIFs('', 10, gifSearchResult);
                }
            });

            $(document).on('keyup', '.gif-search-input', function(e) {
                e.preventDefault();
                var gifSearchResult = $(this).closest('.gif-box').find('.gif-search-result');
                window.emoticon.gif.loadTenorGIFs($(this).val(), 10, gifSearchResult);
            });

            $(document).on('click', '.gif-box-close-button', function(e) {
                e.preventDefault();
                var gifBox = $(this).closest('.gif-box');
                gifBox.fadeOut();
            });

            $(document).on('click', '.gif-box .gif-image', function() {
                var url = $(this).data('url');
                var gifBox = $(this).closest('.gif-box');
                var input = gifBox.find('.gif-input');
                var callback = gifBox.data('callback');
                input.val(url);
                gifBox.fadeOut();
                eval(callback)(this);
            });
        },

        loadTenorGIFs: function (term, limit, container) {
            var url = "https://api.tenor.com/v1/search?tag=" + term + "&key=" + tenorGifApiKey + "&limit=" + limit;
            $.ajax({
                url: url,
                success: function(data) {
                    var results = data.results;
                    container.html('');
                    if(results) {
                        results.forEach(function(result) {
                            var media = result.media[0].gif;
                            var url = media.url;
                            var image = $('<img class="gif-image" id="' + url + '" src="' + url + '" data-url ="' + url + '">');
                            container.append(image);
                        });
                    }
                }
            });
        }
    }
}

window.emoticon.init()