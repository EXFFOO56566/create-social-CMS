function toogleCommentIndicator(c) {
    var i = c.find('.comment-editor-indicator');
    if(i.css('display') == 'none') {
        i.fadeIn();
    } else {
        i.fadeOut();
    }
}

function delete_comment(id) {
    var c = $(".comment-" + id);
    var b = $("#comment-remove-button-" + id);
    c.css('opacity', '0.5');
    $.ajax({
        url: baseUrl + 'comment/delete?id=' + id + '&csrf_token=' + requestToken,
        type: 'GET',
        success: function(data) {
            if(data == 1) {
                c.fadeOut();
            } else {
                c.css('opacity', 1);
            }
        },
        error: function() {
            c.css('opacity', 1);
        }
    })
    return false;
}

function resent_comment_form(form) {
    form.find('textarea').val('').css('height', '30px');
    form.find('input[type=file]').val('');
    form.find('.comment-editor-footer').fadeOut();
    var voiceRecorder = form.find('.comment-editor-voice-recorder');
    var voiceRecorderId = $(voiceRecorder).data('id');
    if(typeof comment.editor.editors[voiceRecorderId] !== 'undefined') {
        comment.editor.editors[voiceRecorderId].voice.live.end();
    }
    form.find('.gif-input').val('');

}

function show_comment_add_error(form, message) {

    var o = form.find('.alert-warning');
    if(message == 'default') message = o.data('error');
    notifyError(message);
}

function show_more_comment(type, typeId, indicator) {
    var c = $('.comment-lists-' + type + '-' + typeId);
    var indicator = $('#' + indicator);
    indicator.fadeIn();
    var offset = c.data('offset');
    var limit = c.data('limit');
    $.ajax({
        url: baseUrl + 'comment/more?type=' + type + '&type_id=' + typeId + '&offset=' + offset + '&limit=' + limit + '&csrf_token=' + requestToken,
        type: 'GET',
        dataType: 'html',
        success: function(data) {
            json = jQuery.parseJSON(data);
            c.each(function() {
                c.data('offset', json.offset);
            })
            if(json.comments == '') {
                indicator.hide();
                $(".comment-view-more-button-" + type + '-' + typeId).hide();
            } else {
                c.prepend(json.comments);
                c.each(function() {

                })
                indicator.hide();
                reloadInits();
            }
        }
    })
    return false;
}

function edit_comment(id) {
    var c = $(".comment-" + id);
    var form = c.find('.comment-edit-form');
    if(form.css('display') == 'none') {
        form.fadeIn();
    } else {
        form.fadeOut();
    }
    return false;
}

function save_comment(id, gid) {
    var c = $(".comment-" + id);
    var cG = $(".comment-" + gid);
    var form = cG.find('.comment-edit-form');
    var indicator = form.find('.comment-edit-form-indicator');
    form.ajaxSubmit({
        url: baseUrl + 'comment/save?id=' + id,
        type: 'POST',
        beforeSend: function() {
            indicator.fadeIn();
            form.css('opacity', '0.5');
        },
        success: function(r) {
            if(r != '0') {
                c.find('.comment-text-content').html(r);
                form.hide();
            }
            indicator.hide();
            form.css('opacity', 1);
        },
        error: function() {
            indicator.hide();
            form.css('opacity', 1);
        }
    })
    return false;
}

function show_comment_replies(id, gId) {
    var container = $(".comment-replies-" + gId);
    var repliesLink = $(".comment-replies-" + gId + " .load-replies-link");
    if(repliesLink.length > 0) {
        repliesLink.find('img').fadeIn();
    }

    var editor = container.find('.comment-editor');
    editor.fadeIn();
    $.ajax({
        url: baseUrl + 'comment/load/replies?id=' + id + '&csrf_token=' + requestToken,
        success: function(data) {
            container.find('.comment-lists').html(data).css("padding", "10px 0");
            container.find('.comment-view-more-button').show();
            if(repliesLink.length > 0) {
                repliesLink.remove();
            }
            reloadInits();
        }
    })
    return false;
}

$(function() {

    $(document).on('focus', ".comment-editor  textarea", function() {
        $(this).css('height', '50px').data('height', '50px');
        var target = $($(this).data('target'));
        target.find('.comment-editor-footer').fadeIn();
    });

    $(document).on('submit', ".comment-editor", function(e) {
        e.preventDefault();
        comment.editor.submit(this);
    });

    $(document).on('mouseover', '.comment', function() {
        var commentId = $(this).data('id');
        $('.comment-actions-button-' + commentId).each(function() {
            $(this).show();
        })
    });

    $(document).on('mouseout', '.comment', function() {
        var commentId = $(this).data('id');
        $('.comment-actions-button-' + commentId).each(function() {
            $(this).hide();
        })
    });

    $(document).on('change', '.comment-editor-footer .file-input input', function(event) {
        var footer;
        var closest = event.target;
        while(closest.parentNode.classList && !closest.parentNode.classList.contains('comment-editor-footer') && closest.tagName !== 'HTML') {
            closest = closest.parentNode;
        }
        footer = closest !== event.target && closest.parentNode.tagName === 'HTML' ? null : closest.parentNode;
        var chooser = footer.querySelector('.file-chooser');
        if(this.files.length === 0) {
            if(chooser.classList.contains('selected')) {
                chooser.classList.remove('selected');
            }
        } else {
            if(!chooser.classList.contains('selected')) {
                chooser.classList.add('selected');
            }
        }
    });
});
if(typeof window.comment === 'undefined') {
    window.comment = {
        init: function() {
            comment.editor.init();
        },

        editor: {
            editors: [],

            init: function() {
                comment.editor.attachEvents();
            },

            submit: function(form) {
                var form = $(form);
                var text = form.find('textarea');
                var imageInput = form.find('input[type=file]');
                var voiceInput = form.find('.voice-input');
                var gifImageInput = form.find('.gif-input');
                if(text.val() == '' && imageInput.val() == '' && voiceInput.val() == '' && gifImageInput.val() == '') {
                    show_comment_add_error(form, 'default');
                    return false
                }
                var commentList = $(".comment-lists-" + form.data('type') + '-' + form.data('type-id'));
                toogleCommentIndicator(form);

                form.ajaxSubmit({
                    url: baseUrl + 'comment/add',
                    type: 'POST',
                    dataType: 'json',
                    success: function(data) {
                        var json = data;
                        if(json.status == 0) {
                            show_comment_add_error(form, json.message);
                        } else {
                            div = $("<div style='display: none'></div>");
                            div.html(json.comment);
                            //commentList.append(div);
                            $(".comment-lists-" + form.data('type') + '-' + form.data('type-id')).each(function() {
                                $(this).append(json.comment);
                                //alert(".comment-lists-" + form.data('type') + '-' + form.data('type-id'))
                            });
                            $(".comment-count-" + form.data('type') + '-' + form.data('type-id')).each(function() {
                                $(this).html(json.count);
                            })
                            notifySuccess(json.message);

                            resent_comment_form(form);
                            reloadInits();
                        }

                        toogleCommentIndicator(form);
                    },
                    error: function() {
                        toogleCommentIndicator(form);
                    }
                });
                return false;
            },

            onGIFClick: function(gif) {
                var form = $(gif).closest('form').get(0);
                comment.editor.submit(form);
            },

            attachEvents: function() {
                $(document).on('click', '.comment-editor-voice-recorder .control', function(e) {
                    e.preventDefault();
                    var container = $(this).closest('.comment-editor-voice-recorder');
                    var id = $(container).data('id');
                    if($(container).hasClass('recording')) {
                        comment.editor.editors[id].voice.live.recorderStop();
                        comment.editor.editors[id].voice.live.recorderSave();
                    } else if($(container).hasClass('recorded')) {
                        comment.editor.editors[id].voice.live.recorderStart(true);
                    } else {
                        if(typeof comment.editor.editors[id] === 'undefined') {
                            comment.editor.editors[id] = {};
                        }
                        comment.editor.editors[id].voice = {};
                        comment.editor.editors[id].voice.live = window.live;
                        comment.editor.editors[id].voice.live.init({
                            autoplay: false,
                            constraint: {audio: true},
                            record: $(container).find('.voice-input'),
                            recorderReadyCallback: function(live) {
                                comment.editor.editors[id].voice.live.recorderStart(true);
                                $(container).removeClass('recorded');
                                $(container).addClass('recording');
                            },
                            recorderStartCallback: function(live) {
                                $(container).removeClass('recorded');
                                $(container).addClass('recording');
                            },
                            recorderStopCallback: function(live) {
                                $(container).removeClass('recording');
                                $(container).addClass('recorded');
                            },
                            recorderSaveCallback: function(live) {
                                var reader = new FileReader();
                                reader.readAsDataURL(live.blob);
                                reader.onload = function(event) {
                                    $(container).find('.voice-input').val(event.target.result);
                                };
                            },
                            liveEndCallback: function(live) {
                                $(container).removeClass('recorded');
                                $(container).removeClass('recording');
                            },
                        });
                    }
                });

                $(document).on('click', '.comment-editor-voice-recorder .play', function(e) {
                    e.preventDefault();
                    var container = $(this).closest('.comment-editor-voice-recorder');
                    var id = $(container).data('id');
                    comment.editor.editors[id].voice.live.record.play();
                });

                $(document).on('click', '.comment-editor-voice-recorder .close', function(e) {
                    e.preventDefault();
                    var container = $(this).closest('.comment-editor-voice-recorder');
                    var id = $(container).data('id');
                    comment.editor.editors[id].voice.live.end();
                });

                $(document).on("click", "comment-reply-button", function() {
                    var target = $(this).data('id');
                    var wrapper = $("#comment-editor-mention-" + target);
                    wrapper.addClass("mention-user-list");
                    wrapper.find(".gif-comment-wrapper").hide();
                    wrapper.hide();
                    return false;
                });
            }
        }
    }
}

$(function() {
    comment.init();
    addPageHook(comment.init);
});