if(typeof video === 'undefined') {
    window.video = {
        players: [],

        init: function() {
            $(document).on('click', '.video-source-option', function() {
                var value = $(this).data('value');
                video.changeFormSourceField(value);
            });
            $(document).on('mouseover', '.video-thumb-hover:not(.static)', function() {
                if(!$(this).css('background-image') || $(this).css('background-image') === 'none') {
                    var image = $(this).data('image');
                    $(this).css('background-image', 'url(' + image + ')');
                }
            });
            if(window.videoPlayerScrollEventType) {
                video.attachVideoPlayerEvents();
                Hook.register('page.reload.init.after', video.attachVideoPlayerEvents);
            }
        },

        isOnFeed: function() {
            return true;
        },

        scrollEventIsEnabled: function() {
            return video.isOnFeed();
        },

        changeFormSourceField: function(value) {
            $('.video-source-selector .source').hide();
            $('.video-source-selector  .' + value).fadeIn();
            if(value === 'upload') {
                $('.video-details-container').show();
            } else {
                $('.video-details-container').hide();
            }
            return true;
        },

        getListFilterURL: function() {
            var form = $('#video-list-search');
            var term = form.find('input[type=text]').val();
            var category = $('#video-category-list').val();
            var type = form.find('.video-type-input').val();
            var filter = $('#video-filter-select').val();
            return baseUrl + 'videos?term=' + term + '&category=' + category + '&type=' + type + '&filter=' + filter;
        },

        filterList: function() {
            var url = video.getListFilterURL();
            loadPage(url);
        },

        attachVideoPlayerEvents: function() {
            $('.feed-video-embed-container  iframe[src*="' + baseUrl.replace(/(^\w+:|^)\/\//, '') + 'embed/video"]').each(function() {
                var frame = this;
                var playerId = $(frame).closest('.feed-video-embed-container').attr('id');
                if(!(playerId in window.video.players)) {
                    window.video.players[playerId] = [];
                    var frameLoaded = function() {
                        window.video.players[playerId]['type'] = 'internal';
                        window.video.players[playerId]['player'] = $(frame).contents().find('video')[0];
                        window.video.players[playerId]['player'].addEventListener('play', function() {
                            console.log('Video started playing');
                        });
                        if(video.scrollEventIsEnabled() && window.videoPlayerScrollEventType == 2) {
                            var unstick = $('<span class="unstick ion-close"></span>');
                            $(unstick).on('click', function() {
                                window.video.players[playerId]['player'].pause();
                                $(frame).removeClass('is-stuck');
                                $(this).removeClass('is-stuck');
                            });
                            $(unstick).insertAfter(frame);
                        }
                        if(video.scrollEventIsEnabled()) {
                            $(window).scroll(function(e) {
                                if(window.videoPlayerScrollEventType == 1) {
                                    $(frame).is(':in-viewport(400)') ? window.video.players[playerId]['player'].play() : window.video.players[playerId]['player'].pause();
                                } else if(window.videoPlayerScrollEventType == 2) {
                                    if($(frame).hasClass('is-stuck')) {
                                        if($(frame).closest('.feed-video-embed-container').is(':in-viewport')) {
                                            $(frame).removeClass('is-stuck');
                                            $(unstick).removeClass('is-stuck');
                                        }
                                    } else {
                                        if(window.video.players[playerId]['player'].currentTime > 0 && !window.video.players[playerId]['player'].paused && !window.video.players[playerId]['player'].ended && window.video.players[playerId]['player'].readyState > 2 && !$(frame).is(':in-viewport')) {
                                            $('.feed-video-embed-container').not('#' + playerId).find('.unstick.is-stuck').each(function() {
                                                $(this).click();
                                            });
                                            $(frame).addClass('is-stuck');
                                            $(unstick).addClass('is-stuck');
                                        }
                                    }
                                }
                            });
                        }
                    };
                    $(frame).contents().find('video').length ? frameLoaded(): $(frame).bind('load', frameLoaded);
                }
            });

            video.attachVideoPlayerEventYouTubeInterval = setInterval(function() {
                if(window.videoYouTubeAPIReady === true) {
                    video.attachVideoPlayerEventYouTube();
                }
            }, 1000);

            video.attachVideoPlayerEventVimeoInterval = setInterval(function() {
                if(typeof Froogaloop !== 'undefined') {
                    video.attachVideoPlayerEventVimeo();
                }
            }, 1000);

            /*video.attachVideoPlayerEventDailyMotionInterval = setInterval(function() {
                if(typeof DM !== 'undefined') {
                    video.attachVideoPlayerEventDailyMotion();
                }
            }, 1000);*/
        },

        attachVideoPlayerEventYouTube: function() {
            if(typeof video.attachVideoPlayerEventYouTubeInterval !== 'undefined') {
                clearInterval(video.attachVideoPlayerEventYouTubeInterval);
            }
            $('.feed-video-embed-container iframe[src*="be.com/embed/"]').each(function() {
                var frame = this;
                var playerId = $(frame).closest('.feed-video-embed-container').attr('id');
                if(!(playerId in window.video.players)) {
                    window.video.players[playerId] = [];
                    window.video.players[playerId]['type'] = 'youtube';
                    window.video.players[playerId]['player'] = new YT.Player(frame, {
                        events: {
                            'onReady': function(event) {
                                var frame = event.target.getIframe();
                                if(video.scrollEventIsEnabled() && window.videoPlayerScrollEventType == 2) {
                                    var unstick = $('<span class="unstick ion-close"></span>');
                                    $(unstick).on('click', function() {
                                        window.video.players[playerId]['player'].pauseVideo();
                                        $(frame).removeClass('is-stuck');
                                        $(this).removeClass('is-stuck');
                                    });
                                    $(unstick).insertAfter(frame);
                                }
                                if(video.scrollEventIsEnabled()) {
                                    $(window).scroll(function(e) {
                                        if(window.videoPlayerScrollEventType == 1) {
                                            $(frame).is(':in-viewport(400)') ? event.target.playVideo() : event.target.pauseVideo();
                                        } else if(window.videoPlayerScrollEventType == 2) {
                                            if($(frame).hasClass('is-stuck')) {
                                                if($(frame).closest('.feed-video-embed-container').is(':in-viewport')) {
                                                    $(frame).removeClass('is-stuck');
                                                    $(unstick).removeClass('is-stuck');
                                                }
                                            } else {
                                                if(event.target.getPlayerState() == YT.PlayerState.PLAYING && !$(frame).is(':in-viewport')) {
                                                    $('.feed-video-embed-container').not('#' + playerId).find('.unstick.is-stuck').each(function() {
                                                        $(this).click();
                                                    });
                                                    $(frame).addClass('is-stuck');
                                                    $(unstick).addClass('is-stuck');
                                                }
                                            }
                                        }
                                    });
                                }
                            }
                        }
                    });
                }
            });
        },

        attachVideoPlayerEventVimeo: function() {
            if(typeof video.attachVideoPlayerEventVimeoInterval !== 'undefined') {
                clearInterval(video.attachVideoPlayerEventVimeoInterval);
            }
            $('.feed-video-embed-container iframe[src*="player.vimeo.com/video/"]').each(function() {
                var frame = this;
                var playerId = $(frame).closest('.feed-video-embed-container').attr('id');
                if(!(playerId in window.video.players)) {
                    window.video.players[playerId] = [];
                    window.video.players[playerId]['type'] = 'vimeo';
                    window.video.players[playerId]['player'] = Froogaloop(frame);
                    if(video.scrollEventIsEnabled() && window.videoPlayerScrollEventType == 2) {
                        var unstick = $('<span class="unstick ion-close"></span>');
                        $(unstick).on('click', function() {
                            window.video.players[playerId]['player'].api('pause');
                            $(frame).removeClass('is-stuck');
                            $(this).removeClass('is-stuck');
                        });
                        $(unstick).insertAfter(frame);
                    }
                    if(video.scrollEventIsEnabled()) {
                        $(window).scroll(function(e) {
                            if(window.videoPlayerScrollEventType == 1) {
                                $(frame).is(':in-viewport(400)') ? window.video.players[playerId]['player'].api('play') : window.video.players[playerId]['player'].api('pause');
                            } else if(window.videoPlayerScrollEventType == 2) {
                                if($(frame).hasClass('is-stuck')) {
                                    if($(frame).closest('.feed-video-embed-container').is(':in-viewport')) {
                                        $(frame).removeClass('is-stuck');
                                        $(unstick).removeClass('is-stuck');
                                    }
                                } else {
                                    if(window.video.players[playerId]['player'].api('getCurrentTime') > 0 && !window.video.players[playerId]['player'].api('getPaused') && !window.video.players[playerId]['player'].api('getEnded') && window.video.players[playerId]['player'].api('ready') && !$(frame).is(':in-viewport')) {
                                        $('.feed-video-embed-container').not('#' + playerId).find('.unstick.is-stuck').each(function() {
                                            $(this).click();
                                        });
                                        $(frame).addClass('is-stuck');
                                        $(unstick).addClass('is-stuck');
                                    }
                                }
                            }
                        });
                    }
                }
            });
        }/*,

		attachVideoPlayerEventDailyMotion: function() {
			if(typeof video.attachVideoPlayerEventDailyMotionInterval !== 'undefined') {
				DM.init({apiKey: dailymotionAPIKey, status: true, cookie: true});
				clearInterval(video.attachVideoPlayerEventDailyMotionInterval);
			}
			$('.feed-video-embed-container iframe[src*="dailymotion.com/embed/video/"]').each(function() {
				var frame = this;
				var playerId = $(frame).closest('.feed-video-embed-container').attr('id');
				window.video.players[playerId] = [];
				window.video.players[playerId]['type'] = 'dailymotion';
				window.video.players[playerId]['player'] = DM.player(frame, {});
				if(video.scrollEventIsEnabled() && window.videoPlayerScrollEventType == 2) {
					var unstick = $('<span class="unstick ion-close"></span>');
					$(unstick).on('click', function() {
						/*
						 * @todo DailyMotion Video is pause.
						 *
						//window.video.players[playerId]['player'].pause();
						$(frame).removeClass('is-stuck');
						$(this).removeClass('is-stuck');
					});
					$(unstick).insertAfter(frame);
				}
				if(video.scrollEventIsEnabled()) {
					$(window).scroll(function(e) {
						if(window.videoPlayerScrollEventType == 1) {
							$(frame).is(':in-viewport(400)') ? window.video.players[playerId]['player'].play() : window.video.players[playerId]['player'].pause();
						} else if(window.videoPlayerScrollEventType == 2) {
							if($(frame).hasClass('is-stuck')) {
								if($(frame).closest('.feed-video-embed-container').is(':in-viewport')) {
									$(frame).removeClass('is-stuck');
									$(unstick).removeClass('is-stuck');
								}
							} else {

								/*
								 * @todo If DailyMotion Video is playing.
								 *
								if(false) {
									$('.feed-video-embed-container').not('#' + playerId).find('.unstick.is-stuck').each(function() {
										$(this).click();
									});
									$(frame).addClass('is-stuck');
									$(unstick).addClass('is-stuck');
								}
							}
						}
					});
				}
			});
		}*/
    };
}

$(function() {
    video.init();
});