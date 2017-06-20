<?php
namespace Ldap\Auth;

use Ldap\Plugin;
use Tk\Auth\Result;

/**
 * LDAP Authentication adapter
 *
 * This adapter requires that the data values have been set
 * ```
 * $adapter->replace(array('username' => $value, 'password' => $password));
 * ```
 *
 */
class UnimelbAdapter extends \Tk\Auth\Adapter\Ldap
{

    /**
     * @var \App\Db\Institution
     */
    protected $institution = null;


    /**
     * Constructor
     *
     * @param \App\Db\Institution $institution
     */
    public function __construct($institution)
    {
        $this->institution = $institution;

        $data = Plugin::getInstitutionData($institution);
        parent::__construct($data->get(\Ldap\Plugin::LDAP_HOST), $data->get(\Ldap\Plugin::LDAP_BASE_DN),
            $data->get(\Ldap\Plugin::LDAP_FILTER), (int)$data->get(\Ldap\Plugin::LDAP_PORT), $data->get(\Ldap\Plugin::LDAP_TLS));
    }

    /**
     * Authenticate the user
     *
     * @throws \Tk\Auth\Exception
     * @return Result
     */
    public function authenticate()
    {
        $username = $this->get('username');
        $password = $this->get('password');
        
        /* @var \Tk\Auth\Result $r */
        $r = parent::authenticate();
        vd($r);
        if ($r->getCode() != Result::SUCCESS)
            return $r;
        vd();
        $ldapData = $r->get('ldap');

        if (!$ldapData) return new Result(Result::FAILURE_CREDENTIAL_INVALID, $username, 'Error Connecting to LDAP Server.');

        if (!$this->institution) {
            return new Result(Result::FAILURE_CREDENTIAL_INVALID, $username, 'Invalid institution.');
        }

        // TODO: This should be in the Listener and the adapter should be more generic.
        // TODO: If this is in a listener then custom listeners could be created for different institutions...

        // Update the user record with ldap data
        /* @var \App\Db\User $user */
        $user = Plugin::getPluginApi()->findUser($r->getIdentity(), $this->institution->getId());;
        if (!$user && isset($ldapData[0]['mail'][0])) {
            $user = Plugin::getPluginApi()->findUser($ldapData[0]['mail'][0], $this->institution->getId());
        }

        if (!$user) {
            // Create a user record if none exists
            $role = 'student';
            if (preg_match('/(staff|student)/', strtolower($ldapData[0]['auedupersontype'][0]), $reg)) {
                if ($reg[1] == 'staff') {
                    $role = 'staff';
                } else if ($reg[1] == 'student') {
                    $role = 'student';
                }
            }
            $params = array(
                'type' => 'ldap',
                'institutionId' => $this->institution->getId(),
                'username' => $username,
                'email' => $ldapData[0]['mail'][0],
                'role' => $role,
                'password' => $password,
                'name' => $ldapData[0]['displayname'][0],
                'active' => true,
                'uid' => $ldapData[0]['auedupersonid'][0]
            );
            $user = Plugin::getPluginApi()->createUser($params);
        } else {
            // Update User info if record already exists
            $user->username = $username;
            if (!empty($ldapData[0]['mail'][0]))
                $user->email = $ldapData[0]['mail'][0];
            if (!empty($ldapData[0]['auedupersonid'][0]))
                $user->uid = $ldapData[0]['auedupersonid'][0];

            $user->setNewPassword($password);
            $user->save();
        }

        $r = new Result(Result::SUCCESS, $user->getId());
        return $r;
    }

}