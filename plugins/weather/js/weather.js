function forecasts_button() {
    var e = $('.forcast_container');
    if(e.css('display') == 'none') {
        e.show();
        reloadInits();
    } else {
        e.hide();
    }
}