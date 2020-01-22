<?php

$config = \App\Config::getInstance();

if (!$config->get('ldap.adapter.mock'))
    $config->set('ldap.adapter.mock', false);


/** @var \Composer\Autoload\ClassLoader $composer */
$composer = $config->getComposer();
if ($composer)
    $composer->add('Ldap\\', dirname(__FILE__));

$routes = $config->getRouteCollection();
if (!$routes) return;



$routes->add('ldap-admin-settings', new \Tk\Routing\Route('/admin/ldapSettings.html', 'Ldap\Controller\SystemSettings::doDefault'));

$routes->add('ldap-admin-institution-settings', new \Tk\Routing\Route('/admin/ldapInstitutionSettings.html', 'Ldap\Controller\InstitutionSettings::doDefault'));
$routes->add('ldap-client-institution-settings', new \Tk\Routing\Route('/client/ldapInstitutionSettings.html', 'Ldap\Controller\InstitutionSettings::doDefault'));







