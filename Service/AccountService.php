<?php
namespace Port1HybridAuth\Service;

/**
 * Copyright (C) portrino GmbH - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by André Wuttig <wuttig@portrino.de>, portrino GmbH
 */

use Shopware\Models\Customer\Customer;

/**
 * Class AccountService
 * @package Port1HybridAuth\Service
 */
class AccountService implements AccountServiceInterface
{
    /**
     * @var \sAdmin
     */
    protected $admin;

    /**
     * @var \Enlight_Controller_Front
     */
    protected $front;

    /**
     * AccountService constructor.
     */
    public function __construct()
    {
        $this->admin = Shopware()->Modules()->Admin();
        $this->front = Shopware()->Container()->get('Front');
    }

    /**
     * Checks if user is correctly logged in. Also checks session timeout
     *
     * @return bool
     */
    public function checkUser()
    {
        return $this->admin->sCheckUser();
    }

    /**
     * Logs in the user for the given customer object
     *
     * @param Customer $customer
     *
     * @return bool|array
     */
    public function loginUser($customer) {
        $this->front->Request()->setPost('email', $customer->getEmail());
        $this->front->Request()->setPost('passwordMD5', $customer->getPassword());
        return $this->admin->sLogin(true);
    }
}
