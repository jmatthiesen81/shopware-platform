import { test } from '@fixtures/AcceptanceTest';
import { isSaaSInstance } from '@fixtures/AcceptanceTest';
import { satisfies } from 'compare-versions';

/**
 * @sw-package fundamentals@after-sales
 */
test('Merchant is able to be guided through the First Run Wizard.', { tag: '@FirstRunWizard' }, async ({
    FRWSalesChannelSelectionPossibility,
    ShopAdmin,
    DefaultSalesChannel,
    AdminFirstRunWizard,
    AdminApiContext,
    InstanceMeta,
}) => {
    test.skip(await isSaaSInstance(AdminApiContext),'Skipping test for the first run wizard, because it is disabled on SaaS instances.');
    // TODO: Meteor fix
    test.skip(satisfies(InstanceMeta.version, '>=6.7'), 'Skipped due to 6.7 expect in the ats npm package');

    await ShopAdmin.goesTo(AdminFirstRunWizard.url());

    // LanguagePack part
    await ShopAdmin.expects(AdminFirstRunWizard.installLanguagePackButton).toBeVisible();
    await ShopAdmin.expects(AdminFirstRunWizard.welcomeText).toBeVisible();
    await ShopAdmin.expects(AdminFirstRunWizard.pluginCardInfo).toBeVisible();
    await AdminFirstRunWizard.nextButton.click();

    // DataImport part
    await ShopAdmin.expects(AdminFirstRunWizard.dataImportHeader).toBeVisible();
    await ShopAdmin.expects(AdminFirstRunWizard.dataImportCard).toHaveCount(2);
    await ShopAdmin.expects(AdminFirstRunWizard.installDemoDataButton).toBeVisible();
    await ShopAdmin.expects(AdminFirstRunWizard.installMigrationAssistantButton).toBeVisible();
    await ShopAdmin.expects(AdminFirstRunWizard.backButton).not.toBeVisible();
    await AdminFirstRunWizard.nextButton.click();

    // Setup default values part
    await ShopAdmin.expects(AdminFirstRunWizard.defaultValuesHeader).toBeVisible();
    const currentSalesChannel = DefaultSalesChannel.salesChannel.name;
    await ShopAdmin.attemptsTo(FRWSalesChannelSelectionPossibility(currentSalesChannel));
    await AdminFirstRunWizard.nextButton.click();

    // Mailer configuration part
    await ShopAdmin.expects(AdminFirstRunWizard.mailerConfigurationHeader).toBeVisible();
    await ShopAdmin.expects(AdminFirstRunWizard.nextButton).toBeDisabled();
    await AdminFirstRunWizard.smtpServerButton.click();
    await AdminFirstRunWizard.nextButton.click();
    await ShopAdmin.expects(AdminFirstRunWizard.smtpServerTitle).toBeVisible();
    await ShopAdmin.expects(AdminFirstRunWizard.smtpServerHostInput).toBeVisible();
    await ShopAdmin.expects(AdminFirstRunWizard.smtpServerPortInput).toBeVisible();
    await ShopAdmin.expects(AdminFirstRunWizard.smtpServerUsernameInput).toBeVisible();
    await ShopAdmin.expects(AdminFirstRunWizard.smtpServerPasswordInput).toBeVisible();
    await ShopAdmin.expects(AdminFirstRunWizard.smtpServerEncryptionInput).toHaveCount(1);
    await ShopAdmin.expects(AdminFirstRunWizard.smtpServerSenderAddressInput).toBeVisible();
    await ShopAdmin.expects(AdminFirstRunWizard.smtpServerDeliveryAddressInput).toBeVisible();
    await ShopAdmin.expects(AdminFirstRunWizard.smtpServerDisableEmailDeliveryCheckbox).toBeVisible();

    await AdminFirstRunWizard.configureLaterButton.click();

    // PayPal setup part
    await ShopAdmin.expects(AdminFirstRunWizard.payPalSetupHeader).toBeVisible();
    await ShopAdmin.expects(AdminFirstRunWizard.payPalInfoCard).toBeVisible();
    await ShopAdmin.expects(AdminFirstRunWizard.payPalPaymethods).toHaveCount(4);
    await AdminFirstRunWizard.skipButton.click();

    // Extensions part
    await ShopAdmin.expects(AdminFirstRunWizard.extensionsHeader).toBeVisible();
    await AdminFirstRunWizard.germanRegionSelector.click();
    await AdminFirstRunWizard.toolsSelector.click();
    await ShopAdmin.expects(AdminFirstRunWizard.toolsRecommendedPlugin.first()).toContainText('Migration Assistant');
    await ShopAdmin.expects(AdminFirstRunWizard.recommendationHeader).toBeVisible()
    await AdminFirstRunWizard.nextButton.click();

    // Shopware account part
    await ShopAdmin.expects(AdminFirstRunWizard.shopwareAccountHeader).toBeVisible();
    await ShopAdmin.expects(AdminFirstRunWizard.emailAddressInputField).toBeVisible();
    await ShopAdmin.expects(AdminFirstRunWizard.passwordInputField).toBeVisible();
    await ShopAdmin.expects(AdminFirstRunWizard.forgotPasswordLink).toBeVisible();
    await ShopAdmin.expects(AdminFirstRunWizard.nextButton).toBeVisible();
    await AdminFirstRunWizard.skipButton.click();

    // Shopware store part
    await ShopAdmin.expects(AdminFirstRunWizard.shopwareStoreHeader).toBeVisible({ timeout: 120000 });
    await ShopAdmin.expects(AdminFirstRunWizard.extensionStoreHeading).toBeVisible({ timeout: 120000 });
    await AdminFirstRunWizard.skipButton.click();

    // Finish
    await ShopAdmin.expects(AdminFirstRunWizard.frwSuccessText).toBeVisible();
    await ShopAdmin.expects(AdminFirstRunWizard.documentationLink).toBeVisible();
    await ShopAdmin.expects(AdminFirstRunWizard.forumLink).toBeVisible();
    await ShopAdmin.expects(AdminFirstRunWizard.roadmapLink).toBeVisible();
    await AdminFirstRunWizard.finishButton.click();
});
