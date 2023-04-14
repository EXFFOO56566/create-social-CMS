function change_livestream_source(t) {
    var o = $(t);
    var v = o.val();
    $(".livestream-source-selector .source").hide();
    $(".livestream-source-selector  ." + v).fadeIn();
    if(v == 'upload') {
        $('.livestream-details-container').show();
    } else {
        $('.livestream-details-container').hide();
    }
    return true;
}


function livestream_form_list_url() {
    var form = $('#livestream-list-search');
    var v = form.find('input[type=text]').val();
    var cat = $("#livestream-category-list").val();

    var type = form.find('.livestream-type-input').val();
    var filter = $("#livestream-filter-select").val();
    var url = baseUrl + 'livestreams?term=' + v + "&category=" + cat + "&type=" + type + '&filter=' + filter;

    return url;
}
function livestream_submit_search(t) {
    url = livestream_form_list_url();
    loadPage(url);
    return false;
}

function livestream_list_change_category(t) {
    url = livestream_form_list_url();
    loadPage(url);
}