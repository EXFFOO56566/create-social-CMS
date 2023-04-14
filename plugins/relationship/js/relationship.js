function process_follow(userid) {
    var o = $("#follow-button-" + userid);
    var status = o.attr('data-status');
    var follow = o.data('follow');
    var unfollow = o.data('unfollow');
    if (status == 1) {
        //user want to unfollow
        o.removeClass('followed').html(follow).attr('data-status', 0);
        type = 'unfollow';
    } else {
        //user want to follow
        o.addClass('followed').html(unfollow).attr('data-status', 1);
        type = 'follow'
    }

    $.ajax({
        url : baseUrl + 'relationship/follow?type=' + type + '&userid=' + userid + '&csrf_token=' + requestToken,
    })
    return false;
}

function process_friend(userid) {
    var o = $(".friend-button-" + userid);
    var status = o.attr('data-status');
    var addText = o.data('add');
    var sentText = o.data('sent');

    if (status == 0) {
        //adding user

        o.css('opacity', '0.4');

        $.ajax({
            url : baseUrl + 'relationship/add/friend?userid=' + userid + '&csrf_token=' + requestToken,
            success : function(data) {
                if (data == 1) {
                    o.find('span').html(sentText);
                    o.attr('data-status', 1);
                }
                o.css('opacity', 1);
            }
        })
    } else if(status == 1 || status == 2) {
        //canceling friend or request made
        message = (status == 1) ? o.data('cancel-warning') : o.data('remove-warning');
        confirm.action(function() {
            o.css('opacity', '0.4');
            $.ajax({
                url : baseUrl + 'relationship/remove/friend?userid=' + userid + '&csrf_token=' + requestToken,
                success: function(data) {
                    if (data == 1) {
                        o.find('span').html(addText);
                        o.removeClass('ready-friend').attr('data-status', 0);
                    }
                    o.css('opacity', 1);
                }
            })
        }, message)
    }
    return false;
}

function show_friend_request_dropdown() {
    var dropdown = $(".friend-request-dropdown");
    var indicator = dropdown.find('#friend-request-dropdown-indicator');
    var content = dropdown.find('.friend-request-dropdown-result-container');
    if (dropdown.css('display') == 'none') {
        dropdown.fadeIn();
        indicator.show();
        $.ajax({
            url : baseUrl + 'relationship/load/requests?csrf_token=' + requestToken,
            success : function(data) {
                content.html(data);
                indicator.hide();
                var counter = $('#friend-request-dropdown-container > a > span');
                counter.remove();
                reloadInits();
            }
        })
    } else {
        dropdown.fadeOut();
    }
    $(document).click(function(e) {
        if(!$(e.target).closest("#friend-request-dropdown-container").length) dropdown.hide();
    });
    return false;
}

function confirm_friend_request(userid, b) {
    var c = $('#friend-request-' + userid);
    var requestButton = $(".friend-request-respond-button-" + userid);
    c.css('opacity', '0.4');
    requestButton.css('opacity', '0.4');
    $("#friend-requests-respond-dropdown-" + userid).hide();
    $.ajax({
        url : baseUrl + 'friend/request/confirm?userid=' + userid + '&csrf_token=' + requestToken,
        type : 'GET',
        success : function(data) {
            if (data == 'login') {
                login_required();
            } else {
                c.css('opacity', 1).find('.actions').fadeOut();
                requestButton.hide();
                var button = $('.friend-button-' + userid);
                var frTrans = button.data('friends');
                button.show().attr('data-status', '2').html(frTrans).addClass('ready-friend');
            }
        }
    })
    return false;
}

function delete_friend_request(userid, b) {
    var c = $('#friend-request-' + userid);
    var requestButton = $("#friend-request-respond-button-" + userid);
    c.css('opacity', '0.4');
    requestButton.css('opacity', '0.4');
    $("#friend-requests-respond-dropdown-" + userid).hide();
    $.ajax({
        url : baseUrl + 'relationship/remove/friend?userid=' + userid + '&csrf_token=' + requestToken,
        success: function(data) {
            if (data == 1) {
                c.slideUp();
                requestButton.hide();
                var button = $('.friend-button-' + userid);
                var frTrans = button.data('add');
                button.show().attr('data-status', '0').html(frTrans);
            } else {
                login_required();
            }

        }
    });
    return false;
}
function push_friend_requests(type, d) {
    if (type == 'friend-request') {
        var notyCounts = 0;
        var a = $("#friend-request-dropdown-container > a");
        if (!a.find('span').length) {
            a.append("<span class='count' style='display:none'></span>")
        }
        var span = a.find('span');
        var nIds = '';
        $.each(d, function(pushId, nId) {
            if (!Pusher.hasPushId(pushId)) {
                Pusher.addPushId(pushId);
                nIds += (nIds) ? ',' + nId : nId;
            }
            notyCounts +=1;
        });

        if (notyCounts > 0) {
            span.html(notyCounts).fadeIn();
            Pusher.addCount(notyCounts);
        }

        a.click(function() {
            Pusher.removeCount(notyCounts);
            span.hide();
        });
        if (nIds) {
            $.ajax({
                url : baseUrl + 'friend/requests/preload?csrf_token=' + requestToken,
                success : function(data) {
                    var c = $(".friend-request-dropdown-result-container");
                    c.prepend(data);
                    reloadInits();
                }
            })
        }
    }
}

Pusher.addHook('push_friend_requests');

// Relationship status and family relationships
$(function() {
    $(document).on('click', '#relationship-tags-suggestion a', function() {
        $('#relationship-tags-suggestion').hide();
        var uid = $(this).data('id');
        var id = $('#relationship-tags-suggestion').data('id');
        $('#relationship-tags-suggestion-input').val('')
        $.ajax({
            url : baseUrl + 'relationship/add/member?id=' + id + '&uid=' + uid + '&csrf_token=' + requestToken,
            success : function(data) {
                //notifySuccess(data);
                $("#family-container").html(data)
            }
        })
        return false;
    });


    $(document).on('click', '#relationship-cancel', function () {
        $('#relationship').hide();
        return false;
    });


    $(document).on('click', '#relationship-edit', function () {
        $('#relation-edit').show();
        $('.relation-edit').hide();
        return false;
    });

    $(document).on('click', '#relationship-cancel', function () {
        $('#relation-edit').hide();
        $('.relation-edit').show();
        return false;
    });

    $(document).on('click', '#relationship-save', function () {
        var status = $("#relation-status").val();
        $.ajax({
            url : baseUrl + 'relationship/edit/status?val=' + status + '&csrf_token=' + requestToken,
            success : function(data) {
                data = JSON.parse(data);
                if (data.status == 1) {
                    notifySuccess(data.success);
                    $(".r-status").html(data.message);
                    $('#relation-edit').hide();
                    $('.relation-edit').show();
                } else {
                    notifyError(data.message)
                }
            }
        })
    });

    $(document).on('click', '#relationship-send-request', function () {
        var sender = $("#relationship");
        var id = $('#relationship-family-id').val();
        var relationshipType = $('#relationSelector').val();
        if (relationshipType == 0 ) return false;
        var loading = sender.find('.foreground');
        loading.fadeIn();
        var indicator = sender.find('.indicator');
        indicator.fadeIn();
        $.getJSON(baseUrl + 'relationship/family/request?uid=' + id + '&type=' + relationshipType + '&csrf_token=' + requestToken, function (data) {
            indicator.hide();
            var messageParent = sender.find('.message');
            var message = data.status == true ? sender.find('.alert-success') : sender.find('.alert-danger');
            messageParent.fadeIn();
            message.fadeIn().css("display", "inline-block");
            setTimeout(function () {
                message.hide();
                messageParent.hide();
                loading.hide();
            }, 2000);
            setTimeout(function () {
                sender.hide();
                $("#relationship-families").append(data.content);
                notifySuccess("successful");
            }, 2000);
        });
        return false;
    });
})

function relationship_edit(e) {
    var id = $(e).data('id');
    var r = $(e).data('r');
    var sender = $("#relationship");
    var loading = sender.find('.foreground');
    loading.fadeIn();
    var indicator = sender.find('.indicator');
    indicator.fadeIn();
    $.ajax({
        beforeSend: function () {
            $(e).attr('disabled', 'disabled');
        },
        url: baseUrl + 'relations/edit?id=' + id + '&r=' + r,
        success: function (data) {
            $(e).removeAttr('disabled');
            $('.edit-relation-details-'+ id).hide();
            $('#edit-relation-details-'+ id).html(data);
            indicator.hide();
            loading.hide();
        }
    });
    return false;
}

function save_relations(e) {
    var id = $('#relationship-family-id').val();
    var user_id = $('#relation-user-id').val();
    var relationshipType = $('#relationSelector').val();
    $.ajax({
        url : baseUrl + 'relationship/save?id=' + id + '&rel=' + relationshipType,
        success : function(data) {
            data = JSON.parse(data);
            if (data.status == 1) {
                notifySuccess(data.success);
                $('#edit-relation-details-'+ user_id).html("");
                $('.edit-relation-details-'+ user_id).html(data.content).show();
            } else {
                notifyError(data.success)
            }
        }
    })
}

function cancel_save_relations(e) {
    var id = $(e).data('id');
    $('.edit-relation-details-'+ id).show();
    $('#edit-relation-details-'+ id).html("");
}
