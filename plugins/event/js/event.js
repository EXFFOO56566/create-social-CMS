function upload_event_profile_cover(reposition) {
    toggle_profile_cover_indicator(true);
    var id = $('#event-profile-container').data('id');
    $("#profile-cover-change-form").ajaxSubmit({
        url: baseUrl + 'event/change/cover?id=' + id,
        success: function(data) {
            var result = jQuery.parseJSON(data);
            if(result.status == 0) {
                alert(result.message);
            } else {
                var img = result.image;
                $('.profile-cover-wrapper img').attr('src', img);
                $('.profile-resize-cover-wrapper img').attr('src', result.original);
                if(reposition) {
                    reposition_user_profile_cover();
                }
            }
            toggle_profile_cover_indicator(false);
        }
    })
}

function save_event_profile_cover() {
    var i = $('#profile-cover-resized-top').val();
    var id = $('#event-profile-container').data('id');
    var width = $('#event-profile-container').data('width');
    if(i == 0) {
        refresh_profile_cover_positioning()
    } else {
        toggle_profile_cover_indicator(true);
        $.ajax({
            url: baseUrl + 'event/cover/reposition?pos=' + i + '&id=' + id + '&width=' + width + '&csrf_token=' + requestToken,
            success: function(data) {
                $('.profile-cover-wrapper img').attr('src', data);
                toggle_profile_cover_indicator(false);
                refresh_profile_cover_positioning();
            },
            error: function() {
                toggle_profile_cover_indicator(false);
                refresh_profile_cover_positioning();
            }
        })
    }
    return false;
}

function event_feed_toggle(e) {
    var detail_link = $(".event-about-link");
    var discussion_link = $(".event-discussion-link");
    var event_id = $(e).data('id');
    var event_action = $(e).data('action');
    var current_action = $(e).data('current');
    if(event_action == 'feed') {
        detail_link.data('current', 0);
    } else {
        discussion_link.data('current', 0);
    }
    var m = $('.event-loading-img');
    m.css('display', 'block').fadeIn();

    if(current_action == "1") {
        return false;
    }
    $.ajax({
        url: baseUrl + 'event/about/discussion?id=' + event_id + '&action=' + event_action + '&csrf_token=' + requestToken,
        success: function(data) {
            var json = JSON.parse(data);
            if(json.status == 1) {
                $("#event-about-discussion-container").html(json.content);
                $(e).data('current', json.status);
                m.hide();
                if(event_action == 'feed') {
                    // feed.editor.init();
                }
            }
        }
    });
    return false;
}

function remove_event_profile_cover(img) {

    $('.profile-cover-wrapper img').attr('src', img);
    $('.profile-resize-cover-wrapper img').attr('src', '');
    var id = $('#event-profile-container').data('id');
    $.ajax({
        url: baseUrl + 'event/cover/remove?id=' + id + '&csrf_token=' + requestToken,
    });
    return false;
}

function event_invite_friend(t, userid, id) {
    var o = $('.event-invite-user-' + userid);
    o.css('opacity', '0.5');
    $.ajax({
        url: baseUrl + 'event/invite/user?id=' + id + '&userid=' + userid + '&csrf_token=' + requestToken,
        success: function(data) {
            $('.event-invited-stats').html(data);
            o.fadeOut();
        },
        error: function() {
            o.css('opacity', 1);
        }
    })
    return false;

}

function event_search_invite_friend(i) {
    var input = $(i);
    var container = $(".event-invite-friends-list");

    if(input.val().length > 1) {

        $.ajax({
            url: baseUrl + 'event/invite/search?id=' + container.data('id') + '&term=' + input.val() + '&csrf_token=' + requestToken,
            success: function(data) {
                container.html(data);
            }
        })
    }
}

function event_rsvp(t, id) {
    var s = $(t);
    var rsvp_value = '';
    if(s.val()) {
        rsvp_value = s.val();
    } else {
        rsvp_value = s.data('id');
    }
    s.css('opacity', '0.5');
    $.ajax({
        url: baseUrl + 'event/rsvp?id=' + id + '&v=' + rsvp_value + '&csrf_token=' + requestToken,
        success: function(d) {
            var json = jQuery.parseJSON(d);
            $(".event-going-stats").html(json.going);
            $(".event-maybe-stats").html(json.maybe);
            $(".event-invited-stats").html(json.invited);
            $(".event-interested-stats").html(json.interested);
            s.css('opacity', 1);
        },
        error: function() {
            s.css('opacity', 1);
        }
    })
}

function toggle_visibility() {
    if(document.getElementById('postas').value == 'user') {
        document.getElementById('pages').style.display = 'none';
    } else {
        document.getElementById('pages').style.display = 'block';
    }
}

if(typeof subscribers === "undefined") {
    window.subscribers = {
        filterParams: [],
        filterParamsHooks: [],

        addFilterParamsHook: function(hook) {
            subscribers.filterParamsHooks.push(hook);
        },

        runFilterParamsHooks: function() {
            for(var i = 0; i <= subscribers.filterParamsHooks.length - 1; i++) {
                eval(window.pageLoadHooks[i])();
            }
        }
    };
}

function eventChooseDate() {
    $(document).ready(function() {
        $('.applyBtn').on('click', function() {
            var dateValue = '';
            $('.input-mini').each(function() {
                dateValue += '-' + $(this).val();
            });
            dateValue = dateValue.replace(/^[\-]+/, '');
            console.log(dateValue);
            var url = baseUrl + 'events?type=select&param=date-range&daterange=' + dateValue;
            $('.input-mini').remove();
            loadPage(url);
        });
    });
}

function initEventSlider() {
    var jcarousel = $('.event-slider');

    jcarousel
        .on('jcarousel:reload jcarousel:create', function() {
            var carousel = $(this),
                width = carousel.innerWidth();

            if(width >= 600) {
                width = width / 3;
            } else if(width >= 350) {
                width = width / 2;
            }

            carousel.jcarousel('items').css('width', Math.ceil(width) + '%');
        })
        .jcarousel({
            wrap: 'circular'
        });

    $('.jcarousel-control-prev')
        .jcarouselControl({
            target: '-=1'
        });

    $('.jcarousel-control-next')
        .jcarouselControl({
            target: '+=1'
        });

    $('.jcarousel-pagination')
        .on('jcarouselpagination:active', 'a', function() {
            $(this).addClass('active');
        })
        .on('jcarouselpagination:inactive', 'a', function() {
            $(this).removeClass('active');
        })
        .on('click', function(e) {
            e.preventDefault();
        })
        .jcarouselPagination({
            perPage: 1,
            item: function(page) {
                return '<a href="#' + page + '">' + page + '</a>';
            }
        });
    return false;
}

(function($) {
    $(function() {
        initEventSlider();
        eventChooseDate();
    });
})(jQuery);


function event_page_loaded() {
    initEventSlider();
    eventChooseDate();
}


try {
    addPageHook('event_page_loaded');
} catch(e) {
}
