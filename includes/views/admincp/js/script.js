function refresh_email_template() {
    var temp = $("#email-template-select");
    var lang = $("#email-language-select");
    window.location = temp.data('url') + '?id=' + temp.val() + '&lang=' + lang.val();
}

function reload_statistics(t) {
    var s = $(t);
    var year = s.val();
    var link = s.data('link') + '?year=' + year;
    window.location = link;
}

function open_mailing_selector(t) {
    var o = $(t);
    $('.mail-to-selectors').hide();
    if(o.val() == 'selected') {
        $("#mail-selected-members").slideDown();
    } else if(o.val() == 'non-active') {
        $("#mail-non-active-members").slideDown();
    }
}

function suggest_mail_users(t) {
    var i = $(t);
    $.ajax({
        url: baseUrl + 'user/tag/suggestion?term=' + i.val() + '&csrf_token=' + requestToken,
        success: function(data) {
            $("#mail-selected-members .user-suggestion").html(data).fadeIn();
            $(document).click(function(e) {
                if(!$(e.target).closest($("#mail-selected-members .user-suggestion")).length) $("#mail-selected-members .user-suggestion").hide();
            });
        }
    })
}

function show_other_languages(id) {
    var o = $(id);
    if(o.css('display') == 'none') {
        o.show();
    } else {
        o.hide();
    }
    return false;
}

function show_more_content(id, e) {
    var o = $(id);
    if(o.css('display') == 'none') {
        e.innerHTML = 'Show Less'
        o.show();
    } else {
        e.innerHTML = 'Show more'
        o.hide();
    }
    return false;
}

function initRichEditor() {
    tinymce.remove();
    tinymce.init({
        selector: '.ckeditor',
        height: 150,
        menubar: false,
        plugins: [
            'advlist autolink lists link image charmap print preview anchor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table contextmenu paste code textcolor colorpicker spellchecker imgupload'
        ],
        toolbar: 'styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist table forecolor code | link image imgupload',
        relative_urls: false,
        convert_urls: false,
        document_base_url: baseUrl,
        valid_elements: '*[*]'
    });
}


$(function() {
    $(document).on('click', '#mail-selected-members .user-suggestion a', function() {
        var c = '<span><input type="hidden" value="' + $(this).data('id') + '" name="val[selected][]"/> ' + $(this).data('name') + '<a href=""><i class="ion-close"></i></a> </span>';
        $(c).insertBefore('#mail-selected-members input[type=text]');
        $('#mail-selected-members input[type=text]').val('');
        $("#mail-selected-members .user-suggestion").hide();
        return false;
    });

    $(document).on('click', '#mail-selected-members div span a', function() {
        $(this).parent().remove();
        return false;
    });

    $(window).resize(function() {
        if($('body').width() > 700 && $('#side-navigation').css('display') === 'none') {
            $('#side-navigation').show();
            $('body').removeClass('menu-in');
        }
        if($('body').hasClass('menu-in')) {
            $('#side-navigation').show();
            $('body').removeClass('menu-in');
        }
    });

    $(document).click(function(e) {
        if($('body').width() <= 700 && $('#side-navigation').css('display') !== 'none' && !$(e.target).closest('#side-navigation').length) {
            $('#side-navigation').hide();
            $('body').removeClass('menu-in');
        }
    });

    $(document).on('click', '.menu-toggle', function() {
        if($('#side-navigation').css('display') == 'none') {
            $('#side-navigation').show();
            $('body').addClass('menu-in');
        } else {
            $('#side-navigation').hide();
            $('body').removeClass('menu-in');
        }
        return false;
    });

    if($('#charts-stats').length > 0) {
        var year = $("#admincp-statistics-input").data('year');
        $.ajax({
            url: baseUrl + 'admincp/load/statistics?type=chart&year=' + year + '&csrf_token=' + requestToken,
            success: function(data) {
                var json = jQuery.parseJSON(data);
                //$('#server-stats').html(json.server);

                $.each(json.charts, function(i, c) {
                    var yD = [];
                    xKey = 'y';
                    yKeys = [];
                    labels = [];
                    //alert(c);

                    $.each(c, function(n, nC) {
                        labels.push(nC.name);
                        yKeys.push(n);
                        var x = 0;
                        $.each(nC.points, function($name, $number) {
                            var o = (yD[x] != undefined) ? yD[x] : {y: $name};
                            if(yD[x] != undefined) {
                                yD[x][n] = $number;
                            } else {
                                o[n] = $number;
                                yD.push(o);
                            }
                            x++;
                        });

                    });
                    //alert(yD);

                    var divId = 'chat-' + i;
                    var div = $("<div id='" + divId + "' style='width: 100%;height: 300px'></div> ");
                    $("#charts-stats ").find('img').hide();
                    $("#charts-stats").append(div);

                    Morris.Area({
                        element: divId,
                        data: yD,
                        xkey: xKey,
                        ykeys: yKeys,
                        labels: labels,
                        parseTime: false
                    });

                });
            }
        })
    }

    if($('#server-stats').length > 0) {
        $.ajax({
            url: baseUrl + 'admincp/load/statistics?type=server?csrf_token=' + requestToken,
            success: function(data) {
                var json = jQuery.parseJSON(data);
                $('#server-stats').html(json.server);
            }
        })
    }

    $(document).on('focus', '.color-picker', function() {
        $(this).ColorPicker({
            onSubmit: function(hsb, hex, rgb, el) {
                jQuery(el).val('#' + hex);
                $('#' + $(el).prop('id') + '-color').css('background-color', '#' + hex);
                jQuery(el).ColorPickerHide();
            },
            onBeforeShow: function() {
                jQuery(this).ColorPickerSetColor(this.value);
            }
        });
    });

    tinymce.init({
        selector: '.ckeditor',
        height: 250,
        menubar: false,
        plugins: [
            'advlist autolink lists link image charmap print preview anchor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table contextmenu paste code textcolor colorpicker spellchecker imgupload'
        ],
        toolbar: 'styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist table forecolor code | link image imgupload',
        relative_urls: false,
        convert_urls: false,
        document_base_url: baseUrl,
        valid_elements: '*[*]'
    });

    tinymce.init({
        selector: '#ckeditor',
        height: 250,
        menubar: false,
        plugins: [
            'advlist autolink lists link image charmap print preview anchor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table contextmenu paste code textcolor colorpicker spellchecker imgupload'
        ],
        toolbar: 'styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist table forecolor code | link image imgupload',
        relative_urls: false,
        convert_urls: false,
        document_base_url: baseUrl,
        valid_elements: '*[*]'
    });

    $("#side-navigation-menu").perfectScrollbar({
        suppressScrollX: true,
        maxScrollbarLength: '150'
    });

    $('#side-navigation-menu ul.dropdown').on('shown.bs.collapse', function(e) {
        $("#side-navigation-menu").scrollTop($("#side-navigation-menu").scrollTop() + 1);
        $("#side-navigation-menu").scrollTop($("#side-navigation-menu").scrollTop() - 1);
    });

    if($(".admin-toast-message").length) {
        var message = $(".admin-toast-message").html();

        ///Materialize.toast(message, 5000);
    }

    $(document).on('click', '.admin-confirm', function() {
        var message = $(this).data('message');

        var url = $(this).attr('href');
        if(message != undefined) {
            $("#admin-confirm-modal").find('.modal-body').html(message);
        }

        $("#admin-confirm-modal").modal('show');

        $("#admin-confirm-modal").find('.admin-confirmed').unbind().click(function() {
            window.location = url;
        });

        return false;
    });

    $(document).on('change', '#site-logo-input', function() {
        var file = document.getElementById("site-logo-input");
        if(file.files && file.files.length) {
            if(typeof FileReader != 'undefined') {
                for(i = 0; i < file.files.length; i++) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#site-logo-display').attr("src", e.target.result);
                    }
                    reader.readAsDataURL(file.files[i]);
                }
            }
        }
    });
    var pageBlocks = $('#page-blocks').html();

    function refreshBlocksList() {
        $('#page-blocks').html(pageBlocks);
        dragPageBlocks();
    }

    function dragPageBlocks() {
        $('#page-blocks div').draggable({

            containment: 'window',
            cursor: 'move',
            revert: 'invalid',
            iframeFix: true,
            appendTo: 'body',
            scroll: false,
            zIndex: 9999,
            helper: 'clone'
        });
    }

    function sortablePageBlocks() {
        $('#page-blocks-droppable').sortable({
            items: '.each-block',
            containment: 'window',
            appendTo: 'body',
            helper: 'clone',
            update: function(e, ui) {
                var data = $('#page-blocks-droppable').sortable('toArray');
                var page = $('#page-blocks-droppable').data('page');
                $.ajax({
                    url: baseUrl + 'admincp/block/sort?csrf_token=' + requestToken,
                    type: 'POST',
                    data: {page: page, data: data}
                })
            }
        });
        //$('#page-blocks-droppable .each-block').disableSelection();
    }

    dragPageBlocks();
    sortablePageBlocks();

    $("#page-blocks-droppable").droppable({
        accept: '#page-blocks div',
        drop: function(event, ui) {
            var o = ui.draggable;
            var timestamp = $.now();
            var view = o.data('view');
            var page = o.data('page');
            var pageId = o.data('page-id');
            var settings = o.data('settings');
            var block = $('<div data-page="' + page + '" data-view="' + view + '" id="' + timestamp + '-block" class="each-block"></div>');
            var action = $('<div class="action"></div>');

            block.append(o.data('title'));//append the block title
            if(o.find('form').length) {
                o.find('form').attr('id', "" + timestamp + "-form").attr('data-id', timestamp);
                block.append(o.find('form')); //append form content too
                action.append('<a data-id="' + timestamp + '-block" class="edit-button" href=""><i class="ion-edit"></i></a> | ');
            }
            action.append('<a data-id="' + timestamp + '" class="delete-button" href=""><i class="ion-close"></i></a>');
            block.append(action);
            $('#page-blocks-droppable').append(block)
            refreshBlocksList();

            //NOW SEND TO SERVER TO SAVE THE BLOCK
            $.ajax({
                url: baseUrl + 'admincp/block/register?csrf_token=' + requestToken,
                type: 'POST',
                data: {page: page, page_id: pageId, id: timestamp, view: view, settings: settings},
                success: function(r) {
                    var data = $('#page-blocks-droppable').sortable('toArray');
                    var page = $('#page-blocks-droppable').data('page');
                    $.ajax({
                        url: baseUrl + 'admincp/block/sort?csrf_token=' + requestToken,
                        type: 'POST',
                        data: {page: page, data: data}
                    })
                }
            });


        }
    });

    $(document).on('click', '#page-blocks-droppable .edit-button', function() {
        var block = $('#' + $(this).data('id'));
        var form = block.find('form');
        if(form.css('display') == 'none') {
            $('#page-blocks-droppable form').slideUp(); //let hide other forms opened
            form.slideDown();
        } else {
            form.slideUp();
        }
        return false;
    });

    $(document).on('click', '#page-blocks-droppable .delete-button', function() {
        var block = $('#' + $(this).data('id') + '-block');
        block.fadeOut(1000).remove();
        $.ajax({
            url: baseUrl + 'admincp/block/remove?csrf_token=' + requestToken,
            type: 'POST',
            data: {id: $(this).data('id')},
            success: function(r) {
                //Materialize.toast("Successfully added", 3000)
            }
        })
        return false;
    });

    $(document).on('submit', '#page-blocks-droppable .each-block form', function() {
        var obj = $("#" + $(this).data('id') + '-block');
        $(this).slideUp(); //for fast effect change
        $(this).ajaxSubmit({
            url: baseUrl + 'admincp/block/save',
            type: 'POST',
            data: {page: obj.data('page'), id: $(this).data('id')}
        });
        return false;
    });


    /**
     * Custom field
     */
    $(document).on('change', '#custom-field-type-selection', function() {
        var v = $(this).val();
        if(v == 'selection') {
            $('#custom-field-selection-data').fadeIn().focus();//show the selection area
        } else {
            $('#custom-field-selection-data').fadeOut();//hide the selection area
        }
    });

    $('.custom-field-list').each(function() {
        var obj = $(this);
        $(this).sortable({
            items: '.item',
            update: function(e, ui) {
                var data = obj.sortable('toArray');
                var category = obj.data('category');
                $.ajax({
                    url: baseUrl + 'admincp/custom-fields?action=order&csrf_token=' + requestToken,
                    type: 'POST',
                    data: {category: category, data: data}
                })
            }
        });
    });

    $("#custom-field-categories").sortable({
        items: '.row',
        update: function(e, ui) {

        }
    });

    $(".admincp-sortable").each(function() {
        var obj = $(this);
        var url = obj.data('url');
        var extra = obj.data('extra');
        $(this).sortable({
            items: '.item',
            forceHelperSize: true,
            forcePlaceholderSize: true,
            update: function(e, ui) {
                var data = obj.sortable('toArray');
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {extra: extra, data: data, csrf_token: requestToken}
                })
            }
        })
    })

});

function notify(m, t, time) {
    var c = $('#site-wide-notification');
    var cM = c.find('.message');
    var time = (time == undefined) ? 8000 : time;
    c.fadeOut();
    c.removeClass('error').removeClass('success').removeClass('info').removeClass('warning').addClass(t);
    cM.html(m);
    c.fadeIn('slow');
    setTimeout(function() {
        c.fadeOut('slow');
    }, time);
}

function notifyError(m, time) {
    notify(m, 'error', time);
}

function notifySuccess(m, time) {
    notify(m, 'success', time);
}

function notifyInfo(m, time) {
    notify(m, 'info', time);
}

function notifyWarning(m, time) {
    notify(m, 'warning', time);
}

function closeNotify() {
    $('#site-wide-notification').fadeOut();
    return false;
}

var Pusher = {
    hooks: [],
    alert: false,
    pushIds: [],
    titleCount: 0,
    pageTitle: '',
    userid: '',
    onAlert: function() {
        this.alert = true;
    },
    offAlert: function() {
        this.alert = false;
    },

    finish: function() {
        //final steps to take like sound alert if on
        if(this.alert) {
            var audio = document.getElementById('update-sound');
            audio.load();
            audio.play();
            //document.getElementById('update-sound').play();
        }
        this.alert = false;
        this.refreshPageTitle();
    },

    setPageTitle: function(t) {
        this.pageTitle = t;
        this.refreshPageTitle();
    },

    refreshPageTitle: function() {
        if(this.titleCount > 0) {
            pageTitle = this.pageTitle;
            pageTitle = '(' + this.titleCount + ') ' + pageTitle;
            document.title = pageTitle;
            this.titleCount = 0;
        } else {
            document.title = this.pageTitle;
        }
    },

    setUser: function(userid) {
        this.userid = userid;
    },
    getUser: function() {
        return this.userid;
    },
    addCount: function(c) {
        this.titleCount = parseInt(this.titleCount) + parseInt(c);
    },

    removeCount: function(c) {
        this.titleCount -= c;
        this.refreshPageTitle();
    },

    addHook: function(hook) {
        this.hooks.push(hook);
    },

    run: function(type, d) {
        for(i = 0; i <= this.hooks.length - 1; i++) {
            f = this.hooks[i];
            r = null;
            eval(this.hooks[i])(type, d);

        }
    },

    addPushId: function(id) {
        this.pushIds.push(id);
    },
    hasPushId: function(id) {
        if(jQuery.inArray(id, this.pushIds) != -1) return true;
        return false;
    }
}

$(function() {
    $('.list-table-select').each(function() {
        $(this).on('click', function() {
            var group = $(this).data('group');
            if($(this).hasClass('selected')) {
                $(this).removeClass('selected');
                $('.list-table-select[data-group="' + group + '"]').each(function() {
                    if($(this).data('id') === 0) {
                        $(this).removeClass('selected');
                    }
                    if($('.list-table-select.selected[data-group="' + group + '"]').length === 0) {
                        $('.list-table-select-actions[data-group="' + group + '"]').fadeOut('fast');
                    }
                });
            } else {
                $(this).addClass('selected');
                $('.list-table-select-actions[data-group="' + group + '"]').fadeIn('fast');
                var all = $('.list-table-select[data-group="' + group + '"]').length;
                var selected = $('.list-table-select.selected[data-group="' + group + '"]').length;
                if(all - 1 === selected) {
                    $('.list-table-select[data-group="' + group + '"]').each(function() {
                        if($(this).data('id') === 0) {
                            $(this).addClass('selected');
                            $('.list-table-select-actions[data-group="' + group + '"]').fadeIn('fast');
                        }
                    });
                }
            }
            if($(this).data('id') === 0) {
                if($(this).hasClass('selected')) {
                    $('.list-table-select[data-group="' + group + '"]').not('.selected').each(function() {
                        $(this).addClass('selected');
                        $('.list-table-select-actions[data-group="' + group + '"]').fadeIn('fast');
                    });
                } else {
                    $('.list-table-select.selected[data-group="' + group + '"]').each(function() {
                        $(this).removeClass('selected');
                        if($('.list-table-select.selected[data-group="' + group + '"]').length === 0) {
                            $('.list-table-select-actions[data-group="' + group + '"]').fadeOut('fast');
                        }
                    });
                }
            }
        });
    });

    $('.list-table-select-action').on('click', function() {
        var url = $(this).attr('href');
        var group = $(this).data('group');
        var ids = [];
        $('.list-table-select.selected[data-group="' + group + '"]').each(function() {
            if($(this).data('id') !== 0) {
                ids.push($(this).data('id'));
            }
        });
        var query = 'ids=' + ids.join(',');
        url += /\?/.test(url) ? '&' + query : '&' + query;
        window.open(url, '_self');
        return false;
    });

    var headerSearching = false;
    $(document).on('keyup', '.header-block-search input[type="search"]', function() {
        var result = $(this).data('result');
        var term = $(this).val();
        term.length >= 3 ? $(result).fadeIn() : $(result).fadeOut();
        if(!headerSearching && term.length >= 3) {
            $.ajax({
                url: baseUrl + 'admincp/search/load?term=' + term + '&csrf_token=' + requestToken,
                beforeSend: function() {
                    headerSearching = true;
                },
                success: function(response) {
                    $(result).html(response);
                    headerSearching = false;
                },
                error: function(error) {
                    headerSearching = false;
                }
            });
        }
    });

    $(document).on('click', '.plugin-activate', function(e) {
        e.preventDefault();
        var modal = $('#plugin-activate-modal');
        var url = $(this).attr('href') + '&ajax=true';
        var container = $(this).data('container') ? $(this).data('container') : $(this).closest('tr');
        $.ajax({
            url: url,
            beforeSend: function() {
                $(container).css({opacity: '0.5', 'pointer-event': 'none'});
            },
            success: function(response) {
                response = JSON.parse(response);
                if(response.status === 1) {
                    $(container).html(response.html);
                } else if(response.status === 2) {
                    $(modal).find('input[name="id"]').val(response.id);
                    $(modal).modal('show');
                }
                $(container).css({opacity: 'unset', 'pointer-event': 'unset'});
            },
            error: function(error) {
                $(container).css({opacity: 'unset', 'pointer-event': 'unset'});
            }
        });
    });

    $(document).on('click', '#plugin-license-activate', function(e) {
        e.preventDefault();
        var modal = $('#plugin-activate-modal');
        var id = $(modal).find('input[name="id"]').val();
        var url = $(this).attr('href') + '&id=' + id + '&ajax=true';
        var container = $('#plugin-' + id);
        $.ajax({
            url: url,
            beforeSend: function() {
                $(container).css({opacity: '0.5', 'pointer-event': 'none'});
                $(modal).find('.modal-dialog').css({opacity: '0.5', 'pointer-event': 'none'});
            },
            success: function(response) {
                response = JSON.parse(response);
                if(response.status === 1) {
                    $(container).html(response.html);
                    $(modal).modal('hide');
                } else if(response.status === 2) {
                    var message = $('<div class="alert text-danger">' + response.message + '</div>');
                    message.css({
                        'text-align': 'center',
                        'position': 'absolute',
                        'left': '-12px',
                        'bottom': '-10px',
                        'width': '100%'
                    });
                    $(container).html(response.html);
                    $(modal).find('.modal-body').prepend(message);
                    setTimeout(function() {
                        message.remove();
                    }, 3000);
                }
                $(container).css({opacity: 'unset', 'pointer-event': 'unset'});
                $(modal).find('.modal-dialog').css({opacity: 'unset', 'pointer-event': 'unset'});
            },
            error: function(error) {
                $(container).css({opacity: 'unset', 'pointer-event': 'unset'});
                $(modal).find('.modal-dialog').css({opacity: 'unset', 'pointer-event': 'unset'});
            }
        });
    });
    $('.switch').on('click', function(e) {
        if(e.target.classList.contains('slider')) {
            var checked = $(this).closest('.switch').find('input:checked');
            var notChecked = $(this).closest('.switch').find('input:not(:checked)');
            checked.attr('checked', false);
            notChecked.attr('checked', true);
            notChecked.click();
        }
    });
    $('.switch input').on('click', function(e) {
        if(e.originalEvent !== undefined) {
            e.preventDefault();
            e.stopPropagation();
        }
    });
});

$(function() {
    if(!sessionTimezone) {
        var time = new Date();
        var offset = -time.getTimezoneOffset() / 60;
        $.ajax({
            type: "POST",
            url: baseUrl + "timezone/set",
            data: {offset: offset},
            success: function() {
                // location.reload();
            }
        });
    }
});

function membership_admin_change_form(s) {
    var s = $(s);
    var v = s.val();
    if (v == 'one-time' || v == 'recurring') {
        $(".plan-price").fadeIn();
        if (v == 'recurring') {
            $(".recurring-container").fadeIn();
        } else {
            $(".recurring-container").fadeOut();
        }
    } else {
        $(".plan-price").fadeOut();
        $(".recurring-container").fadeOut();
    }
}
$(document).ready(function () {
    $('.codemirror-textarea').each(function () {
        var myTextarea = this;
        CodeMirror.fromTextArea(myTextarea, {
            lineNumbers: true
        });
    });
});

$(document).on('click', '#auto-delete-btn', function(event) {
    event.preventDefault();
    var e = document.getElementById("auto-delete-select");
    var deleteValue = e.options[e.selectedIndex].value;
    if (!confirm('Are you sure you want to perform this action? this action can\'t be reversed')) {
        return false;
    }
    $(this).attr('disabled', 'disabled');
    $(this).text('Please wait..');
    $.ajax({
        url: baseUrl + 'admincp/auto/delete?csrf_token=' + requestToken,
        type: 'POST',
        cache: false,
        data: {delete: deleteValue},
        success: function (data) {
            data = JSON.parse(data);
            if (data.status == "1"){
                $('#auto-delete-btn').text('Data is being deleted, check your site after few mins.');
                $('#auto-delete-btn').removeAttr('disabled');
                setTimeout(function () {
                    $('#auto-delete-btn').text('Delete Data');
                }, 4000);
            }
        },
        error: function () {
            $('#auto-delete-btn').text('Oops!!!, Something went wrong. Try Again!!!');
            $('#auto-delete-btn').removeAttr('disabled');
            setTimeout(function () {
                $('#auto-delete-btn').text('Delete Data');
            }, 4000);
        }
    })
});

if(typeof language === 'undefined') {
    language = {
        phrases: {},

        addPhrase: function(id, phrase) {
            language.phrases[id] = phrase;
        },
        addPhrases: function(phrases) {
            for(var i in phrases) {
                this.addPhrase(i, phrases[i]);
            }
        },
        phrase: function(id) {
            return typeof language.phrases[id] === 'undefined' ? id : language.phrases[id];
        }
    }
}
