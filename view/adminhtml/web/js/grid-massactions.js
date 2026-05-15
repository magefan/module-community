/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

define([
    'Magento_Ui/js/grid/tree-massactions',
    'underscore',
    'mageUtils'
], function (TreeMassactions, _, utils) {
    'use strict';

    return TreeMassactions.extend({

        /**
         * Posts selected IDs as `id[]` instead of `selected[]`.
         *
         * @param {Object} action
         * @param {Object} data
         */
        defaultCallback: function (action, data) {
            var itemsType = data.excludeMode ? 'excluded' : 'selected',
                selections = {};

            selections['id'] = data[itemsType];

            if (!selections['id'].length) {
                selections['id'] = false;
            }

            _.extend(selections, data.params || {});

            utils.submit({
                url: action.url,
                data: selections
            });
        }
    });
});