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
