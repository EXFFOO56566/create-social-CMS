global = {
    magicSelect: {
        init: function() {
            var magicSelects = document.getElementsByClassName('magic-select');
            for(var i = 0; i < magicSelects.length; i++) {
                var name = magicSelects[i].getAttribute('data-name');
                if(name) {
                    if(!magicSelects[i].querySelector('input[name="' + name + '"]')) {
                        var input = document.createElement('input');
                        input.setAttribute('type', 'hidden');
                        input.setAttribute('name', name);
                        input.setAttribute('class', 'magic-select-value');
                        var firstOption = magicSelects[i].querySelector('.magic-select-option');
                        var value = firstOption ? firstOption.getAttribute('data-value') : '';
                        input.setAttribute('value', value);
                        magicSelects[i].insertBefore(input, magicSelects[i].firstChild);
                    }
                    var width = magicSelects[i].getAttribute('data-width');
                    if(width) {
                        magicSelects[i].style.width = width;
                    }
                }
            }
        },

        addEvents: function() {
            window.addEventListener('click', function(event) {
                if(event.target.matches('.magic-select-toggle') || event.target.matches('.magic-select-option')) {
                    event.preventDefault();
                    var magicSelect;
                    var magicSelectContent;
                    var closest = event.target;
                    while(closest.parentNode.classList && !closest.parentNode.classList.contains('magic-select') && closest.tagName !== 'HTML') {
                        closest = closest.parentNode;
                    }
                    magicSelect = closest !== event.target && closest.parentNode.tagName === 'HTML' ? null : closest.parentNode;
                    magicSelectContent = magicSelect.querySelector('.magic-select-content');
                    if(magicSelect) {
                        if(event.target.matches('.magic-select-toggle')) {
                            if(magicSelectContent && !magicSelectContent.classList.contains('show')) {
                                magicSelectContent.classList.add('show');
                                if(magicSelectContent.parentNode.classList.contains('slimScrollDiv')) {
                                    magicSelectContent.parentNode.style.display = 'inline-block';
                                }
                            }
                        } else {
                            var magicSelectValue = magicSelect.querySelector('.magic-select-value');
                            if(magicSelectValue) {
                                var magicSelectToggle = magicSelect.querySelector('.magic-select-toggle');
                                magicSelectToggle.innerHTML = event.target.innerHTML;
                                var value = event.target.getAttribute('data-value');
                                magicSelectValue.setAttribute('value', value);
                                if(magicSelectContent && magicSelectContent.classList.contains('show')) {
                                    magicSelectContent.classList.remove('show');
                                    if(magicSelectContent.parentNode.classList.contains('slimScrollDiv')) {
                                        magicSelectContent.parentNode.style.display = 'none';
                                    }
                                }
                            }
                        }
                    }
                } else if(!(event.target.matches('.slimScrollBar') || event.target.matches('.slimScrollBar') || event.target.matches('.magic-select-label'))) {
                    var magicSelectContents = document.getElementsByClassName('magic-select-content');
                    for(var i = 0; i < magicSelectContents.length; i++) {
                        if(magicSelectContents[i].classList.contains('show')) {
                            magicSelectContents[i].classList.remove('show');
                            if(magicSelectContents[i].parentNode.classList.contains('slimScrollDiv')) {
                                magicSelectContents[i].parentNode.style.display = 'none';
                            }
                        }
                    }
                }
            });
        }
    },

    magicInputImagePreview: {
        defaultImageURL: baseUrl + 'themes/default/images/mi_prev.png',

        init: function() {
            var magicInputImagePreviews = document.getElementsByClassName('magic-input-image-preview');
            for(var i = 0; i < magicInputImagePreviews.length; i++) {
                var name = magicInputImagePreviews[i].getAttribute('data-name');
                if(name) {
                    if(!magicInputImagePreviews[i].querySelector('input[type="file"]')) {
                        var input = document.createElement('input');
                        input.setAttribute('type', 'file');
                        input.setAttribute('name', name);
                        magicInputImagePreviews[i].appendChild(input);
                    }
                    if(!magicInputImagePreviews[i].querySelector('.reset')) {
                        var reset = document.createElement('span');
                        reset.setAttribute('class', 'reset ion-close');
                        magicInputImagePreviews[i].appendChild(reset);
                    }
                    var image = magicInputImagePreviews[i].getAttribute('data-image');
                    image = image ? image : global.magicInputImagePreview.defaultImageURL;
                    magicInputImagePreviews[i].style.backgroundImage = 'url(' + image + ')';
                }
            }
        },
        addEvents: function() {
            window.addEventListener('click', function(event) {
                if(event.target.matches('.magic-input-image-preview')) {
                    event.preventDefault();
                    event.target.querySelector('input[type="file"]').click();
                } else if(event.target.matches('.magic-input-image-preview > .reset')) {
                    event.target.parentNode.style.backgroundImage = 'url(' + event.target.parentNode.getAttribute('data-image') + ')';
                    event.target.parentNode.querySelector('input[type="file"]').value = '';
                    var dimension = event.target.parentNode.querySelector('.dimension');
                    if(dimension) {
                        dimension.innerHTML = '';
                    }
                    var image = event.target.parentNode.getAttribute('data-image');
                    image = image ? image : global.magicInputImagePreview.defaultImageURL;
                    event.target.parentNode.style.backgroundImage = 'url(' + image + ')';
                    event.target.style.display = 'none';
                }
            });
            window.addEventListener('change', function(event) {
                if(event.target.matches('.magic-input-image-preview > input[type="file"]')) {
                    var fileHandle = event.target;
                    if(fileHandle.files && fileHandle.files[0]) {
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            var preview = fileHandle.parentNode;
                            preview.style.backgroundImage = 'url(' + e.target.result + ')';
                            var dimension = fileHandle.parentNode.querySelector('.dimension');
                            if(dimension) {
                                var image = new Image();
                                image.addEventListener('load', function() {
                                    dimension.innerHTML = image.width + 'px x ' + image.height + 'px';
                                });
                                image.src = e.target.result;
                            }
                            var reset = fileHandle.parentNode.querySelector('.reset');
                            if(reset) {
                                reset.style.display = 'inline-block';
                            }
                        }
                        reader.readAsDataURL(fileHandle.files[0]);
                    }
                }
            });
        }
    },

    dateTimePickerInit: function() {
        $.datetimepicker.setLocale(locale);
        $('.datetimepicker').datetimepicker(dateTimePickerOptions);
        $('.datepicker').datetimepicker(datePickerOptions);
        $('.timepicker').datetimepicker(timePickerOptions);

        var start = moment().subtract(29, 'days');
        var end = moment();

        function cb(start, end) {
            $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
        }

        $('.dateRangePicker').daterangepicker({
            opens: 'right',
            drops: 'up',
            applyClass: 'btn-primary',
            locale: {
                cancelLabel: 'Cancel',
                applyLabel: 'Find Events',
                customRangeLabel: 'Choose Range',
            },
            /*ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            } */
        }, cb);
        cb(start, end);
    },

    sliderInt: function() {
        $('.slider-side-widget').not('.slick-initialized').slick({
            centerMode: true,
            centerPadding: '1px',
            slidesToShow: 2,
            autoplay: true,
            autoplaySpeed: 2000,
            arrows: true,
            speed: 900,
            nextArrow: '<div class="slick-next-tweak"> <div class="ion-ios-arrow-forward"></div></div>',
            prevArrow: '<div class="slick-prev-tweak"> <div class="ion-ios-arrow-back"></div></div>',
            responsive: [{
                breakpoint: 992,
                settings: {
                    arrows: true,
                    centerMode: true,
                    centerPadding: '40px',
                    slidesToShow: 5
                }
            }, {
                breakpoint: 768,
                settings: {
                    arrows: true,
                    centerMode: true,
                    centerPadding: '40px',
                    slidesToShow: 4
                }
            }, {
                breakpoint: 520,
                settings: {
                    arrows: true,
                    centerMode: true,
                    centerPadding: '40px',
                    slidesToShow: 3
                }
            }, {
                breakpoint: 420,
                settings: {
                    arrows: true,
                    centerMode: true,
                    centerPadding: '40px',
                    slidesToShow: 2
                }
            }, {
                breakpoint: 340,
                settings: {
                    arrows: true,
                    centerMode: true,
                    centerPadding: '40px',
                    slidesToShow: 1
                }
            }]
        });

        $(".slick-next-tweak").removeClass("slick-arrow");
        $(".slick-prev-tweak").removeClass("slick-arrow");

        $(document).on('mouseover', '.slider-side-widget', function() {
            $('.slick-next-tweak').show();
            $('.slick-prev-tweak').show();
            return false;
        });
        $(document).on('mouseout', '.slider-side-widget', function() {
            $('.slick-next-tweak').hide();
            $('.slick-prev-tweak').hide();
            return false;
        });
    }
};


global.magicSelect.init();
global.magicSelect.addEvents();
global.magicInputImagePreview.addEvents();
Hook.register('page.reload.init.after', global.magicSelect.init);
Hook.register('page.reload.init.after', global.dateTimePickerInit);
Hook.register('page.reload.init.after', global.sliderInt);
Hook.register('page.reload.init.after', global.magicInputImagePreview.init);

$(function() {
    global.dateTimePickerInit();
    global.sliderInt();
});
