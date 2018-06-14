<?php
namespace Ldap\Auth;

use Ldap\Plugin;
use Tk\Auth\Result;

/**
 * This adapter requires that the data values have been set
 * ```
 * $adapter->replace(array('username' => $value, 'password' => $password));
 * ```
 */
class UnimelbAdapter extends \Tk\Auth\Adapter\Ldap
{

    /**
     * @var \Uni\Db\InstitutionIface
     */
    protected $institution = null;


    /**
     * Constructor
     *
     * @param \Uni\Db\InstitutionIface $institution
     * @throws \Tk\Db\Exception
     * @throws \Tk\Plugin\Exception
     */
    public function __construct($institution)
    {
        $this->institution = $institution;

        $data = Plugin::getInstitutionData($institution);
        parent::__construct($data->get(Plugin::LDAP_HOST), $data->get(Plugin::LDAP_BASE_DN),
            (int)$data->get(Plugin::LDAP_PORT), $data->get(Plugin::LDAP_TLS));
    }

    /**
     * Authenticate the user
     *
     * @return Result
     * @throws \Tk\Exception
     */
    public function authenticate()
    {
        $username = $this->get('username');
        $password = $this->get('password');
        /* @var \Tk\Auth\Result $r */
        $r = parent::authenticate();
        if ($r->getCode() != Result::SUCCESS)
            return $r;

        $ldapData = $r->get('ldap');
        if (!$ldapData) {
            return new Result(Result::FAILURE_CREDENTIAL_INVALID, $username, 'Error Connecting to LDAP Server.');
        }

        if (!$this->institution) {
            return new Result(Result::FAILURE_CREDENTIAL_INVALID, $username, 'Invalid institution.');
        }

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
                'uid' => $ldapData[0]['auedupersonid'][0],
                'ldapData' => $ldapData
            );
            $user = Plugin::getPluginApi()->createUser($params);
            if (!$user) {       // If user is null here it is assumed that we are not allowed to create users automatically
                return new Result(Result::FAILURE_CREDENTIAL_INVALID, $username, 'Invalid user account. Please contact your administrator.');
            }
        } else {
            if (!empty($ldapData[0]['auedupersonid'][0]))
                $user->uid = $ldapData[0]['auedupersonid'][0];
            $user->setNewPassword($password);
            $user->save();
        }

        if (method_exists($user, 'getData')) {
            $data = $user->getData();
            $data->set('ldap.last.login', json_encode($ldapData));
            $data->save();
        }


        $r = new Result(Result::SUCCESS, $user->getId());
        return $r;
    }

}