<?php
namespace Ldap;

use Tk\Event\Dispatcher;
use Tk\EventDispatcher\EventDispatcher;
use Tk\Exception;


/**
 * @author Michael Mifsud <http://www.tropotek.com/>
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
    public static $pluginData = null;


    /**
     * A helper method to get the Plugin instance globally
     *
     * @return static|\Tk\Plugin\Iface
     * @throws \Tk\Db\Exception
     * @throws \Tk\Plugin\Exception
     */
    static function getInstance()
    {
        return \App\Config::getInstance()->getPluginFactory()->getPlugin('plg-ldap');
    }

    /**
     * @return \Tk\Db\Data
     * @throws \Exception
     */
    public static function getPluginData()
    {
        $fkey = self::getInstance()->getName() . '.admin';
        $fid = 0;
        if (self::isUniLib()) {
            if (!is_object(\App\Config::getInstance()->get('institution'))) {
                // TODO: if this gets thrown then we need to reconsider the construction of this object
                throw new Exception('No institution object found?');
            }
            $fkey = self::getInstance()->getName() . '.institution';
            $fid = \App\Config::getInstance()->get('institution')->getId();
        }
        return self::$pluginData = \Tk\Db\Data::create($fkey, $fid);
    }

    /**
     * Return true if the plugin is enabled for this institution
     *
     * @param $institution
     * @return bool
     * @throws \Exception
     */
    public static function isEnabled()
    {
        $data = self::getPluginData();
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
        include dirname(__FILE__) . '/config.php';

        if (self::isUniLib()) {
            $this->getPluginFactory()->registerZonePlugin($this, self::ZONE_INSTITUTION);
        }

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getConfig()->getEventDispatcher();
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
        $db = \App\Config::getInstance()->getDb();
        // Clear the data table of all plugin data
        $sql = sprintf('DELETE FROM %s WHERE %s LIKE %s',
            $db->quoteParameter(\Tk\Db\Data::$DB_TABLE), $db->quoteParameter('fkey'),
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
        if (!self::isUniLib()) return null;
        switch ($zoneName) {
            case self::ZONE_INSTITUTION:
                return \Bs\Uri::createHomeUrl('/ldapInstitutionSettings.html');
        }
        return null;
    }

    /**
     * Return the URI of the plugin's configuration page
     * Return null for none
     *
     * @return \Tk\Uri
     */
    public function getSettingsUrl()
    {
        if (self::isUniLib()) return null;
        return \Bs\Uri::createHomeUrl('/ldapSettings.html');
    }

    /**
     * Return true if the site is a tk-uni lib based site
     * @return bool
     */
    public static function isUniLib()
    {
        return class_exists('Uni\Db\Institution');
    }
}