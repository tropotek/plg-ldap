<?php
namespace Ldap;


use Tk\EventDispatcher\EventDispatcher;


/**
 * Class Plugin
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class Plugin extends \App\Plugin\Iface
{


    /**
     * A helper method to get the Plugin instance globally
     *
     * @return \App\Plugin\Iface
     */
    static function getInstance()
    {
        return \Tk\Config::getInstance()->getPluginFactory()->getPlugin('ems-ldap');
    }


    // ---- \Tk\Plugin\Iface Interface Methods ----
    
    
    /**
     * Init the plugin
     *
     * This is called when the session first registers the plugin to the queue
     * So it is the first called method after the constructor.....
     *
     */
    function doInit()
    {
        // TODO: Implement doInit() method.
        include dirname(__FILE__) . '/config.php';

        $this->getPluginFactory()->registerInstitutionPlugin($this);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = \Tk\Config::getInstance()->getEventDispatcher();
        //$dispatcher->addSubscriber(new \Ldap\Listener\ExampleHandler());

    }
    
    /**
     * Activate the plugin, essentially
     * installing any DB and settings required to run
     * Will only be called when activating the plugin in the
     * plugin control panel
     *
     */
    function doActivate()
    {
        // TODO: Implement doActivate() method.

        // Init Settings
        $data = \Tk\Db\Data::create($this->getName());
        $data->set('plugin.title', 'EMS III Ldap Plugin');
        $data->set('plugin.email', 'null@unimelb.edu.au');
        $data->save();
    }

    /**
     * Deactivate the plugin removing any DB data and settings
     * Will only be called when deactivating the plugin in the
     * plugin control panel
     *
     */
    function doDeactivate()
    {
        // TODO: Implement doDeactivate() method.
        
        // Delete any setting in the DB
        $data = \Tk\Db\Data::create($this->getName());
        $data->clear();
        $data->save();
    }

    /**
     * @return \Tk\Uri
     */
    public function getSettingsUrl()
    {
        return \Tk\Uri::create('/ldap/adminSettings.html');
    }

    /**
     * Get the course settings URL, if null then there is none
     *
     * @return string|\Tk\Uri|null
     */
    public function getInstitutionSettingsUrl()
    {
        return \Tk\Uri::create('/ldap/institutionSettings.html');
    }

}