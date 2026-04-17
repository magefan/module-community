/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

define([
    'uiComponent'
], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magefan_Community/config/menu',
            templates: {
                items: 'Magefan_Community/config/items'
            },
            items: []
        },

        /**
         * Is tab active
         *
         * @param {Array} extensions
         * @returns {Boolean}
         */
        isActive: function (extensions) {
            return extensions.some(function (item) {
                return item.is_active;
            });
        }
    });
});
