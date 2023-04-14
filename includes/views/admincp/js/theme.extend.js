$(function() {
    $('.auto-preview-image').change(function() {
        var fileHandle = this;
        if(fileHandle.files && fileHandle.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var preview = $('#' + $(fileHandle).data('preview'));
                if(preview.prop('tagName') == 'IMG') {
                    preview.attr('src', e.target.result);
                } else {
                    preview.css('background-image', 'url(' + e.target.result + ')');
                }
                var dimension = $('#' + $(fileHandle).data('dimension'));
                if(dimension) {
                    var image = new Image();
                    image.addEventListener('load', function() {
                        dimension.html(image.width + 'px x ' + image.height + 'px');
                    });
                    image.src = e.target.result;
                }
                var reset = $('#' + $(fileHandle).data('reset'));
                if(reset) {
                    reset.fadeIn('fast');
                }
            }
            reader.readAsDataURL(fileHandle.files[0]);
        }
    });

    $('.reset-image-preview').click(function() {
        var button = $(this);
        var preview = $('#' + button.data('preview'));
        var originalImage = button.data('original-image');
        var imageHandle = $('#' + button.data('image-handle'));
        var dimension = $('#' + button.data('dimension'));
        if(preview.prop('tagName') == 'IMG') {
            preview.attr('src', originalImage);
        } else {
            preview.css('background-image', 'url(' + originalImage + ')');
        }
        imageHandle.val(null);
        if(dimension) {
            dimension.html('');
        }
        button.fadeOut('fast');
    });
});