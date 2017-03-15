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
    // data labels
    const LDAP_ENABLE = 'inst.ldap.enable';
    const LDAP_HOST = 'inst.ldap.host';
    const LDAP_TLS = 'inst.ldap.tls';
    const LDAP_PORT = 'inst.ldap.port';
    const LDAP_BASE_DN = 'inst.ldap.baseDn';
    const LDAP_FILTER = 'inst.ldap.filter';


    /**
     * @var \Tk\Db\Data
     */
    public static $institutionData = null;


    /**
     * A helper method to get the Plugin instance globally
     *
     * @return \App\Plugin\Iface
     */
    static function getInstance()
    {
        return \Tk\Config::getInstance()->getPluginFactory()->getPlugin('ems-ldap');
    }

    /**
     * @return \Tk\Db\Data
     */
    public static function getInstitutionData()
    {
        if (\Tk\Config::getInstance()->getUser() && !self::$institutionData) {
            $institution = \Tk\Config::getInstance()->getUser()->getInstitution();
            if ($institution)
                self::$institutionData = \Tk\Db\Data::create(self::getInstance()->getName() . '.institution', $institution->getId());
        }
        return self::$institutionData;
    }

    /**
     * Return true if the plugin is enabled for this institution
     *
     * @return bool
     */
    public static function isEnabled()
    {
        $data = self::getInstitutionData();
        if ($data && $data->has(self::LDAP_ENABLE)) {
            return $data->get(self::LDAP_ENABLE);
        }
        return false;
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

        $config = $this->getConfig();

        // Setup the adapter, this should be selectable from the settings if needed?
//        $adapters = $config['system.auth.adapters'];
//        $adapters = array_merge(array('LDAP' => '\Ldap\Auth\UnimelbAdapter'), $adapters);
//        $config['system.auth.adapters'] = $adapters;

        $this->getPluginFactory()->registerZonePlugin($this, \App\Plugin\Iface::ZONE_CLIENT);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = \Tk\Config::getInstance()->getEventDispatcher();
        /** @var \App\Db\Institution $institution */
        $institution = $config->getInstitution();
        if($institution && $this->isZonePluginEnabled(\App\Plugin\Iface::ZONE_CLIENT, $institution->getId())) {
            $dispatcher->addSubscriber(new \Ldap\Listener\AuthHandler());
        }

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
//        $data->set('plugin.title', 'EMS III Ldap Plugin');
//        $data->set('plugin.email', 'null@unimelb.edu.au');
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
        $db = \App\Factory::getDb();

        // Clear the data table of all plugin data
        $sql = sprintf('DELETE FROM %s WHERE %s LIKE %s', $db->quoteParameter(\Tk\Db\Data::$DB_TABLE), $db->quoteParameter('foreign_key'), $db->quote($this->getName().'%'));
        $db->query($sql);
    }

    /**
     * Get the course settings URL, if null then there is none
     *
     * @return string|\Tk\Uri|null
     */
    public function getZoneSettingsUrl($zoneName)
    {
        switch ($zoneName) {
            case \App\Plugin\Iface::ZONE_CLIENT:
                return \Tk\Uri::create('/ldap/institutionSettings.html');
        }
        return null;
    }

}