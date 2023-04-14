function adsVideoInit() {
	$("#adstype").change(function() {
		if($(this).val() == "video") {
			$(".non-video-ads").hide();
			$(".video-ads").show();
		}
        else if($("#adstype").val() == "overlay") {
              $(".not-overlay").hide();
              $(".overlay").show();
        }
		else {
			$(".non-video-ads").show();
			$(".video-ads").hide();
		}
	});
};

$(document).ready(function() {
	adsVideoInit();
	try {
		addPageHook('adsVideoInit');
	} catch(e) {
	}
});