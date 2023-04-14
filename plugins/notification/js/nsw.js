if(typeof notification === 'undefined') {
    notification = {
        init: function() {
            notification.push.init();
        },

        push: {
            driver: 'ajax',

            init: function() {
                if(typeof pushDriver !== 'undefined') {
                    notification.push.driver = pushDriver;
                }
                for(let i in notification.push.pushers) {
                    notification.push.pushers[i].init();
                }
            },

            onMessage: function(data) {
                if(data) {
                    let seen = data.swseen;
                    let notifications = data.notifications;
                    if(seen === 0) {
                        if(notifications) {
                            notifications = typeof notifications === 'string' ? JSON.parse(notifications) : notifications;
                            function notify(notifications) {
                                let optionsArray = [];
                                for(let type in notifications) {
                                    if(notifications[type]) {
                                        for(let subPushOrId in notifications[type]) {
                                            if(typeof notifications[type][subPushOrId].status !== 'undefined') {
                                                if(notifications[type][subPushOrId].status) {if(notifications[type][subPushOrId].options.title.length) {
                                                        optionsArray.push(notifications[type][subPushOrId].options);
                                                    }
                                                    if(notifications[type][subPushOrId].notifications) {
                                                        notify(notifications[type][subPushOrId].notifications);
                                                    }
                                                }
                                            } else {
                                                for(let id in notifications[type][subPushOrId]) {
                                                    if(notifications[type][subPushOrId][id].status) {
                                                        if(notifications[type][subPushOrId][id].options.title.length) {
                                                            optionsArray.push(notifications[type][subPushOrId][id].options);
                                                        }
                                                        if(notifications[type][subPushOrId][id].notifications && notifications[type][subPushOrId][id].notifications.length) {
                                                            notify(notifications[type][subPushOrId][id].notifications);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                for(let i in optionsArray) {
                                    notification.pop.notify(optionsArray[i]);
                                }
                            }
                            notify(notifications);
                        }
                    }
                }
            },

            pushers: {
                poll: {
                    init: function() {
                        if(notification.push.driver === 'ajax' && loggedIn) {
                            notification.push.pushers.poll.check();
                        }
                    },

                    check: function() {
                        let headers = new Headers();
                        headers.append('pragma', 'no-cache');
                        headers.append('cache-control', 'no-cache');
                        fetch('./ajax/push/check?csrf_token=' + requestToken + '&sw=1', {
                            method: 'GET',
                            credentials: 'same-origin',
                            headers: headers
                        }).then(function(response) {
                            if (response.status === 200) {
                                response.json().then(function(data) {
                                    //console.log('AJAX Push Message Arrive in SW');
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
                        let config = {
                            apiKey: firebaseAPIKey,
                            authDomain: firebaseAuthDomain,
                            databaseURL: firebaseDatabaseURL,
                            projectId: firebaseProjectId,
                            storageBucket: firebaseStorageBucket,
                            messagingSenderId: firebaseMessagingSenderId
                        };
                        if(!(config.messagingSenderId + '').length) {
                            return false;
                        }
                        firebase.initializeApp(config);
                        notification.push.pushers.FCM.messaging = firebase.messaging();
                        notification.push.pushers.FCM.permission = true;
                        notification.push.pushers.FCM.setToken();
                        notification.push.pushers.FCM.messaging.setBackgroundMessageHandler(function(payload) {
                            var tabActive = false;
                            self.clients.matchAll({includeUncontrolled: true, type: 'window'}).then((clients) => {
                                clients.forEach(function(client) {
                                    if(client.visibilityState === 'visible') {
                                        tabActive = true;
                                    } else {
                                        console.log(client.visibilityState);
                                    }
                                    client.postMessage(payload);
                                });
                            });
                            if(!tabActive) {
                                var data = JSON.parse(payload.data.pushes);
                                notification.push.onMessage(data);
                                //console.log('FCM Push Message Arrive in SW and No Tab Active');
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
                                if(error.code === 'messaging/notifications-blocked') {
                                    console.log('Service Worker unable to get FCM permission to notify. Falling back to AJAX Polling');
                                    notification.push.driver = 'ajax';
                                    notification.push.pushers.poll.init();
                                } else {
                                    console.log('An error occurred while retrieving token. ', error);
                                }
                            });
                        }
                    },

                    updateServerToken: function(token) {
                        token = token || notification.push.pushers.FCM.token;
                        fetch('./notification/fcm/token/update?token=' + token + '&csrf_token=' + requestToken, {
                            method: 'GET',
                            credentials: 'same-origin'
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
                return self.registration.showNotification(notification.pop.options.title, notification.pop.options);
            },

            notify: function(options) {
                if(options) {
                    notification.pop.set(options);
                }
                if(typeof Notification === 'undefined') {
                    console.log('This browser does not support push notification');
                } else if(Notification.permission === "granted") {
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

self.addEventListener('notificationclick', function(event) {
    let url = event.currentTarget.notification.pop.options.link;
    event.notification.close();
    event.waitUntil(
        clients.matchAll({type: 'window'}).then( windowClients => {
            for (let i = 0; i < windowClients.length; i++) {
                let client = windowClients[i];
                if (client.url === url && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});

self.addEventListener('install', function(event) {
    self.skipWaiting();
});

self.addEventListener('activate', function(event) {
    if(enablePushNotification) {
        notification.push.init();
    }
});