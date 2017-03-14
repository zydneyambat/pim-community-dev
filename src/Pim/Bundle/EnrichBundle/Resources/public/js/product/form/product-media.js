'use strict';
/**
 * Label extension
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @author    Filips Alpe <filips@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
define(
    [
      'pim/form',
      'text!pim/template/product/product-media',
      'pim/user-context',
      'pim/i18n',
      'pim/media-url-generator',
    ],
    function (BaseForm, template, UserContext, i18n, mediaUrlGenerator) {
        return BaseForm.extend({
            template: _.template(template),

            /**
             * When the product gets updated, re-render the media preview
             */
            configure: function () {
                var root = this.getRoot()
                var event = 'pim_enrich:form:entity:post_update'

                this.listenTo(root, event, this.render);
                return BaseForm.prototype.configure.apply(this, arguments);
            },

            /**
             * {@inheritdoc}
             */
            render: function () {
                var mediaUrl = this.getMediaUrl()
                var generatedUrl = mediaUrlGenerator.getMediaShowUrl(mediaUrl, 'thumbnail_small')

                this.$el.html(this.template({
                  generatedUrl: generatedUrl
                }));

                return this;
            },

            /**
             * Get the media url for the first image
             *
             * @return {String}
             */
            getMediaUrl: function () {
                var data = this.getFormData();
                var picture = data.values.picture
                if (picture.length) return picture[0].data.filePath
            }
        });
    }
);
