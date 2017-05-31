<?php
$config = \Tk\Config::getInstance();

// NOTE:
// You must manually include all required php files if you are not using composer to install the plugin.
// Alternatively be sure to use the plugin namespace for all classes such as \sample\Ems\MyClass


/** @var \Tk\Routing\RouteCollection $routes */
$routes = $config['site.routes'];

$params = array('section' => \App\Db\UserGroup::ROLE_ADMIN);
$routes->add('LDAP Admin Settings', new \Tk\Routing\Route('/ldap/adminSettings.html', 'Ldap\Controller\SystemSettings::doDefault', $params));


$params = array('section' => \App\Db\UserGroup::ROLE_CLIENT);
$routes->add('LDAP Institution Settings', new \Tk\Routing\Route('/ldap/institutionSettings.html', 'Ldap\Controller\InstitutionSettings::doDefault', $params));



