/**
 * Feed Editor scripting
 *
 * **/

if(typeof feed === 'undefined') {
    window.feed = {
        editor: {
            submitting: false,
            hasUpload: false,
            uploadType: '',
            actionCount: 0,
            hasLink: false,
            processedLink: '',
            processingLink: false,

            init: function() {
                $(document).on("submit", "#feed-editor-form", function(e) {
                    e.preventDefault();
                    feed.editor.post_feed($(this));
                    return false;
                });

                $(document).on('click', '#feed-editor-menu-item-image', function() {
                    return file_chooser('#feed-editor-image-input');
                });

                $(document).on('click', '#feed-editor-menu-item-video', function() {
                    return file_chooser('#feed-editor-video-input');
                });

                $(document).on('click', '#feed-tags-suggestion a', function() {
                    feed.editor.addTag($(this));
                    return false;
                });

                $(document).on('click', '#feed-editor-tags-container .user a', function() {
                    feed.editor.removeTag($(this));
                    return false;
                });

                $(document).on('click', '.feed-privacy-toggle', function() {
                    var p = $(this).data('id');
                    var feed = $(this).data('feed');
                    var icon = $(this).data('icon');
                    var link = $("#feed-privacy-icon-" + feed);
                    link.html("<i class='" + icon + "'></i>");
                    link.dropdown("toggle");
                    //$("#feed-privacy-dropdown-" + feed).hide();
                    $.ajax({
                        url: baseUrl + 'feed/update/privacy?id=' + feed + '&privacy=' + p + '&csrf_token=' + requestToken,
                    });
                    return false;
                });

                $(document).on('click', '.feed-feeling-trigger', function(e) {
                    e.preventDefault();
                    var c = $(".feed-editor-feeling-container");
                    if(c.css('display') === 'none') {
                        c.fadeIn();
                        c.find("#dropdown-link").dropdown("toggle");
                        c.find(".feeling-right input[type=text]").focus();
                    } else {
                        c.fadeOut();
                    }
                    return false;
                });

                $(document).on('keyup change focus paste', '#feed-editor-textarea', function() {
                    if(document.getElementById('feed-editor-underlay')) {
                        feed.editor.focus();
                    }
                    feed.editor.processLink(this);
                });

                if(enableEditorEnterSubmit) {
                    $(document).on('keydown', '#feed-editor-textarea', function(event) {
                        if(event.keyCode == 13) {
                            if(!event.shiftKey) {
                                $('#feed-editor-form').submit();
                            }
                        }
                    });
                }

                $(document).on('click', '.feed-editor-voice-recorder .control', function(e) {
                    e.preventDefault();
                    var container = $(this).closest('.feed-editor-voice-recorder');
                    if($(container).hasClass('recording')) {
                        feed.editor.voice.live.recorderStop();
                        feed.editor.voice.live.recorderSave();
                    } else if($(container).hasClass('recorded')) {
                        feed.editor.voice.live.recorderStart(true);
                    } else {
                        if(typeof feed.editor === 'undefined') {
                            feed.editor = {};
                        }
                        feed.editor.voice = {};
                        feed.editor.voice.live = window.live;
                        feed.editor.voice.live.init({
                            autoplay: false,
                            constraint: {audio: true},
                            record: $(container).find('.voice-input'),
                            recorderReadyCallback: function(live) {
                                feed.editor.voice.live.recorderStart(true);
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

                $(document).on('click', '.feed-editor-voice-recorder .play', function(e) {
                    e.preventDefault();
                    feed.editor.voice.live.record.play();
                });

                $(document).on('click', '.feed-editor-voice-recorder .close', function(e) {
                    e.preventDefault();
                    if(typeof feed.editor.voice !== 'undefined') {
                        feed.editor.voice.live.end();
                    }
                });

                $(document).on("keyup", ".feed-search-editor-gif", function() {
                    var indicator = $("#gif-feed-indicator");
                    indicator.addClass("feed-gif-indicator");
                    var limit = 10;
                    var search_term = $(".feed-search-editor-gif").val();
                    var search_url = "https://api.tenor.com/v1/search?tag=" + search_term + "&key=" + tenorGifApiKey + "&limit=" + limit;
                    $.ajax({
                        url: search_url,
                        success: function(data) {
                            var results = data.results;
                            indicator.removeClass("feed-gif-indicator");
                            var container = $(".feed-gif-search-results");
                            container.html("");
                            if(results) {
                                results.forEach(function(result) {
                                    var media = result.media[0].gif;
                                    var url = media.url;
                                    var image = '<img onclick=" return gifFeedProcessing(this)" src="' + url + '" data-url ="' + url + '">';
                                    container.append(image);
                                })
                            } else {

                            }
                        }
                    });
                    return false;
                });

                this.processEditorPrivacyDropdown();

                feed.editor.syncBackground();
                feed.editor.initToggle();
            },

            push: function(type, data) {
                if(type === 'feed-update') {
                    var json = jQuery.parseJSON(data);
                    if(json.count > 0) {
                        if(c.length > 0) {
                            var div = $("<div></div>");
                            ///alert('d')
                            if(json.feeds != '') {
                                div.html(json.feeds).hide();
                                c.prepend(div);

                            }
                            if(document.body.scrollTop > 50) {

                                var a = $("#feed-top-update-alert");
                                a.find('span').html(json.count);
                                a.fadeIn().click(function() {
                                    $('body').click().animate({scrollTop: 0}, 200);
                                    div.fadeIn();
                                    try {
                                        reloadInits();
                                    } catch(e) {
                                        console.log(e);
                                    }
                                    $(this).fadeOut();
                                    return false;
                                });
                            } else {
                                setTimeout(function() {
                                    div.fadeIn();
                                    try {
                                        reloadInits();
                                    } catch(e) {
                                        console.log(e);
                                    }
                                }, 300);
                            }
                        } else {

                        }
                    }
                }
            },

            addOptions: function() {
                $(".poll-options-container").append('<div class="options"><i class="ion-ios-plus-outline"></i> <input type="text" name="val[poll_options][]"/><a href="" onclick=" return feed.editor.remove_poll_option(this)" class="close"><i class="ion-android-close"></i></a></div>');
                return false;
            },

            remove_poll_option: function(t) {
                var c = $(t).parent();
                c.remove();
                return false;
            },

            openPoll: function(th) {
                var c = $(".feed-editor-poll-container");
                var i = $("#feed-poll-enable-input");
                var t = $('#feed-editor-textarea');
                var o = $(th);
                if(c.css('display') === 'none') {
                    c.fadeIn();
                    i.val(1);
                    t.val("").prop("placeholder", o.data('holder'));
                } else {
                    c.fadeOut();
                    i.val(0);
                    t.val("").attr("placeholder", o.data('revert'));
                }
                return false;
            },

            loadFeeling: function(t) {
                t = $(t);
                var type = t.data('type');
                var clone = t.clone();
                clone.find('i').remove();
                var content = clone.html();
                //alert(content);
                var c = $(".feed-editor-feeling-container");
                c.find("#dropdown-link").html(content)
                c.find(".feeling-right input[type=text]").focus().val("");
                $("#feed-editor-feeling-type").val(type);
                c.fadeIn();
                $('.feed-editor-feeling-container input[type=text]').val('').show().focus();
                $("#feed-feeling-selected-suggestion").html('');
                return false;

            },

            listenMediaFeeling: function(t) {
                var i = $(t);
                var type = $("#feed-editor-feeling-type").val();
                if(type === 'watching' || type === 'listening-to') {
                    if(i.val().length > 0) {
                        $.ajax({
                            url: baseUrl + 'feed/search/media?type=' + type + '&term=' + i.val() + '&csrf_token=' + requestToken,
                            success: function(data) {
                                if(data) {
                                    $("#feed-feeling-suggestion").html(data).fadeIn();
                                } else {
                                    $("#feed-feeling-suggestion").hide();
                                }

                                $(document).click(function(e) {
                                    if(!$(e.target).closest($("#feed-feeling-suggestion")).length) $("#feed-feeling-suggestion").hide();
                                });
                            }
                        })
                    }
                }
            },

            insertFeelingMedia: function(t) {
                var l = $(t);
                var c = l.data('content');
                $("#feed-editor-feeling-data").val(c);
                var o = $("<div class='media media-sm'><div class='media-left'><div class='media-object'><img style='width: 30px;height: 20px !important;background: #d3d3d3;border-radius: 3px' src='" + l.data('image') + "'/> </div> </div><div class='media-body'><h6 class='media-heading'>" + l.data('title') + "</h6><a class='close' onclick='return feed.editor.removeFeelingMedia()' href=''><i class='ion-android-close'></i></a> </div></div> ")
                $("#feed-feeling-selected-suggestion").html(o);
                $("#feed-feeling-suggestion").fadeOut();
                $("#feed-editor-feeling-text").val(l.data('title')).hide();
                return false;
            },

            removeFeeling: function(t) {
                var i = $(t);
                if(i.val() != '') return false;
                //$(".feed-editor-feeling-container").fadeOut();
            },

            removeFeelingMedia: function() {
                $("#feed-feeling-selected-suggestion").html('');
                $("#feed-editor-feeling-text").val('').show().focus();
                $("feed-editor-feeling-data").val("");
                return false;
            },

            processLink: function(textarea) {
                var str = $(textarea).val();
                if(str === '') {
                    feed.editor.hasLink = false;
                    feed.editor.processedLink = '';
                    feed.editor.processingLink = false;
                }
                if(feed.editor.hasLink || feed.editor.processingLink) return false;
                var container = $("#feed-editor-link-container");
                var indicator = container.find('.link-indicator');
                var content = container.find('.link-content');
                content.html('');
                var split = str.split(" ");
                if(split.length > 0) {
                    var foundLink = searchTextForLink(str);
                    if(foundLink != '' && foundLink != feed.editor.processedLink) {
                        feed.editor.processingLink = true;
                        container.fadeIn();
                        indicator.fadeIn();
                        $.ajax({
                            url: baseUrl + 'feed/link/get?csrf_token=' + requestToken,
                            type: 'POST',
                            cache: false,
                            data: {link: foundLink},
                            success: function(data) {
                                if(data) {
                                    feed.editor.processingLink = false;
                                    feed.editor.hasLink = true;
                                    feed.editor.processedLink = foundLink;
                                    indicator.hide();
                                    content.html(data);
                                } else {
                                    feed.editor.hasLink = false;
                                    feed.editor.processedLink = '';
                                    feed.editor.processingLink = false;
                                }
                            },
                            error: function() {
                                container.hide();
                                indicator.hide();
                                feed.editor.hasLink = false;
                                feed.editor.processedLink = '';
                                feed.editor.processingLink = false;
                            }
                        })
                    }
                }
            },

            removeLinkDetails: function(all) {
                var container = $("#feed-editor-link-container");
                var content = container.find('.link-content');
                feed.editor.hasLink = false;
                if(all) feed.editor.processedLink = '';
                feed.editor.processingLink = false;
                container.fadeOut();
                content.html('');
                return false;
            },
            processEditorPrivacyDropdown: function() {

                $(document).on('click', "#feed-privacy-dropdown li a", function() {
                    var h = $(this).find('i').clone();

                    var input = $("#feed-editor-privacy");
                    //alert($(this).data('id'))
                    input.val($(this).data('id'));
                    $("#feed-editor-privacy-toggle").html(h);
                    $.ajax({
                        url: baseUrl + 'feed/editor/privacy?v=' + $(this).data('id') + '&csrf_token=' + requestToken,
                    });
                });


            },

            addActionCount: function() {
                this.actionCount = this.actionCount + 1;
            },

            removeActionCount: function() {
                this.actionCount = this.actionCount - 1;
                if(this.actionCount < 0) this.actionCount = 0;
            },

            choose: function(id, type) {

                if(this.hasUpload && this.uploadType != type) return false;
                return file_chooser(id)
            },
            processMedia: function(type) {
                if(type === 'image') {
                    var selector = $("#feed-editor-image-selector");
                    // var imageInput = document.getElementById("feed-editor-image-input");
                    var info = $("#photo-feed-media-selected-info");
                    var imageInput = document.getElementById("feed-editor-image-input");
                    var span = selector.find('span');

                    if(imageInput.files.length > maxPhotosUpload) {
                        alert('Max no of images allowed is ' + maxPhotosUpload);
                        return false;
                    }
                    if(!imageInput.files.length) return this.removeImage();
                    span.html(imageInput.files.length).fadeIn();


                    info.find('.count').html(imageInput.files.length);
                    info.fadeIn();
                    this.hasUpload = true;
                    this.uploadType = 'image';
                } else if(type === 'video') {


                    var selector = $("#feed-editor-video-selector");
                    var span = selector.find('span');
                    var info = $("#video-feed-media-selected-info");
                    var videoInput = document.getElementById("feed-editor-video-input");
                    if(!videoInput.files.length) return this.removeVideo();
                    span.html(videoInput.files.length).fadeIn();

                    info.find('.count').html(videoInput.files.length);
                    info.fadeIn();
                    this.hasUpload = true;
                    this.uploadType = 'video';
                } else if(type === 'file') {

                    var selector = $("#feed-editor-file-selector");
                    selector.addClass('active');
                    var info = $("#file-feed-media-selected-info");
                    var fileInput = document.getElementById("feed-editor-file-input");
                    if(!fileInput.files.length) return this.removeFile();
                    info.find('.count').html(fileInput.files.length);
                    info.fadeIn();
                    this.hasUpload = true;
                    this.uploadType = 'file';
                }
            },
            removeImage: function() {
                $("#feed-editor-image-input").val('');
                $("#feed-editor-image-selector span").html('').hide();
                $("#photo-feed-media-selected-info").fadeOut();
                this.hasUpload = false;
                this.uploadType = '';
                return false;
            },
            removeVideo: function() {
                $("#feed-editor-video-input").val('');
                var info = $("#video-feed-media-selected-info").fadeOut();
                $("#feed-editor-video-selector span").html('').hide();
                this.hasUpload = false;
                this.uploadType = '';
                return false;
            },
            removeFile: function() {
                $("#feed-editor-file-input").val('');
                $("#feed-editor-file-selector").removeClass('active');
                var info = $("#file-feed-media-selected-info").fadeOut();
                this.hasUpload = false;
                this.uploadType = '';
                return false;
            },
            toggleCheckIn: function() {
                var container = $("#feed-editor-check-in-input-container");
                var selector = $("#feed-editor-check-in-input-selector");
                if(container.css('display') === 'none') {
                    container.slideDown();
                    selector.addClass('active');
                    container.find('input').focus();
                } else {
                    container.slideUp();
                    if(container.find('input').val() === '') {
                        selector.removeClass('active')
                    }
                }

                return false;
            },
            removeCheckIn: function() {
                var container = $("#feed-editor-check-in-input-container");
                var selector = $("#feed-editor-check-in-input-selector");
                container.find('input').val('');
                container.slideUp();
                selector.removeClass('active');
                return false;
            },
            toggleGif: function() {
                var container = $("#feed-editor-gif-input-container");
                var selector = $("#feed-editor-gif-input-selector");
                if(container.css('display') === 'none') {
                    container.slideDown();
                    selector.addClass('active');
                    container.find('input').focus();
                } else {
                    container.slideUp();
                    if(container.find('input').val() === '') {
                        selector.removeClass('active')
                    }
                }

                return false;
            },
            removeGif: function() {
                var container = $("#feed-editor-gif-input-container");
                var selector = $("#feed-editor-gif-input-selector");
                container.find('input').val('');
                container.slideUp();
                selector.removeClass('active');
                return false;
            },
            showTags: function() {
                var c = $("#feed-editor-tags-container");
                var selector = $("#feed-editor-tags-input-selector");
                if(c.css('display') === 'none') {
                    c.slideDown();
                    //alert(container.css('display'))
                    selector.addClass('active');
                    c.find('input[type=text]').focus();
                } else {
                    c.slideUp();
                    if(c.find('.user').length < 1) {
                        selector.removeClass('active')
                    }
                }
                return false;
            },
            addTag: function(o) {
                var id = o.data('id');
                var name = o.data('name');
                if($('#feed-editor-tags-container #user-' + id).length > 0) return false;
                var span = $("<span id='user-" + id + "' class='user'>" + name + "<input type='hidden' name='val[tags][]' value='" + id + "'/><a href=''><i class='ion-close'></i></a></span>");

                var input = $("#feed-editor-tags-container .input-field");
                input.before(span);
                input.find('#feed-tags-suggestion').fadeOut();
                input.find('input[type=text]').val('').focus();
            },

            removeTag: function(o) {
                o.parent().remove();
                var input = $("#feed-editor-tags-container .input-field");
                input.find('input[type=text]').focus();
            },
            formatFeedActivity: function() {

            },

            validateEditor: function() {
                if(feed.editor.hasUpload) {
                    jQuery('#background').val('');
                }
                if($("#feed-editor-textarea").val() != '') return false;
                if($("#feed-editor-geocomplete").val() != '') return false;
                if(this.actionCount > 0 || this.hasUpload) return false;
                if($(".feed-editor-feeling-container").css('display') != 'none' && $(".feed-editor-feeling-container input[type=text]").val() != "") return false;
                if($("#feed-editor .voice-input").val() != '') return false;
                if($(".feed-editor-gif-value").val() != '') return false;
                return true;
            },

            post_feed: function(form) {
                if(this.validateEditor()) {
                    this.show_error();
                    return false;
                }
                form.ajaxSubmit({
                    url: baseUrl + 'feed/add',
                    dataType: 'json',
                    type: 'POST',
                    beforeSend: function() {
                        window.feed.editor.toggleIndicator();
                        window.feed.editor.submitting = true;
                        //window.feed.editor.show_error(false);
                    },
                    success: function(data) {
                        window.feed.editor.submitting = false;
                        var json = data;

                        if(json.status == 0) {
                            window.feed.editor.show_error(json.message);
                        } else {
                            window.feed.editor.reset(form);
                            if(document.getElementById('feed-editor-underlay')) window.feed.editor.blur();
                            var feed = $("<div></div>");
                            feed.html(json.feed).hide();
                            $("#feed-lists").prepend(feed);
                            feed.fadeIn('fast');
                            notifySuccess(json.message);
                            try {
                                reloadInits();
                            } catch(e) {
                                console.log(e);
                            }
                            if($('.feed-empty').length > 0) {
                                $('.feed-empty').fadeOut().remove();
                            }
                        }

                        window.feed.editor.toggleIndicator();
                    },
                    uploadProgress: function(event, position, total, percent) {
                        if(!window.feed.editor.hasUpload) return false;
                        var uI = $("#feed-media-upload-indicator");
                        uI.html(percent + '%').fadeIn();
                        if(percent == 100) {
                            uI.fadeOut().html("0%")
                        }
                    },
                    error: function() {
                        window.feed.editor.toggleIndicator();
                        window.feed.editor.submitting = false;
                    }
                });
            },
            show_error: function(message) {
                var o = $("#feed-editor-error");
                if(!message) {
                    message = o.data('error');
                }
                notifyError(message);
            },

            reset: function() {
                $("#feed-editor-textarea").val('').css('height', $("#feed-editor-textarea").data('height'));
                $("#feed-editor-image-input").val('');
                $("#feed-editor-video-input").val('');
                $(".feed-editor-poll-container").css('display', 'none');
                $("#feed-editor-file-input").val('');
                //hide image and video selector span
                $("#feed-editor-image-selector").find('span').hide();
                $("#feed-editor-video-selector").find('span').hide();
                $("#feed-editor-tags-container").hide();
                $("#feed-editor-tags-container .user").each(function() {
                    $(this).remove();
                });
                $("#feed-editor-check-in-input-container").hide().find('input[type=text]').val('');
                $(".feed-editor-footer li").each(function() {
                    $(this).removeClass('active')
                });
                $('.feed-media-selected-info').hide();
                this.actionCount = 0;
                this.hasUpload = false;
                this.uploadType = ''
                this.removeLinkDetails(true);

                //recent feeling input and hide it
                $('.feed-editor-feeling-container').hide();
                $('.feed-editor-feeling-container input[type=text]').val('').show();
                $("#feed-feeling-selected-suggestion").html('');
                $("#feed-feeling-suggestion").fadeOut();
                $("feed-editor-feeling-data").val("");

                //remove poll posting
                $("#feed-poll-enable-input").val(0);
                $(".poll-options-container input[type=text]").val('');
                $(".feed-editor-poll-container").hide();
                var b = $("#feed-editor-poll-toggle");
                $("#feed-editor-textarea").prop('placeholder', b.data('revert'));
                feed.editor.toggleBackground('close', 'default', true);
                $("#feed-editor .voice-input").val('');
                if(typeof feed.editor.voice !== 'undefined') {
                    feed.editor.voice.live.end();
                }
                $("#feed-editor .gif-input").val('');
                feed.editor.removeGif();
            },
            toggleIndicator: function() {
                var obj = $("#post-editor-indicator");
                if(obj.css('display') === 'none') {
                    obj.fadeIn();
                } else {
                    obj.fadeOut();
                }
            },
            toggleBackground: function(action, className, clear) {
                var colors = $('.feed-editor-colors');
                var background = $('.feed-editor-background');
                var textarea = $('#feed-editor-textarea');
                var input = $('.feed-editor-background-input');
                if(action === 'open') {
                    $(colors).css('display', 'block');
                    $(textarea).css('display', 'none');
                    $(background).css('display', 'block');
                } else if(action === 'close') {
                    $(colors).css('display', 'none');
                    $(textarea).css('display', 'block');
                    $(background).css('display', 'none');
                } else {
                    if($(colors).css('display') === 'none') {
                        $(colors).css('display', 'block');
                        $(textarea).css('display', 'none');
                        $(background).css('display', 'block');
                    } else {
                        $(colors).css('display', 'none');
                        $(textarea).css('display', 'block');
                        $(background).css('display', 'none');
                    }
                }
                if(className) {
                    $(background).attr('class', 'feed-editor-background ' + className);
                    $(input).val(className);
                }
                if(clear) {
                    $(textarea).val('');
                    $(background).find('textarea').val(' ');
                }
                return false;
            },

            syncBackground: function() {
                $(document).on('change', '#feed-editor-textarea', function() {
                    $('#feed-editor-textarea-bg').val($(this).val());
                });
                $(document).on('change', '#feed-editor-textarea-bg', function() {
                    $('#feed-editor-textarea').val($(this).val());
                });
            },

            focus: function() {
                if(!document.getElementById('feed-editor')) {
                    return false;
                }
                document.querySelector('body').style.overflow = 'hidden';
                var underlay = document.getElementById('feed-editor-underlay');
                var container = document.getElementById('feed-editor-container');
                var editor = document.getElementById('feed-editor');
                var placeholder = document.getElementById('feed-editor-placeholder');
                var bodyRect = document.body.getBoundingClientRect();
                var feedEditorRect = document.getElementById('feed-editor').getBoundingClientRect();
                var isLTR = $('body').css('direction') !== 'rtl';
                var left = isLTR ? feedEditorRect.left - bodyRect.left : feedEditorRect.left - bodyRect.left/* - ((61 / 100) * $(window).width())*/;
                var width = editor.offsetWidth;
                if(!editor.classList.contains('active')) {
                    container.classList.add('active');
                    var documentHeight = Math.max(document.body.scrollHeight, document.body.offsetHeight, document.documentElement.clientHeight, document.documentElement.scrollHeight, document.documentElement.offsetHeight);
                    container.style.height = documentHeight + 'px';
                    //$('html, body').animate({scrollTop: $('#feed-editor').offset().top - 60}, 0);
                    $('html, body').animate({scrollTop: 0}, 0);
                    editor.classList.add('active');
                    editor.style.left = left + 'px';
                    var mainContentRect = document.getElementsByClassName('main-content')[0].getBoundingClientRect();
                    var top = mainContentRect.top;
                    editor.style.top = top + 'px';
                    editor.style.width = width + 'px';
                    underlay.classList.add('active');
                    placeholder.classList.add('active');
                    window.scrollTo(0, underlay.scrollTop);
                }
                placeholder.style.height = (editor.offsetHeight + 16) + 'px';
            },

            blur: function() {
                if(!document.getElementById('feed-editor')) {
                    return false;
                }
                if(feed.editor.submitting) {
                    return;
                }
                document.querySelector('body').style.overflow = 'auto';
                var underlay = document.getElementById('feed-editor-underlay');
                var container = document.getElementById('feed-editor-container');
                var editor = document.getElementById('feed-editor');
                var placeholder = document.getElementById('feed-editor-placeholder');
                if(editor.classList.contains('active')) {
                    container.classList.remove('active');
                    container.style.height = null;
                    editor.style.top = null;
                    editor.style.left = null;
                    editor.style.width = null;
                    underlay.classList.remove('active');
                    editor.classList.remove('active');
                    placeholder.classList.remove('active');
                }
                placeholder.style.height = '0px';
                feed.editor.reset();
            },

            initToggle: function() {
                $('#feed-editor-underlay').scroll(function(e) {
                    if($(this).hasClass('active')) {
                        window.scroll({top: this.scrollTop});
                    }
                });
                $(document).keyup(function(e) {
                    if(e.keyCode == 27) {
                        feed.editor.blur();
                    }
                });
                $(document).on('click', '#feed-editor-minimize', function(e) {
                    var editor = $(this).closest('#feed-editor');
                    if($(editor).hasClass('active')) {
                        feed.editor.blur();
                    }
                });
                $(document).on('click', '#feed-editor', function(e) {
                    if(e.target !== document.getElementById('feed-editor-minimize') && e.target !== document.getElementById('feed-editor-minimize-button')) {
                        feed.editor.focus();
                    }
                });
                $(document).on('click', '#feed-editor-underlay', function(e) {
                    var editor = $(this).find('#feed-editor');
                    if(e.target === this || e.target === document.getElementById('feed-editor-container')) {
                        if(editor.hasClass('active')) {
                            feed.editor.blur();
                        }
                    }
                });
            }
        }
    };
}

function delete_feed(id) {
    var c = $("#feed-wrapper-" + id);
    confirm.action(function() {
        c.css('opacity', '0.5');
        $.ajax({
            url: baseUrl + 'feed/delete?id=' + id + '&csrf_token=' + requestToken,
            success: function(r) {
                if(r == 1) {
                    c.slideUp('slow');
                    c.remove();
                    //alert('na me')
                } else {
                    c.css('opacity', 1);
                }
            },
            error: function() {
                c.css('opacity', 1);
            }
        })
    });
    return false;
}

function pin_feed(t) {
    var o = $(t);
    $.ajax({
        url: o.attr('href') + '?csrf_token=' + requestToken,
        success: function(data) {
            window.location = window.location;
        }
    });

    return false;
}

function show_feed_edit_form(id) {
    var c = $("#feed-edit-form-" + id);
    if(c.css('display') === 'none') {
        c.fadeIn(500).find('textarea').focus();
    } else {
        c.slideUp();
    }
    return false;
}

function save_feed(id) {
    var form = $("#feed-edit-form-" + id);
    var indicator = form.find('.feed-edit-form-indicator');
    form.ajaxSubmit({
        url: baseUrl + 'feed/save?id=' + id,
        type: 'POST',
        beforeSend: function() {
            indicator.fadeIn();
            form.css('opacity', '0.5');
        },
        success: function(data) {
            if(data === '0') {

            } else {
                $("#feed-content-" + id).find('.content').html(data);
                form.slideUp();
            }
            indicator.fadeOut();
            form.css('opacity', 1);
        },
        error: function() {
            indicator.fadeOut();
            form.css('opacity', 1);
        }
    })
    return false;
}

window.feed_paginating = false;

if(typeof sortFeed === 'undefined') {
    window.feedSort = {
        sort: '',
        order: ''
    }
}

$(document).on('click', '.sort-feed-menu .menu-item > a', function() {
    feedSort.sort = $(this).data('type');
});

$(document).on('click', '.sort-feed-menu .order .dropdown-item', function() {
    feedSort.order = $(this).data('type');
});

function paginate_feed() {
    if(window.feed_paginating) return false;
    window.feed_paginating = true;
    var c = $("#feed-lists");
    var limit = c.data('limit');
    var offset = c.data('offset');
    var type = c.data('type');
    var typeId = c.data('type-id');

    toggle_feed_paginate_indicator();
    $.ajax({
        url: baseUrl + 'feed/more?csrf_token=' + requestToken,
        dataType: 'html',
        type: 'GET',
        data: {
            offset: offset,
            type: type,
            type_id: typeId,
            sortby: feedSort.sort,
            orderby: feedSort.order
        },
        success: function(data) {
            window.feed_paginating = false;
            try {
                var json = JSON.parse(data);
            } catch(e) {
                var json = {'feeds': ''};
            }
            if(json.feeds.trim() == '') {
                $('.feed-load-more').fadeOut();
            } else {
                var div = $("<div style='display: none'></div>");
                div.html(json.feeds);
                c.append(div).data('offset', json.offset);
                setTimeout(function() {
                    div.fadeIn(300);
                    try {
                        reloadInits();
                    } catch(e) {
                        console.log(e);
                    }
                    toggle_feed_paginate_indicator();
                }, 500)
            }
        },
        error: function() {
            window.feed_paginating = false;
            toggle_feed_paginate_indicator();
        }
    })

}

function toggle_feed_paginate_indicator() {
    var m = $('.feed-load-more img');
    if(m.css('display') === 'none') {
        m.css('display', 'block').fadeIn();
    } else {
        m.hide();
    }
    //alert()
    //$(document.body).trigger("sticky_kit:recalc");
}

function share_feed(id, m) {
    confirm.action(function() {
        $.ajax({
            url: baseUrl + 'feed/share?id=' + id + '&csrf_token=' + requestToken,
            success: function(data) {
                json = jQuery.parseJSON(data);
                if(json.count != '') $("#feed-share-count-" + id).html(json.count);
                notifySuccess(json.message)
            },
            error: function() {

            }
        })
    }, m);

    return false;
}

function toggle_feed_notification(id) {
    var o = $("#feed-notifications-" + id);
    var onT = o.data('on');
    var offT = o.data('off');
    var turned = o.attr('data-turned');

    if(turned == 1) {
        o.attr('data-turned', '0').html(onT);
        w = 0;
    } else {
        o.attr('data-turned', '1').html(offT);
        w = 1;
    }
    $.ajax({
        url: baseUrl + 'feed/notification?type=' + w + '&id=' + id + '&csrf_token=' + requestToken,
    });

    return false;
}

function hide_feed(id) {
    var c = $("#feed-hide-container-" + id);
    var w = $("#feed-wrapper-" + id);
    $.ajax({
        url: baseUrl + 'feed/hide?id=' + id + '&csrf_token=' + requestToken,
        success: function() {
            w.fadeOut();
            c.fadeIn();
        },
        error: function() {
            notifyError(c.data('error'));
        }
    });
    return false;
}

function unhide_feed(id) {
    var c = $("#feed-hide-container-" + id);
    var w = $("#feed-wrapper-" + id);
    $.ajax({
        url: baseUrl + 'feed/unhide?id=' + id + '&csrf_token=' + requestToken,
        success: function() {
            w.fadeIn();
            c.fadeOut();
        },
        error: function() {
            notifyError(c.data('error'));
        }
    });
    return false;
}

function init_feed_realtime_update() {
    if(feedUpdate && loggedIn) {
        var c = $('#feed-lists');
        var type = 'feed';
        var typeId = '';
        var container = 0;
        if(c.length) {
            type = c.data('type');
            typeId = c.data('type-id');
            container = 1;

            $.ajax({
                url: baseUrl + 'check/new/feeds?csrf_token=' + requestToken,
                type: 'POST',
                data: {type: type, typeId: typeId, container: container},
                success: function(data) {
                    var json = jQuery.parseJSON(data);
                    if(json.count > 0) {
                        if(c.length > 0) {
                            var div = $("<div></div>");
                            ///alert('d')
                            if(json.feeds != '') {
                                div.html(json.feeds).hide();
                                c.prepend(div);

                            }
                            if(document.body.scrollTop > 50) {

                                var a = $("#feed-top-update-alert");
                                a.find('span').html(json.count);
                                a.fadeIn().click(function() {
                                    $('body').click().animate({scrollTop: 0}, 200);
                                    div.fadeIn();
                                    try {
                                        reloadInits();
                                    } catch(e) {
                                        console.log(e);
                                    }
                                    $(this).fadeOut();
                                    return false;
                                });
                            } else {
                                setTimeout(function() {
                                    div.fadeIn();
                                    try {
                                        reloadInits();
                                    } catch(e) {
                                        console.log(e);
                                    }
                                }, 300);
                            }
                        } else {

                        }

                    }


                },
                error: function() {

                }
            })
        }
    }

}


function show_poll_submit_button(id) {
    var c = $("#feed-poll-" + id);
    c.find('.poll-button').fadeIn();
}

function hide_poll_submit_button(id) {
    var c = $("#feed-poll-" + id);
    c.find('.poll-button').fadeOut();
    c.find("input[type=radio]").prop('checked', "");
}

function submit_feed_poll(f) {
    var f = $("#poll-form-" + f);
    f.css('opacity', '0.5');
    //alert("working")
    f.ajaxSubmit({
        url: baseUrl + 'feed/submit/poll',
        success: function(data) {
            $("#feed-poll-" + f.data('id')).html(data);
        }
    })
    return false;
}

/*---geo Complete for pop up editor--*/


function geoCompleteInit() {
    try {
        $("#feed-editor-geocomplete").geocomplete().bind("geocode:result", function(event, result) {
        });
    } catch(e) {
    }
}

$(function() {
    feed.editor.init();
    setInterval(function() {
        init_feed_realtime_update();
    }, feedUpdateInterval);

    /**if (document.body.scrollHeight===document.body.scrollTop + window.innerHeight ) {
        if ($('#feed-lists').length) {
            paginate_feed();
        };
    }**/

    /**if ($(this).scrollTop() > $(document).height() - $(this).height() - 800) {
        //alert('this ')
        paginate_feed();
    }**/

    $(window).scroll(function() {
        if($('.feed-load-more').length > 0) {
            var buttonY = $('.feed-load-more').offset().top;
            var buttonHeight = $('.feed-load-more').outerHeight();
            var windowHeight = $(window).height();
            var windowY = $(this).scrollTop();
            if(windowY > (buttonY + buttonHeight - windowHeight)) {
                paginate_feed();
            }
        }
    });

    $(document).on('click', '.feed-load-more', function() {
        if($('#feed-lists').length) {
            paginate_feed();
        }
        ;
        return false;
    });


    geoCompleteInit();

});

addPageHook("geoCompleteInit");
addPageHook("feed.editor.syncBackground");
addPageHook("feed.editor.initToggle");

function show_voters(t, answer_id, page) {
    page = page || 1;
    var modal = $('#photoViewer');
    modal.modal('hide');
    var m = $("#likesModal");
    var o = $(t);
    var title = m.find('.modal-title');
    title.html(o.data('otitle'));
    m.modal("show");
    var indicator = m.find('.indicator');
    indicator.fadeIn();
    var lists = m.find('.user-lists');
    lists.html('');
    $.ajax({
        url: baseUrl + 'feed/load/voters?answer_id=' + answer_id + '&page=' + page + '&csrf_token=' + requestToken,
        success: function(data) {
            indicator.hide();
            lists.html(data);

        }
    });
    return false;
}

function paginate_voters(answer_id, page) {
    var modal = $('#photoViewer');
    modal.modal('hide');
    var m = $("#likesModal");
    m.modal("show");
    var indicator = m.find('.indicator');
    indicator.fadeIn();
    var lists = m.find('.user-lists');
    lists.html('');
    $.ajax({
        url: baseUrl + 'feed/load/voters?answer_id=' + answer_id + '&page=' + page + '&csrf_token=' + requestToken,
        success: function(data) {
            indicator.hide();
            lists.html(data);

        }
    });
    return false;
}

function gifFeedProcessing(e) {
    var url = $(e).data("url");
    $(".feed-editor-gif-value").val(url);
    $(".feed-gif-search-results").hide();
    var container = $(".feed-gif-search-results");
    container.html("");
    container.show();
    var image = '<img src="' + url + '">';
    container.append(image);
    return false;
}