<?php
namespace Ldap;

use Tk\Event\Dispatcher;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class Plugin extends \Tk\Plugin\Iface
{

    const ZONE_INSTITUTION = 'institution';
    const ZONE_COURSE = 'course';
    const ZONE_SUBJECT = 'subject';

    // data labels
    const LDAP_ENABLE = 'inst.ldap.enable';
    const LDAP_HOST = 'inst.ldap.host';
    const LDAP_PORT = 'inst.ldap.port';
    const LDAP_BASE_DN = 'inst.ldap.baseDn';

    const LDAP_TLS = 'inst.ldap.tls';
    const LDAP_FILTER = 'inst.ldap.filter';


    /**
     * @var \Tk\Db\Data
     */
    public static $institutionData = null;


    /**
     * A helper method to get the Plugin instance globally
     *
     * @return static|\Tk\Plugin\Iface
     * @throws \Tk\Db\Exception
     * @throws \Tk\Plugin\Exception
     */
    static function getInstance()
    {
        return \Uni\Config::getInstance()->getPluginFactory()->getPlugin('plg-ldap');
    }

    /**
     * @param \Uni\Db\Institution $institution
     * @return \Tk\Db\Data
     * @throws \Exception
     */
    public static function getInstitutionData($institution)
    {
        // TODO: this may not be the best position for this
        \App\Config::getInstance()->set('institution', $institution);

        return self::$institutionData = \Tk\Db\Data::create(self::getInstance()->getName() . '.institution', $institution->getId());
    }

    /**
     * Return true if the plugin is enabled for this institution
     *
     * @param $institution
     * @return bool
     * @throws \Exception
     */
    public static function isEnabled($institution)
    {
        $data = self::getInstitutionData($institution);
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
        /** @var \App\Config $config */
        $config = $this->getConfig();
        $this->getPluginFactory()->registerZonePlugin($this, self::ZONE_INSTITUTION);

        /** @var Dispatcher $dispatcher */
        $dispatcher = $config->getEventDispatcher();
        $dispatcher->addSubscriber(new \Ldap\Listener\SetupHandler());

    }

    /**
     * Activate the plugin, essentially
     * installing any DB and settings required to run
     * Will only be called when activating the plugin in the
     * plugin control panel
     *
     * @throws \Exception
     */
    function doActivate()
    {
        // TODO: Implement doActivate() method.

        // Init Settings
        $data = \Tk\Db\Data::create($this->getName());
        $data->save();
    }

    /**
     * Deactivate the plugin removing any DB data and settings
     * Will only be called when deactivating the plugin in the
     * plugin control panel
     *
     * @throws \Exception
     */
    function doDeactivate()
    {
        $db = \Uni\Config::getInstance()->getDb();

        // Clear the data table of all plugin data
        $sql = sprintf('DELETE FROM %s WHERE %s LIKE %s', $db->quoteParameter(\Tk\Db\Data::$DB_TABLE), $db->quoteParameter('fkey'),
            $db->quote($this->getName().'%'));
        $db->query($sql);
    }

    /**
     * Get the settings URL, if null then there is none
     *
     * @param string $zoneName
     * @param string $zoneId
     * @return string|\Tk\Uri|null
     */
    public function getZoneSettingsUrl($zoneName, $zoneId)
    {
        switch ($zoneName) {
            case self::ZONE_INSTITUTION:
                return \Bs\Uri::createHomeUrl('/ldapInstitutionSettings.html');
        }
        return null;
    }

}