import template from './sw-media-modal-replace.html.twig';
import './sw-media-modal-replace.scss';

const { Mixin } = Shopware;

/**
 * @status ready
 * @description The <u>sw-media-modal-replace</u> component is used to let the user upload a new image for an
 * existing media object.
 * @sw-package discovery
 * @example-type code-only
 * @component-example
 * <sw-media-modal-replace itemToReplace="item">
 * </sw-media-modal-replace>
 */
// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    inject: [
        'mediaService',
        'repositoryFactory',
    ],

    emits: [
        'media-replace-modal-close',
        'media-replace-modal-item-replaced',
    ],

    mixins: [
        Mixin.getByName('notification'),
    ],

    props: {
        itemToReplace: {
            type: Object,
            required: false,
            default: null,
        },
    },

    data() {
        return {
            uploadTag: null,
            isUploadDataSet: false,
            newFileExtension: '',
        };
    },

    methods: {
        onNewUpload({ data }) {
            this.isUploadDataSet = true;

            const newFileExtension = data[0].extension;
            const oldFileExtension = this.itemToReplace.fileExtension;

            if (newFileExtension !== oldFileExtension) {
                this.newFileExtension = newFileExtension;
            }
        },

        emitCloseReplaceModal() {
            this.$emit('media-replace-modal-close');
        },

        async replaceMediaItem() {
            this.itemToReplace.isLoading = true;
            const previousName = this.itemToReplace.fileName;

            await this.mediaService.runUploads(this.itemToReplace.id);
            await this.mediaService.renameMedia(this.itemToReplace.id, previousName);

            this.itemToReplace.isLoading = false;
            this.$emit('media-replace-modal-item-replaced');
        },
    },
};
