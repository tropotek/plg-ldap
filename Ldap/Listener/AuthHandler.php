<?php
namespace Ldap\Listener;

use Tk\EventDispatcher\SubscriberInterface;
use Tk\Kernel\KernelEvents;
use Tk\Event\ControllerEvent;
use Tk\Event\GetResponseEvent;
use Tk\Event\AuthEvent;
use Tk\Auth\AuthEvents;

/**
 * Class StartupHandler
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class AuthHandler implements SubscriberInterface
{


    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function onLogin(AuthEvent $event)
    {

        //$config = \App\Factory::getConfig();
        $result = null;
        $submittedData = $event->all();
        if (!isset($submittedData['instHash'])) return;
        $institution = \App\Db\InstitutionMap::create()->findByHash($submittedData['instHash']);
        if (!$institution) return null;
        $data = \Tk\Db\Data::create(\Ldap\Plugin::getInstance()->getName() . '.institution', $institution->getId());

        //if (LDAP->enabled....)

        $event->stopPropagation();      // If LDAP enabled then no other auth method to be used in the login form.
        vd('----- LDAP', $data);

        $adapter = new \Ldap\Auth\UnimelbAdapter($institution);
        $result = $event->getAuth()->authenticate($adapter);
        $event->setResult($result);
        if ($result && $result->getCode() == \Tk\Auth\Result::SUCCESS) {
            $event->set('auth.adapter.class', '\Ldap\Auth\UnimelbAdapter');
            $event->set('auth.adapter.name', 'LDAP');
        }

        if (!$result) {
            throw new \Tk\Auth\Exception('Invalid login credentials');
        }
        if (!$result->isValid()) {
            return;
        }

        /* @var \App\Db\User $user */
        $user = \App\Db\UserMap::create()->find($result->getIdentity());
        if (!$user) {
            throw new \Tk\Auth\Exception('User not found: Contact Your Administrator.');
        }
        $event->set('user', $user);
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
            AuthEvents::LOGIN => array('onLogin', 10)
        );
    }
    
    
}