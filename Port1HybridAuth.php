<?php
namespace Port1HybridAuth;

/**
 * Copyright (C) portrino GmbH - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by André Wuttig <wuttig@portrino.de>, portrino GmbH
 */

use Doctrine\Common\Collections\ArrayCollection;
use Port1HybridAuth\Service\AbstractAuthenticationService;
use Port1HybridAuth\Service\AuthenticationService\Typo3AuthenticationService;
use Port1HybridAuth\Service\ConfigurationServiceInterface;
use Port1HybridAuth\Service\SingleSignOnService;
use Port1HybridAuth\Service\SingleSignOnServiceInterface;
use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Theme\LessDefinition;
use Shopware\Models\Customer\Customer;

use Exception;
use Shopware\Models\Shop\Shop;

/**
 * Class Port1HybridAuth
 * @package Port1HybridAuth
 */
class Port1HybridAuth extends Plugin
{
    const PROVIDERS = [
        'Amazon',
        'Google',
        'Facebook',
        'LinkedIn',
//        unfortunatly windows live login doesn`t work after hours of bugfixing against M$ oauth
//        'Live'
    ];

    /**
     * @param InstallContext $context
     */
    public function install(InstallContext $context)
    {
        $this->addIdentityFieldsToUser();

        parent::install($context);
    }

    /**
     * @param ActivateContext $context
     */
    public function activate(ActivateContext $context)
    {
        $this->addIdentityFieldsToUser();

        parent::activate($context);
    }

    /**
     * @param UninstallContext $context
     */
    public function uninstall(UninstallContext $context)
    {
        parent::uninstall($context);
    }

    /**
     *
     */
    private function addIdentityFieldsToUser()
    {

        /** @var CrudService $service */
        $service = $this->container->get('shopware_attribute.crud_service');

        foreach (self::PROVIDERS as $provider) {
            $service->update('s_user_attributes', strtolower($provider) . '_identity', 'string', [
                //user has the opportunity to translate the attribute field for each shop
                'translatable' => false,

                //attribute will be displayed in the backend module
                'displayInBackend' => true,

                //in case of multi_selection or single_selection type, article entities can be selected,
                'entity' => Customer::class,

                //numeric position for the backend view, sorted ascending
                'position' => 100,

                //user can modify the attribute in the free text field module
                'custom' => false,
            ]);
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Front_StartDispatch' => 'onFrontStartDispatch',
            'Enlight_Controller_Action_PostDispatch_Frontend_Register' => 'onFrontendPostDispatchRegister',
            'Enlight_Controller_Action_PostDispatch_Frontend_Account' => 'onFrontendPostDispatchAccount',
            'Theme_Compiler_Collect_Plugin_Less' => 'onCollectLessFiles'
        ];
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function onFrontStartDispatch(\Enlight_Event_EventArgs $args)
    {
        $this->registerHybridAuth();
    }

    /**
     *
     */
    public function registerHybridAuth()
    {
        require_once $this->getPath() . '/vendor/autoload.php';
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function onFrontendPostDispatchRegister(\Enlight_Event_EventArgs $args)
    {
        /** @var \Enlight_Controller_Action $controller */
        $controller = $args->get('subject');
        $view = $controller->View();

        /** @var ConfigurationServiceInterface $configurationService */
        $configurationService = $this->container->get('port1_hybrid_auth.configuration_service');

        $enabledProviders = $configurationService->getEnabledProviders();

        $view->assign('providers', $enabledProviders);
        $view->addTemplateDir($this->getPath() . '/Resources/views');
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function onFrontendPostDispatchAccount(\Enlight_Event_EventArgs $args)
    {
        /** @var \Enlight_Controller_Action $controller */
        $controller = $args->get('subject');

        $actionName = $controller->Front()->Request()->getActionName();

        /**
         * do trigger sso logout
         */
        if ($actionName === 'logout') {
            /** @var SingleSignOnServiceInterface $singleSignOnService */
            $singleSignOnService = $this->container->get('port1_hybrid_auth.single_sign_on_service');
            $singleSignOnService->logout();
        }
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     *
     * @return ArrayCollection
     */
    public function onCollectLessFiles(\Enlight_Event_EventArgs $args)
    {
        $lessDir = $this->getPath() . '/Resources/views/frontend/_public/src/less/';
        $lessDefinition = new LessDefinition(
        // less configuration variables
            [],

            // less files which should be compiled
            [
                $lessDir . 'hybrid_auth.less'
            ],

            //import directory for less @import commands
            $lessDir
        );

        return new ArrayCollection([$lessDefinition]);
    }

}
