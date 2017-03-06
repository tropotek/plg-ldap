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
     * @return null|void
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

        // TODO: Would be nice to do this in the listener somewhere????`
//        vd('ldap: onLoginSuccess');
//        $result = $event->getResult();
//        if (!$result) {
//            throw new \Tk\Auth\Exception('Invalid login credentials');
//        }
//        if (!$result->isValid() || $result->get('loginType') != 'ldap') {
//            return;
//        }
//
//        $username = $result->get('username');
//        $password = $result->get('password');
//        $institution = $result->get('institution');
//        $ldapData = $result->get('ldap');
//
//        // Update the user record with ldap data
//        $iid = 0;
//        if ($institution)
//            $iid = $institution->id;
//
//        /* @var \App\Db\User $user */
//        $user = \App\Db\UserMap::create()->findByUsername($result->getIdentity(), $iid);
//        if (!$user && isset($ldapData[0]['mail'][0])) {
//            $user = \App\Db\UserMap::create()->findByEmail($ldapData[0]['mail'][0], $iid);
//        }
//
//        // Create the user record if none exists....
//        if (!$user) {
//            $role = 'student';
//            if (preg_match('/(staff|student)/', strtolower($ldapData[0]['auedupersontype'][0]))) {
//                $role = strtolower($ldapData[0]['auedupersontype'][0]);
//            }
//
//            // Create new user
//            $user = \App\Factory::createNewUser(
//                $iid,
//                $username,
//                $ldapData[0]['mail'][0],
//                $role,
//                $password,
//                $ldapData[0]['displayname'][0],
//                $ldapData[0]['auedupersonid'][0]
//            );
//
//        } else {
//            // Update User info if record already exists
//            $user->username = $username;
//            if (!empty($ldapData[0]['mail'][0]))
//                $user->email = $ldapData[0]['mail'][0];
//            if (!empty($ldapData[0]['auedupersonid'][0]))
//                $user->uid = $ldapData[0]['auedupersonid'][0];
//
//            $user->setPassword($password);
//            $user->save();
//        }
//
//
//
//        // TODO: We would need to overwrite the auth storage as well.
//
//        $event->setResult(new \Tk\Auth\Result(\Tk\Auth\Result::SUCCESS, $user->getId()));




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