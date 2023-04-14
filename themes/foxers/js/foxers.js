$(function() {
    function checkFoxersScroll() {
        if(window.scrollY > 50) {
            $('#explore-menu').fadeOut("fast", "linear");
            $('.foxers-fixed-explore').fadeIn();
        } else {
            $('#explore-menu').fadeIn("fast", "linear");
            $('.foxers-fixed-explore').fadeOut();
        }
    }

    checkFoxersScroll();
    $(window).scroll(checkFoxersScroll);
});


$(function() {
    $('#login-form-link').click(function(e) {
        $("#login-form").delay(100).fadeIn(100);
        $("#register-form").fadeOut(100);
        $('#register-form-link').removeClass('active');
        $(this).addClass('active');
        e.preventDefault();
    });

    $('#register-form-link').click(function(e) {
        $("#register-form").delay(100).fadeIn(100);
        $("#login-form").fadeOut(100);
        $('#login-form-link').removeClass('active');
        $(this).addClass('active');
        e.preventDefault();
    });
});

$(function() {
    function validateEmail(email) {
        var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(email);
    }

    $('#signup-previous').click(function(e) {
        $("#reg-tab-1").delay(100).fadeIn(100);
        $("#reg-tab-2").fadeOut(100);
        $("#signup-continue").delay(100).fadeIn();
        $('#signup-previous').fadeOut(100);
        e.preventDefault();
    });

    $('#signup-continue').click(function(e) {
        var first_name = $('#first_name').val();
        var last_name = $('#last_name').val();
        var username = $('#username').val();
        var email = $('#email').val();
        if(first_name.length === 0) {
            $('#first_name').focus();
            $('#first_name').addClass('reg-focus-error');
            return false;
        } else if(last_name.length === 0) {
            $('#last_name').focus();
            $('#last_name').addClass('reg-focus-error');
            return false;
        } else if(username.length === 0) {
            $('#username').focus();
            $('#username').addClass('reg-focus-error');
            return false;
        } else if(email.length === 0 || !validateEmail(email)) {
            $('#email').focus();
            $('#email').addClass('reg-focus-error');
            return false;
        }
        $("#reg-tab-2").delay(100).fadeIn(100);
        $('#signup-previous').delay(100).fadeIn(100);
        $("#reg-tab-1").fadeOut(100);
        $("#signup-continue").fadeOut(100);
        e.preventDefault();
    })
});