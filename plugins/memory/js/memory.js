if(typeof memory === 'undefined') {
    memory = {
        init: function() {

        },

        shareFeed: function(id, message) {
            confirm.action(function () {
                $.ajax({
                    url: baseUrl + 'memory/feed/share?id=' + id + '&csrf_token=' + requestToken,
                    success: function (response) {
                        response = JSON.parse(response);
                        if (response.status) {
                            notifySuccess(response.message)
                        }
                    },
                    error: function () {
                    }
                })
            }, message);
            return false;
        }
    }
}

$(function() {
    try {
        addPageHook('music.init');
    } catch (e) {}
});
