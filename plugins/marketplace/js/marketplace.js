if(typeof marketplace === 'undefined' || typeof marketplace.init === 'undefined') {
    marketplace = {
        filterParams: [],

        filterParamsHooks: [],

        addFilterParamsHook: function(hook) {
            marketplace.filterParamsHooks.push(hook);
        },

        runFilterParamsHooks: function() {
            for(var i = 0; i <= marketplace.filterParamsHooks.length - 1; i++) {
                eval(window.pageLoadHooks[i])();
            }
        },

        init: function() {
            marketplace.attachEvents()
        },

        attachEvents: function() {
            Hook.register('googleapis.maps.callbacks', marketplace.initMap);

            $('.marketplace-select-images').on('change', function () {
                marketplace.selectImages(this);
            });

            $('.material-card > .mc-btn-action').on('click', function () {
                var card = $(this).parent('.material-card');
                var icon = $(this).children('i');
                icon.addClass('fa-spin-fast');

                if (card.hasClass('mc-active')) {
                    card.removeClass('mc-active');

                    window.setTimeout(function() {
                        icon
                            .removeClass('fa-arrow-left')
                            .removeClass('fa-spin-fast')
                            .addClass('fa-bars');

                    }, 800);
                } else {
                    card.addClass('mc-active');

                    window.setTimeout(function() {
                        icon
                            .removeClass('fa-bars')
                            .removeClass('fa-spin-fast')
                            .addClass('fa-arrow-left');

                    }, 800);
                }
            });

            $('.marketplace-contact').on('click', function (e) {
                e.preventDefault();
                marketplace.showContact(this);
            });

            $('.marketplace-delete-image').on('click', function (e) {
                e.preventDefault();
                marketplace.deleteImageModal(this);
            });

            $('.marketplace-listing-images.slide .marketplace-listing-image').on('click', function () {
                $('#marketplace-image-preview').attr('src', $(this).attr('src').replace(/_75_|_200_/g, "_920_"));
                $('#marketplace-image-preview-modal').modal('show');
            });

            if($('.marketplace-listing-images.slide').length) {
                $('.marketplace-listing-images.slide').slick({
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    arrows: false,
                    fade: true,
                    asNavFor: '.marketplace-listing-images.nav',
                    autoplay: false,
                    autoplaySpeed: 10000,
                    infinite: true,
                    focusOnSelect: true,
                });
            }

            if($('.marketplace-listing-images.nav').length) {
                $('.marketplace-listing-images.nav').slick({
                    slidesToShow: 3,
                    slidesToScroll: 1,
                    asNavFor: '.marketplace-listing-images.slide',
                    focusOnSelect: true,
                    autoplay: true,
                    dots: true,
                    autoplaySpeed: 5000,
                    prevArrow: '<div class="ion-ios-arrow-left prev"></div>',
                    nextArrow: '<div class="ion-ios-arrow-right next"></div>',
                    infinite: true,
                });
            }
        },

        filter: function() {
            var url = baseUrl + 'marketplace';
            marketplace.filterParams = [];
            if(document.getElementById('marketplace-keyword-filter') && document.getElementById('marketplace-keyword-filter').value !== '') marketplace.filterParams.push('s=' + document.getElementById('marketplace-keyword-filter').value);
            if(document.getElementById('marketplace-category-filter') && document.getElementById('marketplace-category-filter').value !== '') marketplace.filterParams.push('c=' + document.getElementById('marketplace-category-filter').value);
            if(document.getElementById('marketplace-featured-filter') && document.querySelectorAll('#marketplace-featured-filter:checked').length) marketplace.filterParams.push('f=' + document.getElementById('marketplace-featured-filter').value);
            if(document.getElementById('marketplace-min-price-filter') && document.getElementById('marketplace-min-price-filter').value !== '') marketplace.filterParams.push('p0=' + document.getElementById('marketplace-min-price-filter').value);
            if(document.getElementById('marketplace-max-price-filter') && document.getElementById('marketplace-max-price-filter').value !== '') marketplace.filterParams.push('p1=' + document.getElementById('marketplace-max-price-filter').value);
            if(document.getElementById('marketplace-location-filter') && document.getElementById('marketplace-location-filter').value !== '') marketplace.filterParams.push('l=' + document.getElementById('marketplace-location-filter').value);
            marketplace.runFilterParamsHooks();
            if(marketplace.filterParams.length > 0) url += '?' + marketplace.filterParams.join('&');
            loadPage(url);
            return false;
        },

        fileSize: function(size) {
            var i = -1;
            var byteUnits = [' KB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB'];
            do {
                size = size / 1024;
                i++;
            } while(size > 1024);
            return Math.max(size, 0.1).toFixed(1) + byteUnits[i];
        },

        selectImages: function(input) {
            var files = input.files;
            if(files.length) {
                var imagesHTML = '';
                for(var i = 0; i < files.length; i++) {
                    imagesHTML += '<div>' + files[i]['name'] + ' (' + marketplace.fileSize(files[i]['size']) + ')</div>';
                }
                document.getElementById('marketplace-form-images-container').innerHTML = imagesHTML;
            }
        },

        deleteImageModal: function(link) {
            var id = $(link).data('id');
            var src = $(link).data('src');
            var action = $(link).attr('href');
            $('#marketplace-image-delete-form').attr('action', action);
            $('#image-delete-id').attr('value', id);
            $('#image-delete-preview').attr('src', src);
            $('#marketplace-image-delete-modal').modal('show');
            return false
        },

        showContact: function(button) {
            var contact = $(button).data('contact');
            $(button).parent().html('<div class="contact-info">' + contact + '</div>');
            return false;
        },

        geocodeLocation: function(geocoder, resultsMap) {
            var listing = document.getElementById('marketplace-listing');
            var address = listing.getAttribute('data-location');
            geocoder.geocode({'address': address}, function (results, status) {
                if (status === google.maps.GeocoderStatus.OK) {
                    resultsMap.setCenter(results[0].geometry.location);
                    var marker = new google.maps.Marker({
                        map: resultsMap,
                        position: results[0].geometry.location
                        //animation: google.maps.Animation.BOUNCE
                    });
                } else {
                    //alert('Geocode Unsuccessful: ' + status);
                }
            });
        },

        initMap: function() {
            $('.marketplace-geocomplete').geocomplete().bind('geocode:result', function (event, result) {});
            if(document.getElementById('marketplace-listing-map')) {
                var map = new google.maps.Map(document.getElementById('marketplace-listing-map'), {
                    zoom: 8,
                    center: {
                        lat: -34.397,
                        lng: 150.644
                    }
                });
                var geocoder = new google.maps.Geocoder();
                marketplace.geocodeLocation(geocoder, map);
            }
        }
    }
}

marketplace.init();

$(function() {
    try {
        addPageHook('marketplace.init');
    } catch (e) {}
});