<?php
namespace Ldap\Auth;

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

        $data = \Tk\Db\Data::create(\Ldap\Plugin::getInstance()->getName() . '.institution', $institution->getId());
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
        if ($r->getCode() != Result::SUCCESS)
            return $r;
        $ldapData = $r->get('ldap');
        if (!$ldapData) return new Result(Result::FAILURE_CREDENTIAL_INVALID, $username, 'Error Connecting to LDAP Server.');

        if (!$this->institution) {
            return new Result(Result::FAILURE_CREDENTIAL_INVALID, $username, 'Invalid institution.');
        }

        // Update the user record with ldap data
        $iid = 0;
        if ($this->institution)
            $iid = $this->institution->getId();

        /* @var \App\Db\User $user */
        $user = \App\Db\UserMap::create()->findByUsername($r->getIdentity(), $iid);
        if (!$user && isset($ldapData[0]['mail'][0])) {
            $user = \App\Db\UserMap::create()->findByEmail($ldapData[0]['mail'][0], $iid);
        }

        // Create the user record if none exists....
        // TODO: Maybe we could create an event to be fired here for the ldap listener
        //       But doing this could create issues with the dispatcher?????? Test it and try???
        if (!$user) {
            $section = \App\Db\UserRole::SECTION_STUDENT;
            if (preg_match('/(staff|student)/', strtolower($ldapData[0]['auedupersontype'][0]), $reg)) {
                if ($reg[1] == 'staff') {
                    $section = \App\Db\UserRole::SECTION_STAFF;
                } else if ($reg[1] == 'staff') {
                    $section = \App\Db\UserRole::SECTION_STUDENT;
                }
            }

            // Create new user
            $user = \App\Factory::createNewUser(
                $iid,
                $username,
                $ldapData[0]['mail'][0],
                $section,
                $password,
                $ldapData[0]['displayname'][0],
                $ldapData[0]['auedupersonid'][0]
            );
        } else {
            // Update User info if record already exists
            $user->username = $username;
            if (!empty($ldapData[0]['mail'][0]))
                $user->email = $ldapData[0]['mail'][0];
            if (!empty($ldapData[0]['auedupersonid'][0]))
                $user->uid = $ldapData[0]['auedupersonid'][0];

            $user->setPassword($password);
            $user->save();
        }
        $r = new Result(Result::SUCCESS, $user->getId());
        //$r->set('loginType', 'ldap');
        //$r->replace($this);
        //$r->set('ldap', $ldapData);
        //$r->set('institution', $this->institution);
        return $r;
    }

}