function site_editor_switch(t, id) {
    var t = $(t);
    var c = $("#" + id);
    $(".each-site-editor-pane").hide();
    c.fadeIn();
    $('#site-editor-link-items li').each(function() {
        $(this).removeClass('active');
    })
    t.parent().addClass('active');
    return false;
}

var siteEditor = {
    url: baseUrl + 'admincp/site/preview',
    page: '',
    previewObj: '',
    previewLoader: '',
    theme: '',
    init: function() {
        this.previewObj = $("#preview-iframe");
        this.previewLoader = $("#preview-loader");
        $(document).on('click', '.edit-page', function(e) {
            var pageSlug = $(this).data('slug');
            if(pageSlug) {
                e.preventDefault();
                siteEditor.editPageInfo(pageSlug);
            }
        });
        $(document).on('click', '#predefined-columns > a', function(e) {
            e.preventDefault();
            siteEditor.changeColumn(this);
            $('#layout-columns').modal('hide');
        });
        $(document).on('click', '.layout-container .col', function(e) {
            $('.layout-container .col.selected').removeClass('selected');
            $(this).addClass('selected');
        });
        $(document).on('keyup', '[data-method="filter"]', function(e) {
            siteEditor.filter($(this).data('selector'), $(this).val());
        });
        $(document).on('click', '#layout-widgets .widget', function(e) {
            e.preventDefault();
            var column = $('.layout-container .col.selected');
            var widget = $('<div class="widget">' + $(this).html() + '</div>');
            widget.find('i.icon').remove();
            var pos = column.data('position');
            var now = $.now();
            var widgetId = $(this).data('widget');
            var settings = $(this).data('setting');
            var actions = $('<div class="actions"></div>');
            widget.append('<input type="hidden" name="' + pos + '[' + now + '][settings]" value="" id="settings-' + now + '" />');
            widget.append('<input type="hidden" name="' + pos + '[' + now + '][widget]" value="' + widgetId + '"/>');
            settings === 1 ? actions.append('<a onclick="return siteEditor.loadWidgetSettings(this, \'' + now + '\', \'' + widgetId + '\')" href="#" ><i class="ion-android-settings"></i></a>') : null;
            actions.append('<a onclick="return siteEditor.deleteWidget(this, \'' + now + '\')" href="#"><i class="ion-android-close"></i></a>');
            widget.append(actions);
            column.append(widget);
            $('#layout-widgets').modal('hide');
        });
        $(document).on('input', '#page-switcher', function () {
            var switcher = $(this);
            var val = switcher.val();
            var filter = val.toLowerCase();
            var matched = $('#pages-list option').filter(function(){return this.value.toUpperCase() === val.toUpperCase();}).length;
            if(matched) {
                var message = 'Are you sure?\nAll unsaved changes will be lost';
                $('#admin-confirm-modal').find('.modal-body').html(message);
                $('#admin-confirm-modal').find('.admin-confirmed').unbind().click(function() {
                    $("#admin-confirm-modal").modal('hide');
                    var match = $('#pages-list option[data-filter="' + filter + '"]');
                    var pageId = match.data('id');
                    siteEditor.loadPage(pageId);
                });
                $("#admin-confirm-modal").modal('show');
            }
        });
        $(document).on('submit', '#page-form', function (e) {
            e.preventDefault();
            var form = $(this);
            var wrapper = $('#page-builder-wrapper');
            wrapper.css('pointer-events', 'none');
            wrapper.css('opacity', .5);
            form.ajaxSubmit({
                url: baseUrl + 'admincp/site/page/ajax?action=save_page',
                success: function(response) {
                    response = JSON.parse(response);
                    if(response.status) {
                        var m = $('#layout-message');
                        m.fadeIn();
                        setTimeout(function() {
                            m.fadeOut();
                        }, 3000);
                        wrapper.html($(response.html).html());
                        siteEditor.reloadPreview();
                        wrapper.css('opacity', 1);
                        wrapper.css('pointer-events', 'initial');
                    }
                },
                error: function() {
                    wrapper.css('opacity', 1);
                    wrapper.css('pointer-events', 'initial');
                }
            });
            return false;
        });
        //this.reloadPreview();
        this.reloadInit();
    },
    reloadPreview: function() {
        this.previewLoader.fadeIn();
        var url = this.url + "?page=" + this.page + "&theme=" + this.theme;
        this.previewObj.attr('src', url);
        this.previewObj.load(function() {
            siteEditor.previewLoader.hide();
        });
    },
    previewTheme: function(theme) {
        this.theme = theme;
        this.reloadPreview();
    },

    saveSettings: function(t) {
        var form = $(t);
        form.css('opacity', '0.6');
        form.ajaxSubmit({
            url: baseUrl + 'admincp/site/page/save/settings',
            success: function() {
                siteEditor.reloadPreview();
                form.css('opacity', 1);
            }
        })
        return false;
    },

    changeColumn: function(t) {
        var o = $(t);
        var name = o.data('name');
        $('.layout-content #columns').removeAttr('class').addClass(name);
        $('.layout-column-type').val(o.data('id'));
        $('#predefined-columns a').removeClass('active');
        o.addClass('active');
        return false;
    },
    loadPage: function(pageId) {
        var wrapper = $('#page-builder-wrapper');
        wrapper.css('pointer-events', 'none');
        wrapper.css('opacity', .5);
        $.ajax({
            url: baseUrl + 'admincp/site/page/ajax?action=load_page&id=' + pageId + '&csrf_token=' + requestToken,
            success: function(response) {
                response = JSON.parse(response);
                if(response.status) {
                    wrapper.replaceWith(response.html);
                    window.history.pushState({'type': 'page-builder', 'id': pageId}, 'Page Builder', baseUrl + 'admincp/site/page/builder?action=edit&id=' + pageId);
                }
                wrapper.css('opacity', 1);
                wrapper.css('pointer-events', 'initial');
                siteEditor.reloadInit();
            },
            error: function(e) {
                console.log(e);
                wrapper.css('opacity', 1);
                wrapper.css('pointer-events', 'initial');
            }
        });
    },
    changePage: function(t) {
        var o = $(t);
        var page = o.val();
        var container = $("#layout-site-editor-pane");
        if(page != 'header' && page != 'footer') {
            this.page = page;
            this.reloadPreview();
        }
        container.css('opacity', '0.6');
        $.ajax({
            url: baseUrl + 'admincp/site/layout/page?page=' + page + '&csrf_token=' + requestToken,
            success: function(data) {
                container.html(data);
                container.css('opacity', 1);
                siteEditor.reloadInit();
            }
        })
    },
    submitLayout: function(form) {
        var form = $(form);
        var wrapper = $('#page-builder-wrapper');
        wrapper.css('pointer-events', 'none');
        wrapper.css('opacity', .5);
        form.ajaxSubmit({
            url: baseUrl + 'admincp/site/page/layout',
            success: function(data) {
                var m = $("#layout-message");
                m.fadeIn();
                setTimeout(function() {
                    m.fadeOut();
                }, 3000);
                siteEditor.reloadPreview();
                wrapper.css('opacity', 1);
                wrapper.css('pointer-events', 'initial');
            },
            error: function() {
                wrapper.css('opacity', 1);
                wrapper.css('pointer-events', 'initial');
            }
        });
        return false;
    },
    deleteWidget: function(t, id) {
        var o = $(t);
        var c = o.closest('.widget');
        c.remove();
        $(".deleted-widgets").append("<input type='hidden' name='val[deleted_widgets][]' value='" + id + "'/>");
        return false;
    },
    loadWidgetSettings: function(t, id, widget) {
        var m = $("#layout-widget-settings");
        var cont = m.find('.setting-content');
        var loader = m.find('#loader');
        var settings = $("#settings-" + id);
        cont.html('');
        loader.fadeIn();
        m.modal('toggle');
        $.ajax({
            url: baseUrl + 'admincp/site/page/widget/settings/load?csrf_token=' + requestToken,
            data: {id: id, widget: widget, settings: settings.val()},
            success: function(c) {
                cont.html(c);
                loader.hide();
                initRichEditor();
            }
        });
        m.find('form').unbind().submit(function() {
            tinyMCE.triggerSave();
            $(this).ajaxSubmit({
                url: baseUrl + 'admincp/site/page/widget/settings/save',
                success: function(data) {
                    settings.val(data);
                    m.modal('toggle');
                }
            });
            return false;
        });

        return false;
    },
    editPageInfo: function(id) {
        var modal = $('#page-info-modal');
        var currentPage = id || $('#layout-page-list').val() ;
        var indicator = modal.find('.info-loader');
        var c = modal.find('.form-content');
        if(currentPage != 'footer' && currentPage != 'header') {
            modal.modal("toggle");
            c.html('');
            indicator.fadeIn();
            $.ajax({
                url: baseUrl + 'admincp/site/page/page/info?id=' + currentPage + '&csrf_token=' + requestToken,
                success: function(data) {
                    c.html(data);
                    indicator.hide();
                    initRichEditor();
                }
            })
        }
        return false;
    },
    savePageInfo: function(f) {
        var form = $(f);
        var indicator = form.find('.save-loader');
        indicator.fadeIn();
        tinyMCE.triggerSave();
        form.ajaxSubmit({
            url: baseUrl + 'admincp/site/page/save/page/info',
            success: function(data) {
                indicator.hide();
                var json = jQuery.parseJSON(data);
                $('#layout-page-list option[value="' + json.old_slug + '"]').val(json.slug)
                $("#layout-page-list").val(json.slug);
                $("#page-info-modal").modal('toggle');
            }
        })
        return false;
    },
    addPage: function(f) {
        var form = $(f);
        var wrapper = $('#page-builder-wrapper');
        wrapper.css('pointer-events', 'none');
        wrapper.css('opacity', .5);
        tinyMCE.triggerSave();
        form.ajaxSubmit({
            url: baseUrl + 'admincp/site/page/ajax?action=add_page&csrf_token=' + requestToken,
            success: function(response) {
                var response = jQuery.parseJSON(response);
                if(response.status) {
                    $('#new-page-info-modal').modal('hide');
                    siteEditor.loadPage(response.page_id);
                } else {
                    wrapper.css('pointer-events', 'initial');
                    wrapper.css('opacity', 1);
                }
            },
            error: function() {
                wrapper.css('opacity', 1);
                wrapper.css('pointer-events', 'initial');
            }
        });
        return false;
    },
    showAddPage: function() {
        var modal = $('#new-page-info-modal');
        modal.modal('toggle');
        initRichEditor();
        return false;
    },

    addMenu: function(title, link, icon, type, ajax, tab, id) {
        if(id == undefined || id == '') id = $.now();
        var location = $("#menu-locations").val()
        $.ajax({
            url: baseUrl + 'admincp/site/menu/add?csrf_token=' + requestToken,
            data: {title: title, link: link, icon: icon, type: type, ajax: ajax, tab: tab, id: id, location: location}
        });
    },

    deleteMenu: function(id) {
        var menu = $("#" + id + "-menu");
        menu.hide().remove();
        $.ajax({
            url: baseUrl + 'admincp/site/menu/delete?csrf_token=' + requestToken,
            data: {id: id, location: $("#menu-locations").val()}
        });
        return false;
    },

    submitLinkMenu: function(t) {
        var form = $(t);
        form.css('opacity', '0.6');
        form.ajaxSubmit({
            url: baseUrl + 'admincp/site/menu/link/add',
            success: function(data) {
                var json = jQuery.parseJSON(data);
                var id = json.id;
                var title = json.title;
                var div = $("<div class='menu-item' id='" + id + "-menu'><span class='menu-title'>" + title + "</span> <a onclick=\"return siteEditor.editMenu('" + id + "')\" href='' style='color:#009CEB'>Edit</a> <a style='font-size:15px' href='' onclick=\"return siteEditor.deleteMenu('" + id + "')\" class='close'><i class='ion-close'></i></a></div>");
                $("#menu-location-items").append(div);
                form.css('opacity', 1);
            }
        });

        return false;
    },

    editMenu: function(id) {
        var modal = $("#edit-menu-modal");
        var loader = modal.find('#menu-loader');
        var content = modal.find('.edit-menu-content');
        content.html('');
        loader.show();
        modal.modal('toggle');
        $.ajax({
            url: baseUrl + 'admincp/site/menu/edit?id=' + id + '&csrf_token=' + requestToken,
            success: function(data) {
                content.html(data);
                loader.hide();
            }
        })
        return false;
    },

    saveMenu: function(form) {
        var form = $(form);
        form.css('opacity', '0.6');
        form.ajaxSubmit({
            url: baseUrl + 'admincp/site/menu/save',
            success: function(data) {
                var modal = $("#edit-menu-modal");
                modal.modal('toggle');
                var json = jQuery.parseJSON(data);
                $("#" + json.id + "-menu").find('.menu-title').html(json.title);
                form.css('opacity', 1);
            }
        });
        return false;
    },
    changeMenu: function(t) {
        var o = $(t);
        var location = o.val();
        var container = $("#menu-location");
        $.ajax({
            url: baseUrl + 'admincp/site/menu/change?location=' + location + '&csrf_token=' + requestToken,
            beforeSend: function() {
                container.css('opacity', '.5');
            },
            success: function(data) {
                container.html(data);
                container.css('opacity', '1');
                siteEditor.reloadInit();
            },
            error: function() {
                container.css('opacity', '1');
            }
        })
    },

    filter: function(selector, filter) {
        filter = filter.toLowerCase();
        if(filter) {
            $(selector + '[data-filter]').filter('[data-filter*="' + filter + '"]').show();
            $(selector + '[data-filter]').filter(':not([data-filter*="' + filter + '"])').hide();
        } else {
            $(selector + '[data-filter]').show();
        }
    },

    reloadInit: function() {
        $('#widgets-container .widget').draggable({

            containment: 'window',
            cursor: 'move',
            revert: 'invalid',
            iframeFix: true,
            appendTo: 'body',
            scroll: false,
            zIndex: 9999,
            helper: 'clone'
        });
        $('.layout-container .col').droppable({
            accept: '#widgets-container .widget',
            drop: function(e, ui) {
                var drop = $(this);
                var o = ui.draggable;
                var div = $("<div class='widget'>" + o.html() + "</div>");
                var pos = drop.data('position');
                var id = $.now();
                var widget = o.data('widget');
                var settings = o.data('setting');
                div.append("<input type='hidden' name='" + pos + "[" + id + "][widget]' value='" + widget + "'/>");
                div.append("<input type='hidden' id='settings-" + id + "' name='" + pos + "[" + id + "][settings]' value=''/>");
                if(settings == 1) div.append("<a onclick='return siteEditor.loadWidgetSettings(this, \"" + id + "\",\"" + widget + "\")' href='' style='font-size:12px;margin-left: 5px;display: inline-block;color: #3CB8FF'>Edit</a>");

                div.append("<a onclick='return siteEditor.deleteWidget(this, \"" + id + "\")' href='' class='close' style='font-size:15px'><i class='ion-android-close'></i></a>");
                drop.append(div);
            }
        });
        $('.layout-container .col').sortable({
            items: '.widget',
            containment: 'window',
            appendTo: 'body',
            helper: 'clone'
        });

        $('#available-menus .menu-item').draggable({

            containment: 'window',
            cursor: 'move',
            revert: 'invalid',
            iframeFix: true,
            appendTo: 'body',
            scroll: false,
            zIndex: 9999,
            helper: 'clone'
        });

        $('#menu-location-items').droppable({
            accept: '#available-menus .menu-item',
            drop: function(e, ui) {
                var drop = $(this);
                var o = ui.draggable;
                var title = o.data('title');
                var link = o.data('link');
                var icon = o.data('icon');
                var id = $.now();

                siteEditor.addMenu(title, link, icon, 'manual', 1, 0, id);
                title = o.html();
                var div = $("<div class='menu-item' id='" + id + "-menu'><span class='menu-title'>" + title + "</span> <a onclick=\"return siteEditor.editMenu('" + id + "')\" href='' style='color:#009CEB'>Edit</a>  <a style='font-size:15px' href='' onclick=\"return siteEditor.deleteMenu('" + id + "')\" class='close'><i class='ion-close'></i></a></div>");
                drop.append(div);
            }
        });
        $('#menu-location-items').sortable({
            items: '.menu-item',
            containment: 'window',
            appendTo: 'body',
            helper: 'clone',
            update: function(e, ui) {
                var data = $('#menu-location-items').sortable('toArray');
                var location = $('#menu-locations').val();
                $.ajax({
                    url: baseUrl + 'admincp/site/menu/sort?csrf_token=' + requestToken,
                    type: 'POST',
                    data: {location: location, data: data}
                })
            }
        });
    }
}

$(function() {
    siteEditor.init();
});