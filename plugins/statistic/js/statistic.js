$(document).ready(function() {
	$(document).on('click', '#statistic-site-widget .height-toggle', function() {
        var minHeight = '243px';
        var maxHeight = '930px';
        if($('#statistic-site-widget .menu').css('height') == minHeight) {
            $('#statistic-site-widget .menu').css('height', maxHeight);
            $('#statistic-site-widget .height-toggle .indicator').css('transform', 'rotate(180deg)');
        } else {
            $('#statistic-site-widget .menu').css('height', minHeight);
            $('#statistic-site-widget .height-toggle .indicator').css('transform', 'rotate(0deg)');
        }
    });
});