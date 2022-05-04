<?php
namespace Ldap\Listener;

use Ldap\Plugin;
use Symfony\Component\HttpKernel\KernelEvents;
use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class SetupHandler implements Subscriber
{

    /**
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     * @throws \Exception
     */
    public function onRequest($event)
    {
        $config = \App\Config::getInstance();

        if (Plugin::isUniLib()) {
            $institution = $config->getInstitution();
            if (!$institution) {
                if ($event->getRequest()->has('instHash')) {
                    $institution = $config->getInstitutionMapper()->findByHash($event->getRequest()->get('instHash'));
                }
                if ($event->getRequest()->attributes->get('institutionId')) {
                    /** @var \Uni\Db\Institution $inst */
                    $institution = $config->getInstitutionMapper()->find($event->getRequest()->attributes->get('institutionId'));
                }
                $config->set('institution', $institution);
            }
            if ($institution && Plugin::getInstance()->isZonePluginEnabled(Plugin::ZONE_INSTITUTION, $institution->getId())) {
                $config->getEventDispatcher()->addSubscriber(new \Ldap\Listener\AuthHandlerUnimelb());
            }
        } else {
            if (Plugin::getInstance()->isActive()) {
                $config->getEventDispatcher()->addSubscriber(new \Ldap\Listener\AuthHandler());
            }
        }
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
            KernelEvents::REQUEST => array('onRequest', -10)
        );
    }
    
    
}