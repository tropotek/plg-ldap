<?php
/*
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2007 Michael Mifsud
 */
namespace Ldap\Auth;

use Tk\Auth\Result;

/**
 * For Debug use only
 */
class MockAdapter extends \Tk\Auth\Adapter\Ldap
{

    /**
     * @var string
     */
    protected $host = '';
    /**
     * @var int
     */
    protected $port = 636;
    /**
     * @var bool
     */
    protected $tls = false;
    /**
     * @var string
     */
    protected $baseDn = '';

    /**
     * @var null|resource
     */
    protected $ldap = null;

    protected $username = '';
    protected $password = '';
    protected $uid = '';


    /**
     * Constructor
     *
     * @param string $host    ldap://centaur.unimelb.edu.au
     * @param string $baseDn  uid=%s,cn=users,dc=domain,dc=edu
     * @param int $port
     * @param bool $tls
     */
    public function __construct($host, $baseDn, $port = 636, $tls = false)
    {
        parent::__construct($host, $baseDn, $port);
    }


    /**
     * Authenticate the user
     *
     * @return Result
     * @throws \Tk\Exception
     */
    public function authenticate()
    {
        $this->username = $this->get('username');
        $this->password = $this->get('password');
        $user = null;
        if (preg_match('/([a-z0-9\._]+)-([0-9]+)/', $this->username, $regs)) {
            vd($regs);
            $this->username = $regs[1];
            $this->uid = $regs[2];
            if ($this->uid) {
                $user = $this->getConfig()->getUserMapper()->findFiltered(['institutionId' => $this->getConfig()->getInstitutionId(), 'uid' => $this->uid])->current();
                if ($user)
                    $this->username = $user->getUsername();
            }
        } else {
            $this->set('username', $this->username);
            $user = $this->getConfig()->getUserMapper()->findByUsername($this->username);
        }
        $this->set('username', $this->username);

        //if (!$this->username || !$this->password) {
        if (!$user) {
            return new Result(Result::FAILURE_CREDENTIAL_INVALID, $this->username, '0000 Invalid username or password.');
        }
        try {

//            $this->ldap = @ldap_connect($this->getHost(), $this->getPort());
//            if ($this->isTls())
//                @ldap_start_tls($this->getLdap());

            $this->setBaseDn(sprintf($this->getBaseDn(), $this->username));
            // legacy check (remove in future versions)
            $this->setBaseDn(str_replace('{username}', $this->username, $this->getBaseDn()));

            //if (@ldap_bind($this->getLdap(), $this->getBaseDn(), $this->password)) {
            if (true) {
                $this->dispatchLoginProcess();
                if ($this->getLoginProcessEvent()->getResult()) {
                    return $this->getLoginProcessEvent()->getResult();
                }
                return new Result(Result::SUCCESS, $this->username);
            }
        } catch (\Exception $e) {
            \Tk\Log::warning($e->getMessage());
        }
        return new Result(Result::FAILURE_CREDENTIAL_INVALID, $this->username, 'Invalid username or password.');
    }

    /**
     * @param $filter
     * @return resource|false|null
     * @throws \Tk\Exception
     */
    public function ldapSearch($filter)
    {
        $username = $this->uid;
        $uid = $this->uid;
        $email = $this->username . '@student.unimelb.edu.au';
        $type = 'student';
        //$type = 'staff';
        //$type = 'lecturer';

        $json = <<<JSON
{
    "count": 1,
    "0": {
        "department": {
            "count": 3,
            "0": "Agriculture and Food",
            "1": "Veterinary & Agricultural Sciences",
            "2": "Ecosystem and Forest Sciences"
        },
        "0": "department",
        "auedupersonsalutation": {
            "count": 1,
            "0": "MR"
        },
        "1": "auedupersonsalutation",
        "auedupersonemailaddress": {
            "count": 1,
            "0": "$email"
        },
        "2": "auedupersonemailaddress",
        "auedupersonlibrarybarcodenumber": {
            "count": 1,
            "0": "21290095388569"
        },
        "3": "auedupersonlibrarybarcodenumber",
        "auedupersontype": {
            "count": 1,
            "0": "$type"
        },
        "4": "auedupersontype",
        "givenname": {
            "count": 1,
            "0": "john"
        },
        "5": "givenname",
        "auedupersonsubtype": {
            "count": 1,
            "0": "postgrad"
        },
        "6": "auedupersonsubtype",
        "auedupersonid": {
            "count": 1,
            "0": "$uid"
        },
        "7": "auedupersonid",
        "mailalternateaddress": {
            "count": 1,
            "0": "$username@$type.unimelb.edu.au"
        },
        "8": "mailalternateaddress",
        "displayname": {
            "count": 1,
            "0": "John Fred Jane Doe"
        },
        "9": "displayname",
        "uid": {
            "count": 1,
            "0": "$uid"
        },
        "10": "uid",
        "auedupersonsharedtoken": {
            "count": 1,
            "0": "VAT5ddSh0JFXQD0OcEZlLDea5_o"
        },
        "11": "auedupersonsharedtoken",
        "loginshell": {
            "count": 1,
            "0": "/bin/bash"
        },
        "12": "loginshell",
        "userpassword": {
            "count": 1,
            "0": "{SSHA512}JGiIUyUmboir3Tv7p3JS60d26iGvXTWnYVf96LWioUCqYhMo9cHDDtoU/6xFR1oCuX/xiMq/odBJI0r7IQyEH/PTcpWzkq7H"
        },
        "13": "userpassword",
        "oblogintrycount": {
            "count": 1,
            "0": "0"
        },
        "14": "oblogintrycount",
        "departmentnumber": {
            "count": 3,
            "0": "251M",
            "1": "259M",
            "2": "220M"
        },
        "15": "departmentnumber",
        "orclopenldapentryuuid": {
            "count": 1,
            "0": "0fc1a888-aeeb-1036-9d74-d9d9c857bdbf"
        },
        "16": "orclopenldapentryuuid",
        "orclsourceobjectdn": {
            "count": 1,
            "0": "uid=$username,ou=people,o=unimelb"
        },
        "17": "orclsourceobjectdn",
        "objectclass": {
            "count": 17,
            "0": "UoMPerson",
            "1": "shadowAccount",
            "2": "posixGroup",
            "3": "posixAccount",
            "4": "radiusprofile",
            "5": "unimelbPerson",
            "6": "eduMember",
            "7": "orclOpenLdapObject",
            "8": "person",
            "9": "oblixorgperson",
            "10": "organizationalPerson",
            "11": "oblixPersonPwdPolicy",
            "12": "inetOrgPerson",
            "13": "auEduPerson",
            "14": "sunFMSAML2NameIdentifier",
            "15": "top",
            "16": "eduPerson"
        },
        "18": "objectclass",
        "cn": {
            "count": 1,
            "0": "John Doe"
        },
        "19": "cn",
        "orclsourcecreatetimestamp": {
            "count": 1,
            "0": "20170406080202Z"
        },
        "20": "orclsourcecreatetimestamp",
        "orclsourcemodifytimestamp": {
            "count": 1,
            "0": "20170719110139Z"
        },
        "21": "orclsourcemodifytimestamp",
        "sn": {
            "count": 1,
            "0": "John"
        },
        "22": "sn",
        "gecos": {
            "count": 1,
            "0": "John Doe"
        },
        "23": "gecos",
        "ismemberof": {
            "count": 1,
            "0": "cn=Students,ou=StudentPortal,ou=appgroups,ou=groups,o=unimelb"
        },
        "24": "ismemberof",
        "homedirectory": {
            "count": 1,
            "0": "/afs/athena.unimelb.edu.au/user/s/$username"
        },
        "25": "homedirectory",
        "ou": {
            "count": 3,
            "0": "Agriculture and Food",
            "1": "Veterinary & Agricultural Sciences",
            "2": "Ecosystem and Forest Sciences"
        },
        "26": "ou",
        "uidnumber": {
            "count": 1,
            "0": "$uid"
        },
        "27": "uidnumber",
        "mail": {
            "count": 1,
            "0": "$email"
        },
        "28": "mail",
        "gidnumber": {
            "count": 1,
            "0": "10000"
        },
        "29": "gidnumber",
        "milleniumid": {
            "count": 1,
            "0": "924445**S"
        },
        "30": "milleniumid",
        "count": 31,
        "dn": "uid=$username,ou=people,o=unimelb"
        }
}
JSON;
        $ldapData = json_decode($json, true);
        return $ldapData;
    }


    /**
     * @return null|resource
     */
    public function getLdap()
    {
        return $this->ldap;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     * @return $this
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @return int|string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int|string $port
     * @return $this
     */
    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @return bool|string
     */
    public function isTls()
    {
        return $this->tls;
    }

    /**
     * @param bool $tls
     * @return $this
     */
    public function setTls($tls)
    {
        $this->tls = $tls;
        return $this;
    }

    /**
     * @return string
     */
    public function getBaseDn()
    {
        return $this->baseDn;
    }

    /**
     * @param string $baseDn
     * @return $this
     */
    public function setBaseDn($baseDn)
    {
        $this->baseDn = $baseDn;
        return $this;
    }

}

/*
[0] => Array
(
    [auedupersonemailaddress] => mick.mif@unimelb.edu.au
    [auedupersonid] => 099619
    [auedupersonsalutation] => MR
    [auedupersontype] => staff
    [aueduunitname] => 2540
    [cn] => Mick Mif
    [department] => Veterinary and Agricultural Sciences
    [departmentnumber] => Array
        (
            [0] => 254H
            [1] => 2540
        )

    [displayname] => Mick Mif
    [dn] => uid=mif,ou=people,o=unimelb
    [employeenumber] => 009619
    [employeetype] => FT
    [gecos] => Mick Mif
    [gidnumber] => 10000
    [givenname] => Mick
    [homedirectory] => /afs/unimelb.edu.au/user/z/mif
    [ismemberof] => cn=Staff,ou=StudentPortal,ou=appgroups,ou=groups,o=unimelb
    [mail] => mick.mif@unimelb.edu.au
    [mailalternateaddress] => mif@unimelb.edu.au
    [milleniumid] => 0096190
    [objectclass] => Array
        (
            [0] => UoMPerson
            [1] => shadowAccount
            [2] => posixGroup
            [3] => radiusprofile
            [4] => posixAccount
            [5] => unimelbPerson
            [6] => eduMember
            [7] => orclOpenLdapObject
            [8] => oblixorgperson
            [9] => person
            [10] => oblixPersonPwdPolicy
            [11] => organizationalPerson
            [12] => inetOrgPerson
            [13] => auEduPerson
            [14] => sunFMSAML2NameIdentifier
            [15] => top
            [16] => eduPerson
        )

    [oblockouttime] => 0
    [oblogintrycount] => 0
    [obpasswordchangeflag] => false
    [orclopenldapentryuuid] => d726a0ec-d0cc-1031-9f0c-b1181d9d8233
    [orclsourcecreatetimestamp] => 20171202130633Z
    [orclsourcemodifytimestamp] => 20170808214935Z
    [orclsourceobjectdn] => uid=mif,ou=people,o=unimelb
    [ou] => Veterinary and Agricultural Sciences
    [sn] => Mif
    [sun-fm-saml2-nameid-info] => https://sso.its.unimelb.edu.au/sso/google|google.com|mif|https://sso.its.unimelb.edu.au/sso/google|urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified|null|null|IDPRole|false
    [sun-fm-saml2-nameid-infokey] => https://sso.its.unimelb.edu.au/sso/google|google.com|mif
    [uid] => mif
    [uidnumber] => 1670202
    [userpassword] => {SSHA512}RbZ8EkhiXSXDi23GrmXpMfY...
)
*/