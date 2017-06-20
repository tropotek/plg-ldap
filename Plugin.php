<?php
namespace Ldap;

use Tk\Event\Dispatcher;


/**
 * Class Plugin
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class Plugin extends \Tk\Plugin\Iface
{

    const ZONE_INSTITUTION = 'institution';
    const ZONE_COURSE_PROFILE = 'profile';
    const ZONE_COURSE = 'course';

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
     * @return \Tk\Plugin\Iface
     */
    static function getInstance()
    {
        return \Tk\Config::getInstance()->getPluginFactory()->getPlugin('ems-ldap');
    }

    /**
     * @return \App\PluginApi
     */
    public static function getPluginApi()
    {
        return \Tk\Config::getInstance()->getPluginApi();
    }

    /**
     * @param \App\Db\Institution $institution
     * @return \Tk\Db\Data
     */
    public static function getInstitutionData($institution)
    {
        \Tk\Config::getInstance()->setInstitution($institution);
        return self::$institutionData = \Tk\Db\Data::create(self::getInstance()->getName() . '.institution', $institution->getId());
    }

    /**
     * Return true if the plugin is enabled for this institution
     *
     * @param $institution
     * @return bool
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
        $sql = sprintf('DELETE FROM %s WHERE %s LIKE %s', $db->quoteParameter(\Tk\Db\Data::$DB_TABLE), $db->quoteParameter('fkey'),
            $db->quote($this->getName().'%'));
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
            case self::ZONE_INSTITUTION:
                return \Tk\Uri::create('/ldap/institutionSettings.html');
        }
        return null;
    }

}