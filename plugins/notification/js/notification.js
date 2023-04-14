function show_notification_dropdown() {
    var dropdown = $(".notifications-dropdown");
    var indicator = dropdown.find('#notification-dropdown-indicator');
    var content = dropdown.find('.notification-dropdown-result-container');
    if(dropdown.css('display') == 'none') {
        dropdown.fadeIn();
        indicator.show();
        $.ajax({
            url: baseUrl + 'notification/load/latest?csrf_token=' + requestToken,
            success: function(data) {
                content.html(data);
                indicator.hide();
                var counter = $('#notification-dropdown-container > a > span');
                counter.remove();
                reloadInits();
            }
        })
    } else {
        dropdown.fadeOut();
    }
    $(document).click(function(e) {
        if(!$(e.target).closest("#notification-dropdown-container").length) dropdown.hide();
    });
    return false;
}

function process_notification_mark(id) {
    var c = $("#notification-" + id);
    var b = c.find('.mark-button');
    var status = b.attr('data-status');
    var lRead = b.data('read');
    var lMark = b.data('mark');
    var type = (status == '0') ? 1 : 0;
    $.ajax({
        url: baseUrl + 'notification/mark?id=' + id + '&type=' + type + '&csrf_token=' + requestToken,
    });
    if(status == 0) {
        c.removeClass("notification-unread");
        b.attr('title', lRead).attr('data-status', type);
    } else {
        c.addClass("notification-unread");
        b.attr('title', lMark).attr('data-status', type);
    }
    return false;
}

function delete_notification(id) {
    var c = $("#notification-" + id);
    c.fadeOut();
    $('div[id=notification-' + id + ']').fadeOut();
    //$('.notifications-dropdown').show();
    $.ajax({
        url: baseUrl + 'notification/delete?id=' + id + '&csrf_token=' + requestToken,
    });
    return false;
}

function push_notification(type, d) {
    if(type == 'notification') {
        var notyCounts = 0;
        var a = $("#notification-dropdown-container > a");
        if(!a.find('span').length) {
            a.append('<span class="count" style="display:none"></span>')
        }
        var span = a.find('span');
        var nIds = '';
        $.each(d, function(pushId, nId) {
            if(!Pusher.hasPushId(pushId)) {
                Pusher.addPushId(pushId);
                nIds += (nIds) ? ',' + nId : nId;
            }
            notyCounts += 1;
        });

        if(notyCounts > 0) {
            span.html(notyCounts).fadeIn();
            Pusher.addCount(notyCounts);
        } else {
            span.remove();
        }

        a.click(function() {
            Pusher.removeCount(notyCounts);
            span.hide();
        });
        if(nIds) {
            $.ajax({
                url: baseUrl + 'notification/preload?csrf_token=' + requestToken,
                data: {ids: nIds},
                success: function(data) {
                    var c = $(".notification-dropdown-result-container");
                    c.prepend(data);
                    if(data) {
                        initNotificationPopup(data);
                    }
                    reloadInits();
                }
            })
        }
    }
}

Pusher.addHook('push_notification');

function initNotificationPopup(data) {

    $("#notification-popup").find('#content').html(data);
    $("#notification-popup").fadeIn();
    setTimeout(function() {
        $("#notification-popup").fadeOut(300);
    }, 5000);

}

function closeNotificationpopup() {
    $("#notification-popup").fadeOut(300);
    return false;
}

$(function() {
    $(document).on('mouseover', '.notification', function() {
        $(this).find('.actions').show();
    });

    $(document).on('mouseout', '.notification', function() {
        $(this).find('.actions').hide();
    });
});

if(typeof notification === 'undefined') {
    notification = {
        init: function() {
            notification.firebase.init();
            notification.users.init();
            notification.serviceWorker.init();
        },

        firebase: {
            config : {
                apiKey: firebaseAPIKey,
                authDomain: firebaseAuthDomain,
                databaseURL: firebaseDatabaseURL,
                projectId: firebaseProjectId,
                storageBucket: firebaseStorageBucket,
                messagingSenderId: firebaseMessagingSenderId
            },

            init: function() {
                firebase.initializeApp(notification.firebase.config);
            }
        },

        users: {
            database: null,

            snapshots: [],

            init: function() {
                if(!(notification.firebase.config.databaseURL + '').length) {
                    return false;
                }
                notification.users.database = firebase.database();
                // notification.users.database.enableLogging(true, true);
                if(typeof userID === 'number') {
                    var user = {id: userID, online: true};
                    notification.users.update(user, function(error) {
                        if(!error) {
                            notification.users.database.ref('users/' + user.id).onDisconnect().update({online: false});
                            notification.users.database.ref('.info/connected').on('value', function(snapshot) {
                                var online = snapshot.val();
                                notification.users.database.ref('users/' + user.id).update({online: online});
                            });
                            if(typeof friends === 'object') {
                                friends.forEach(function(id) {
                                    notification.users.database.ref('users/' + id).on('value', function(snapshot) {
                                        var user = snapshot.val();
                                        var previousUser = notification.users.snapshots[id] ? notification.users.snapshots[user.id] : user;
                                        if(user) {
                                            if(user.online !== previousUser.online) {
                                                user.online ? notification.users.onConnect(user) : notification.users.onDisconnect(user);
                                            }
                                        }
                                        notification.users.snapshots[id] = user;
                                    });
                                });
                            }
                        }
                    });
                }
            },

            update: function (user, callback) {
                notification.users.database.ref('users/' + user.id).once('value', function(snapshot) {
                    var oldUser = snapshot.val();
                    if(oldUser) {
                        notification.users.database.ref('users/' + user.id).update(user, callback);
                    } else {
                        notification.users.database.ref('users/' + user.id).set(user, callback);
                    }
                });
            },

            onConnect: function (user) {
                Hook.fire('friend.connected', null, [user]);
            },

            onDisconnect: function (user) {
                Hook.fire('friend.disconnected', null, [user]);
            }
        },

        serviceWorker: {
            registration: null,

            init: function() {
                notification.serviceWorker.register();
            },

            register: function() {
                navigator.serviceWorker.register(baseUrl + 'nsw.js').then(function(registration) {
                    console.log('ServiceWorker registration successful with scope: ', registration.scope);
                    notification.serviceWorker.registration = registration;
                    notification.push.init();
                }, function(error) {
                    console.log('ServiceWorker registration failed: ', error);
                    notification.push.init();
                });
            }
        },

        push: {
            driver: 'ajax',

            init: function() {
                if(typeof pushDriver !== 'undefined') {
                    notification.push.driver = pushDriver;
                }
                Pusher.addHook('notification.push.hook');
                for(var i in notification.push.pushers) {
                    notification.push.pushers[i].init();
                }
            },

            onMessage: function(data) {
                if(data) {
                    var userID = data.userid;
                    var types = data.types;
                    var seen = data.seen;
                    if(seen === 0) {
                        Pusher.onAlert();
                    }
                    Pusher.setUser(userID);
                    if(types) {
                        types = typeof types === 'string' ? JSON.parse(types) : types;
                        for(var i in types) {
                            Pusher.run(i, types[i]);
                        }
                    }
                    Pusher.finish();
                }
            },

            hook: function() {

            },

            pushers: {
                poll: {
                    init: function() {
                        if(notification.push.driver === 'ajax' && loggedIn) {
                            notification.push.pushers.poll.check();
                        }
                    },

                    check: function() {
                        var headers = new Headers();
                        headers.append('pragma', 'no-cache');
                        headers.append('cache-control', 'no-cache');
                        fetch(baseUrl + 'ajax/push/check?csrf_token=' + requestToken, {
                            method: 'GET',
                            credentials: 'same-origin',
                            headers: headers
                        }).then(function(response) {
                            if (response.status === 200) {
                                response.json().then(function(data) {
                                    // alert('AJAX Push Message Arrive in Browser');
                                    notification.push.onMessage(data);
                                    setTimeout(function() {
                                        if(loggedIn) {
                                            notification.push.pushers.poll.check()
                                        }
                                    }, ajaxInterval);
                                });
                            } else {
                                setTimeout(function() {
                                    if(loggedIn) {
                                        notification.push.pushers.poll.check()
                                    }
                                }, ajaxInterval);
                            }
                        }).catch(function(error) {
                            setTimeout(function () {
                                if (loggedIn) {
                                    notification.push.pushers.poll.check()
                                }
                            }, ajaxInterval);
                        });
                    }
                },

                FCM: {
                    messaging: null,
                    token: null,
                    permission: false,

                    init: function() {
                        if(!(notification.firebase.config.messagingSenderId + '').length) {
                            return false;
                        }
                        notification.push.pushers.FCM.messaging = firebase.messaging();
                        notification.push.pushers.FCM.messaging.usePublicVapidKey(firebasePublicVapidKey);
                        if(notification.serviceWorker.registration) {
                            notification.push.pushers.FCM.messaging.useServiceWorker(notification.serviceWorker.registration);
                            navigator.serviceWorker.addEventListener('message', function (e) {
                                // alert('FCM Push Message Recieved from SW');
                                var payload = e.data['firebase-messaging-msg-type'] == 'push-msg-received' ? e.data['firebase-messaging-msg-data'] : e.data;
                                var data = JSON.parse(payload.data.pushes);
                                notification.push.onMessage(data);
                            });
                        }
                        notification.push.pushers.FCM.messaging.requestPermission().then(function() {
                            notification.push.pushers.FCM.permission = true;
                            notification.push.pushers.FCM.setToken();
                            notification.push.pushers.FCM.messaging.onTokenRefresh(function() {
                                notification.push.pushers.FCM.messaging.getToken().then(function(token) {
                                    console.log('Token refreshed.');
                                    notification.push.pushers.FCM.setToken(token);
                                }).catch(function(error) {
                                    console.log('Unable to retrieve refreshed token ', error);
                                });
                            });
                            notification.push.pushers.FCM.messaging.onMessage(function(payload) {
                                var data = JSON.parse(payload.data.pushes);
                                // alert('FCM Push Message Arrive in Browser');
                                notification.push.onMessage(data);
                            });
                        }).catch(function(error) {
                            if(error.code === 'messaging/permission-blocked') {
                                console.log('Unable to get FCM permission to notify. Falling back to AJAX Polling');
                                notification.push.driver = 'ajax';
                                notification.push.pushers.poll.init();
                            } else {
                                console.log('Unable to get permission to notify.', error);
                            }
                        });
                    },

                    setToken: function(token) {
                        if(token) {
                            notification.push.pushers.FCM.token = token;
                            notification.push.pushers.FCM.updateServerToken();
                        } else {
                            notification.push.pushers.FCM.messaging.getToken().then(function(token) {
                                if(token) {
                                    notification.push.pushers.FCM.token = token;
                                    notification.push.pushers.FCM.updateServerToken();
                                } else {
                                    console.log('No Instance ID token available. Request permission to generate one.');
                                }
                            }).catch(function(error) {
                                console.log('An error occurred while retrieving token. ', error);
                            });
                        }
                    },

                    updateServerToken: function(token) {
                        token = token || notification.push.pushers.FCM.token;
                        $.ajax({
                            url: baseUrl + 'notification/fcm/token/update?token=' + token + '&csrf_token=' + requestToken,
                            success: function(response) {

                            }
                        });
                    }
                }
            }
        },

        pop: {
            options: {
                title: '',
                body: '',
                icon: '',
                link: '',
                direction: '',
                vibrate: [200, 100, 200, 100, 200, 100, 200],
                tag: ''
            },

            set: function(options) {
                notification.pop.options = options;
            },

            create: function(options) {
                if(options) {
                    notification.pop.set(options);
                }
                notification.pop.options['onclick'] = function() {
                    open(notification.pop.options.link);
                };
            },

            notify: function(options) {
                if(options) {
                    notification.pop.set(options);
                }
                if(typeof Notification === 'undefined') {
                    console.log('This browser does not support push notification');
                } else if(Notification.permission === 'granted') {
                    notification.pop.create();
                } else if(Notification.permission !== 'denied') {
                    Notification.requestPermission(function(permission) {
                        if(!('permission' in Notification)) {
                            Notification.permission = permission;
                        }
                        if(permission === 'granted') {
                            notification.pop.create();
                        }
                    });
                }
            }
        }
    };
}

$(function() {
    notification.init();
});