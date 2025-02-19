/**
 * @sw-package fundamentals@framework
 */
import { email } from 'src/core/service/validation.service';
import { KEY_USER_SEARCH_PREFERENCE } from 'src/app/service/search-ranking.service';
import template from './sw-profile-index.html.twig';
import '../../store/sw-profile.store';

const { Component, Mixin, Store } = Shopware;
const { Criteria } = Shopware.Data;
const { mapPropertyErrors } = Component.getComponentHelper();

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    inject: [
        'userService',
        'loginService',
        'mediaDefaultFolderService',
        'repositoryFactory',
        'acl',
        'searchPreferencesService',
        'searchRankingService',
        'userConfigService',
    ],

    mixins: [
        Mixin.getByName('notification'),
    ],

    data() {
        return {
            user: { username: '', email: '' },
            languages: [],
            imageSize: 140,
            newPassword: null,
            newPasswordConfirm: null,
            confirmPassword: null,
            avatarMediaItem: null,
            uploadTag: 'sw-profile-upload-tag',
            isLoading: false,
            isUserLoading: true,
            isSaveSuccessful: false,
            confirmPasswordModal: false,
            mediaDefaultFolderId: null,
            showMediaModal: false,
            timezoneOptions: [],
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        searchPreferences: () => Shopware.Store.get('swProfile').searchPreferences,

        ...mapPropertyErrors('user', [
            'email',
            'timeZone',
        ]),

        userSearchPreferences: {
            get() {
                return Store.get('swProfile').userSearchPreferences;
            },
            set(userSearchPreferences) {
                Store.get('swProfile').userSearchPreferences = userSearchPreferences;
            },
        },

        isDisabled() {
            return true;
        },

        userRepository() {
            return this.repositoryFactory.create('user');
        },

        languageRepository() {
            return this.repositoryFactory.create('language');
        },

        localeRepository() {
            return this.repositoryFactory.create('locale');
        },

        mediaRepository() {
            return this.repositoryFactory.create('media');
        },

        languageId() {
            return Shopware.Store.get('session').languageId;
        },
    },

    watch: {
        $route(newValue) {
            if (!newValue || newValue.name === 'sw.profile.index.searchPreferences') {
                return;
            }

            this.resetGeneralData();
        },

        'user.avatarMedia.id'() {
            if (!this.user.avatarMedia?.id) {
                return;
            }

            if (!this.acl.can('media.creator')) {
                return;
            }

            this.setMediaItem({ targetId: this.user.avatarMedia.id });
        },

        languageId() {
            this.createdComponent();
        },
    },

    created() {
        this.createdComponent();
    },

    beforeMount() {
        this.beforeMountComponent();
    },

    methods: {
        createdComponent() {
            this.isUserLoading = true;

            const languagePromise = new Promise((resolve) => {
                resolve(this.languageId);
            });

            this.userPromise = this.getUserData();
            this.timezoneOptions = Shopware.Service('timezoneService').getTimezoneOptions();

            const promises = [
                languagePromise,
                this.userPromise,
            ];

            if (this.acl.can('media.creator')) {
                this.getMediaDefaultFolderId()
                    .then((id) => {
                        this.mediaDefaultFolderId = id;
                    })
                    .catch(() => {
                        this.mediaDefaultFolderId = null;
                    });
            }

            Promise.all(promises)
                .then(() => {
                    this.loadLanguages();
                })
                .then(() => {
                    this.isUserLoading = false;
                });
        },

        beforeMountComponent() {
            this.userPromise.then((user) => {
                this.user = user;

                if (this.user.avatarId) {
                    this.loadMediaItem(this.user.avatarId);
                }
            });
        },

        loadLanguages() {
            const factoryContainer = Shopware.Application.getContainer('factory');
            const localeFactory = factoryContainer.locale;
            const registeredLocales = Array.from(localeFactory.getLocaleRegistry().keys());

            const languageCriteria = new Criteria(1, 500);
            languageCriteria.addAssociation('locale');
            languageCriteria.addSorting(Criteria.sort('locale.name', 'ASC'));
            languageCriteria.addSorting(Criteria.sort('locale.territory', 'ASC'));
            languageCriteria.addFilter(Criteria.equalsAny('locale.code', registeredLocales));

            return this.languageRepository.search(languageCriteria).then((result) => {
                this.languages = [];
                const localeIds = [];
                let fallbackId = '';

                result.forEach((lang) => {
                    lang.customLabel = `${lang.locale.translated.name} (${lang.locale.translated.territory})`;
                    this.languages.push(lang);

                    localeIds.push(lang.localeId);
                    if (lang.locale.code === Shopware.Context.app.fallbackLocale) {
                        fallbackId = lang.localeId;
                    }
                });

                if (!localeIds.includes(this.user.localeId)) {
                    this.user.localeId = fallbackId;
                }
                this.isUserLoading = false;

                return this.languages;
            });
        },

        async getUserData() {
            const routeUser = this.$route.params.user;
            if (routeUser) {
                return this.userRepository.get(routeUser.id);
            }

            const user = await this.userService.getUser();
            return this.userRepository.get(user.data.id);
        },

        resetGeneralData() {
            this.newPassword = null;
            this.newPasswordConfirm = null;

            this.createdComponent();
            this.beforeMountComponent();
        },

        async saveFinish() {
            this.isSaveSuccessful = false;
            this.user = await this.getUserData();
        },

        onSave() {
            if (this.$route.name === 'sw.profile.index.searchPreferences') {
                this.saveUserSearchPreferences();

                return;
            }

            if (this.checkEmail() === false) {
                return;
            }

            const passwordCheck = this.checkPassword();

            if (passwordCheck === null || passwordCheck === true) {
                this.confirmPasswordModal = true;
            }
        },

        checkEmail() {
            if (!this.user.email || !email(this.user.email)) {
                this.createErrorMessage(this.$tc('sw-profile.index.notificationInvalidEmailErrorMessage'));

                return false;
            }
            return true;
        },

        checkPassword() {
            if (this.newPassword && this.newPassword.length > 0) {
                if (this.newPassword !== this.newPasswordConfirm) {
                    this.createErrorMessage(this.$tc('sw-profile.index.notificationPasswordErrorMessage'));
                    return false;
                }

                this.user.password = this.newPassword;

                return true;
            }

            return null;
        },

        createErrorMessage(errorMessage) {
            this.createNotificationError({
                message: errorMessage,
            });
        },

        saveUser(context) {
            if (!this.acl.can('user:editor')) {
                const changes = this.userRepository.getSyncChangeset([
                    this.user,
                ]);
                delete changes.changeset[0].changes.id;

                this.userService
                    .updateUser(changes.changeset[0].changes)
                    .then(async () => {
                        await this.updateCurrentUser();

                        this.isLoading = false;
                        this.isSaveSuccessful = true;

                        Shopware.Service('localeHelper').setLocaleWithId(this.user.localeId);
                    })
                    .catch((error) => {
                        Shopware.Store.get('error').addApiError({
                            expression: `user.${this.user?.id}.password`,
                            error: new Shopware.Classes.ShopwareError(error.response.data.errors[0]),
                        });
                        this.createNotificationError({
                            message: this.$tc('sw-profile.index.notificationSaveErrorMessage'),
                        });
                        this.isLoading = false;
                        this.isSaveSuccessful = false;
                    });

                return;
            }

            this.userRepository
                .save(this.user, context)
                .then(async () => {
                    await this.updateCurrentUser();
                    Shopware.Service('localeHelper').setLocaleWithId(this.user.localeId);

                    if (this.newPassword) {
                        // re-issue a valid jwt token, as all user tokens were invalidated on password change
                        this.loginService
                            .loginByUsername(this.user.username, this.newPassword)
                            .then(() => {
                                this.isSaveSuccessful = true;
                            })
                            .catch(() => {
                                this.handleUserSaveError();
                            })
                            .finally(() => {
                                this.isLoading = false;
                            });
                    } else {
                        this.isLoading = false;
                        this.isSaveSuccessful = true;
                    }

                    this.confirmPassword = '';
                    this.newPassword = '';
                    this.newPasswordConfirm = '';
                })
                .catch(() => {
                    this.handleUserSaveError();
                    this.isLoading = false;
                    this.isSaveSuccessful = false;
                });
        },

        updateCurrentUser() {
            return this.userService.getUser().then((response) => {
                const data = response.data;
                delete data.password;

                return Shopware.Store.get('session').setCurrentUser(data);
            });
        },

        loadMediaItem(targetId) {
            this.mediaRepository.get(targetId).then((media) => {
                this.avatarMediaItem = media;
            });
        },

        setMediaItem({ targetId }) {
            this.user.avatarId = targetId;
            this.loadMediaItem(targetId);
        },

        onDropMedia(mediaItem) {
            this.setMediaItem({ targetId: mediaItem.id });
        },

        onCloseConfirmPasswordModal() {
            this.confirmPassword = '';
            this.confirmPasswordModal = false;
        },

        onUnlinkAvatar() {
            this.avatarMediaItem = null;
            this.user.avatarId = null;
        },

        openMediaModal() {
            this.showMediaModal = true;
        },

        handleUserSaveError() {
            if (this.$route.name.includes('sw.profile.index')) {
                this.createNotificationError({
                    message: this.$tc('sw-profile.index.notificationSaveErrorMessage'),
                });
            }
            this.isLoading = false;
        },

        onChangeNewPassword(newPassword) {
            this.newPassword = newPassword;
        },

        onChangeNewPasswordConfirm(newPasswordConfirm) {
            this.newPasswordConfirm = newPasswordConfirm;
        },

        onMediaSelectionChange([mediaEntity]) {
            this.avatarMediaItem = mediaEntity;
            this.user.avatarId = mediaEntity.id;
        },

        getMediaDefaultFolderId() {
            return this.mediaDefaultFolderService.getDefaultFolderId('user');
        },

        saveUserSearchPreferences() {
            // eslint-disable-next-line max-len
            this.userSearchPreferences =
                this.userSearchPreferences ?? this.searchPreferencesService.createUserSearchPreferences();
            this.userSearchPreferences.value = this.searchPreferences.map(({ entityName, _searchable, fields }) => {
                return {
                    [entityName]: {
                        _searchable,
                        ...this.searchPreferencesService.processSearchPreferencesFields(fields),
                    },
                };
            });

            this.searchRankingService.clearCacheUserSearchConfiguration();

            this.isLoading = true;
            this.isSaveSuccessful = false;
            return this.userConfigService
                .upsert({
                    [KEY_USER_SEARCH_PREFERENCE]: this.userSearchPreferences.value,
                })
                .then(() => {
                    this.isLoading = false;
                    this.isSaveSuccessful = true;
                })
                .catch((error) => {
                    this.isLoading = false;
                    this.isSaveSuccessful = false;
                    this.createNotificationError({ message: error.message });
                });
        },

        onVerifyPasswordFinished(context) {
            this.confirmPasswordModal = false;
            this.isSaveSuccessful = false;
            this.isLoading = true;

            this.saveUser(context);
        },
    },
};
