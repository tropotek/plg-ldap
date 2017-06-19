<?php
namespace Ldap\Listener;

use Tk\Event\Subscriber;
use Tk\Event\AuthEvent;
use Tk\Auth\AuthEvents;

/**
 * Class StartupHandler
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class AuthHandler implements Subscriber
{

    /**
     * @param AuthEvent $event
     * @return null|void
     */
    public function onLogin(AuthEvent $event)
    {
        $result = null;
        $submittedData = $event->all();

        if (!isset($submittedData['instHash'])) return;
        $institution = \App\Db\InstitutionMap::create()->findByHash($submittedData['instHash']);
        if (!$institution) return null;

        $data = \Tk\Db\Data::create(\Ldap\Plugin::getInstance()->getName() . '.institution', $institution->getId());
        if (!$data->get(\Ldap\Plugin::LDAP_ENABLE)) {
            return;
        }
        $event->stopPropagation();      // If LDAP enabled then no other auth method to be used in the login form.

        $adapter = new \Ldap\Auth\UnimelbAdapter($institution);
        $adapter->replace($submittedData);

        $result = $event->getAuth()->authenticate($adapter);

        $event->setResult($result);
        $event->set('auth.password.access', false);   // Can modify their own password

    }

    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function onLoginSuccess(AuthEvent $event)
    {

        vd('ldap\AuthHandler::onLoginSuccess  - is it creating the new user accounts ?????');
        // See the UnimelbAdapter....

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
            AuthEvents::LOGIN => array('onLogin', 10),   // execute this handler before the app auth handlers
            AuthEvents::LOGIN_SUCCESS => array('onLoginSuccess', 10)
        );
    }
    
    
}