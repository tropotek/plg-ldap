<?php
namespace Ldap\Listener;

use Tk\Event\Subscriber;
use Tk\Event\AuthEvent;
use Tk\Auth\AuthEvents;
use Ldap\Plugin;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class AuthHandler implements Subscriber
{

    /**
     * @param AuthEvent $event
     * @return null|void
     * @throws \Tk\Auth\Exception
     * @throws \Tk\Db\Exception
     * @throws \Tk\Exception
     * @throws \Tk\Plugin\Exception
     */
    public function onLogin(AuthEvent $event)
    {
        $result = null;
        $formData = $event->all();

        // ---------------------------------------------------------------------------
        // TODO: these connection params should be sources from somewhere??????
        // Q: What if we want to use this plugin without an institution???
        // A: We cannot at the moment because of the plugin enabling system
        //   is tied to the institution object
        // This should come from the \Tk\Config::getInstance()->get('ldapConnection');
        //   Then if it is null we do not authenticate using this plugin???

        // This plugin cannot be independent without removing the institution dependency
        //  so this can stay here for now as we will only be using LDAP in an institution environment.

        $institution = $this->getConfig()->getInstitution();
        if (empty($formData['username']) || !$institution || !Plugin::isEnabled($institution)) return;

        $data = Plugin::getInstitutionData($institution);

        $hostUri = $data->get(Plugin::LDAP_HOST);
        $port = (int)$data->get(Plugin::LDAP_PORT);
        $baseDn =  $data->get(Plugin::LDAP_BASE_DN);
        $data = Plugin::getInstitutionData($institution);
        if (!$data->get(Plugin::LDAP_ENABLE)) {
            return;
        }
        // ---------------------------------------------------------------------------

        $auth = $this->getConfig()->getAuth();
        $event->stopPropagation();      // If LDAP enabled then no other auth method to be used in the login form.????

        $adapter = new \Tk\Auth\Adapter\Ldap($hostUri, $baseDn, $port);
        $adapter->replace($formData);
        $result = $auth->authenticate($adapter);

        $event->setResult($result);
        $event->set('auth.password.access', false);   // Can modify their own password

    }


    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            AuthEvents::LOGIN => array('onLogin', 10)   // execute this handler before the app auth handlers
        );
    }


    /**
     * @return \App\Config
     */
    public function getConfig()
    {
        return \App\Config::getInstance();
    }
    
}