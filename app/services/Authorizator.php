<?php

namespace App\Services;

use App\Model\UserRepository;
use Nette\Security\Permission;


class Authorizator
{

    const ROLE_SUPERADMIN = 'sa',
        ROLE_ADMIN = 'a',
        ROLE_USER = 'u',
        RESOURCE_DASHBOARD = 'Dashboard',
        RESOURCE_PAGE = 'Page',
        RESOURCE_ORGANIZATION = 'Organization',
        RESOURCE_ARTICLE = 'Article',
        RESOURCE_NAVIGATION = 'Navigation',
        RESOURCE_USER = 'User',
        RESOURCE_FILE = 'File',
        RESOURCE_TAG = 'Tag',
        RESOURCE_ERROR = 'Error4xx';

    public static function create(): Permission
    {
        $acl = new Permission;

        // roles
        $acl->addRole(UserRepository::ROLE_SUPERADMIN);
        $acl->addRole(UserRepository::ROLE_ADMIN);
        $acl->addRole(UserRepository::ROLE_USER);

        // resources
        $acl->addResource(self::RESOURCE_DASHBOARD);
        $acl->addResource(self::RESOURCE_ORGANIZATION);
        $acl->addResource(self::RESOURCE_ARTICLE);
        $acl->addResource(self::RESOURCE_USER);
        $acl->addResource(self::RESOURCE_TAG);
        $acl->addResource(self::RESOURCE_ERROR);

        // rules
        $acl->allow(self::ROLE_SUPERADMIN, Permission::ALL, ['create', 'read', 'update', 'delete']);
        $acl->allow(self::ROLE_ADMIN, Permission::ALL, ['read']);
        $acl->allow(self::ROLE_USER, Permission::ALL, ['read']);
        $acl->allow(self::ROLE_ADMIN, self::RESOURCE_ARTICLE, ['create', 'red', 'update', 'delete']);
        $acl->allow(self::ROLE_ADMIN, self::RESOURCE_USER, ['create', 'red', 'update', 'delete']);

        return $acl;
    }
}