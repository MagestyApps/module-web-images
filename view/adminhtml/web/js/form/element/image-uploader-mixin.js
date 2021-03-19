/**
 * Copyright © 2021 MagestyApps. All rights reserved.
 * See LICENSE.txt for license details.
 */

define(function () {
    'use strict';

    return function (imageUploader) {
        return imageUploader.extend({
            initialize: function () {
                this._super();

                this.allowedExtensions += ' svg';
            }
        });
    };
});
