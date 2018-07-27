/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Polyfill for local storage and session storage for old browsers and with enabled private mode.
 *
 * Emulates behavior of the native local storage and session storage. Adds ability to use "getItem", "setItem",
 * "removeItem" methods.
 */
try {
    if (!window.localStorage || !window.sessionStorage) {
        throw new Error();
    }

    localStorage.setItem('storage_test', 1);
    localStorage.removeItem('storage_test');
} catch (e) {
    (function () {
        'use strict';

        /**
         * Returns a storage object to shim local or sessionStorage
         * @param {String} type - either 'local' or 'session'
         */
        var Storage = function (type) {
            var data;

            /**
             * Creates a cookie
             * @param {String} name
             * @param {String} value
             * @param {Integer} days
             */
            function createCookie(name, value, days) {
                var date, expires;

                if (days) {
                    date = new Date();
                    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                    expires = '; expires=' + date.toGMTString();
                } else {
                    expires = '';
                }
                document.cookie = name + '=' + value + expires + '; path=/';
            }

            /**
             * Reads value of a cookie
             * @param {String} name
             */
            function readCookie(name) {
                var nameEQ = name + '=',
                    ca = document.cookie.split(';'),
                    i = 0,
                    c;

                for (i = 0; i < ca.length; i++) {
                    c = ca[i];

                    while (c.charAt(0) === ' ') {
                        c = c.substring(1, c.length);
                    }

                    if (c.indexOf(nameEQ) === 0) {
                        return c.substring(nameEQ.length, c.length);
                    }
                }

                return null;
            }

            /**
             * Sets storage cookie to a data object
             * @param {Object} dataObject
             */
            function setData(data) {
                data = encodeURIComponent(JSON.stringify(data));
                createCookie(type === 'session' ? getSessionName() : 'localStorage', data, 365);
            }

            /**
             * Clears value of cookie data
             */
            function clearData() {
                createCookie(type === 'session' ? getSessionName() : 'localStorage', '', 365);
            }

            /**
             * @returns value of cookie data
             */
            function getData() {
                var data = type === 'session' ? readCookie(getSessionName()) : readCookie('localStorage');
                return data ? JSON.parse(decodeURIComponent(data)) : {};
            }

            /**
             * Returns cookie name
             */
            function getSessionName() {
                if (!window.name) {
                    window.name = new Date().getTime();
                }

                return 'sessionStorage' + window.name;
            }

            data = getData();

            return {
                length: 0,

                /**
                 * Clears data from storage
                 */
                clear: function () {
                    data = {};
                    this.length = 0;
                    clearData();
                },

                /**
                 * Gets an item from storage
                 * @param {String} key
                 */
                getItem: function (key) {
                    return data[key] === undefined ? null : data[key];
                },

                /**
                 * Gets an item by index from storage
                 * @param {Integer} i
                 */
                key: function (i) {
                    var ctr = 0,
                        k;

                    for (k in data) {

                        if (data.hasOwnProperty(k)) {

                            // eslint-disable-next-line max-depth
                            if (ctr.toString() === i.toString()) {
                                return k;
                            }
                            ctr++;
                        }
                    }

                    return null;
                },

                /**
                 * Removes an item from storage
                 * @param {String} key
                 */
                removeItem: function (key) {
                    delete data[key];
                    this.length--;
                    setData(data);
                },

                /**
                 * Sets an item from storage
                 * @param {String} key
                 * @param {String} value
                 */
                setItem: function (key, value) {
                    data[key] = value.toString();
                    this.length++;
                    setData(data);
                }
            };
        };

        try {
            window.localStorage.__proto__ = window.localStorage = new Storage('local');
            window.sessionStorage.__proto__ = window.sessionStorage = new Storage('session');
        } catch (e) {
            window._localStorage = new Storage('local');
            window._sessionStorage = new Storage('session');
        }
    })();
}
