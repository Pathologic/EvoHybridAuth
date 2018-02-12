<?php namespace EvoHybridAuth;

include_once MODX_BASE_PATH . 'assets/lib/MODxAPI/autoTable.abstract.php';

/**
 * Class haUserService
 */
class UserService extends \autoTable
{
    protected $table = 'ha_user_service';
    protected $default_field = array(
        'internalKey'   => 0,
        'identifier'    => '',
        'provider'      => '',
        'createdon'     => '',
        'websiteurl'    => '',
        'profileurl'    => '',
        'photourl'      => '',
        'displayname'   => '',
        'description'   => '',
        'firstname'     => '',
        'lastname'      => '',
        'gender'        => '',
        'language'      => '',
        'age'           => 0,
        'birthday'      => 0,
        'birthmonth'    => 0,
        'birthyear'     => 0,
        'email'         => '',
        'emailverified' => '',
        'phone'         => '',
        'address'       => '',
        'country'       => '',
        'region'        => '',
        'city'          => '',
        'zip'           => ''
    );

    /**
     * @param $key
     * @param $value
     * @return \autoTable
     */
    public function set($key, $value)
    {
        if ($key != 'internalKey') {
            $key = strtolower($key);
        }

        return parent::set($key, $value);
    }

    /**
     * @param array $data
     * @return $this
     */
    public function create($data = array())
    {
        parent::create($data);
        $this->set('createdon', date('Y-m-d H:i:s'));

        return $this;
    }

    /**
     * @param int $uid
     * @param bool $full
     * @return array
     */
    public function getActiveServices($uid = 0, $full = false)
    {
        $out = array();
        $uid = (int)$uid;
        if ($uid) {
            $q = $this->query("SELECT * from {$this->makeTable($this->table)} where `internalKey`={$uid}");
            while ($row = $this->modx->db->getRow($q)) {
                $out[$row['provider']] = $row;
            }
        }

        return $full ? $out : array_keys($out);
    }

    /**
     * @param string $identifier
     * @param string $provider
     * @return $this
     */
    public function getService($identifier = '', $provider = '')
    {
        $provider = is_scalar($provider) ? trim($provider) : '';
        $identifier = is_scalar($identifier) ? trim($identifier) : '';

        if ($provider && $identifier && $this->get('provider') != $provider && $this->get('identifier') != $identifier) {
            $this->close();
            $this->markAllEncode();
            $this->newDoc = false;
            $q = $this->query("SELECT * from {$this->makeTable($this->table)} where `identifier`='{$this->escape($identifier)}' AND `provider`='{$provider}'");
            $this->fromArray($this->modx->db->getRow($q));
            $this->store($this->toArray());
            $this->id = $this->eraseField($this->pkName);
            if (is_bool($this->id) && $this->id === false) {
                $this->id = null;
            } else {
                $this->decodeFields();
            }
        }

        return $this;
    }

    /**
     * @param string $provider
     * @param int $uid
     */
    public function deleteService($provider = '', $uid = 0)
    {
        $provider = $this->escape($provider);
        $uid = (int)$uid;
        if ($this->getID() && $this->get('provider') == $provider && $this->get('internalKey') == $uid) {
            $this->close();
        }
        if ($provider && $uid) {
            $this->query("DELETE FROM {$this->makeTable($this->table)} WHERE `provider`='{$provider}' AND `internalKey`={$uid}");
        }
    }

    /**
     *
     */
    public function createTable()
    {
        $this->query("
        CREATE TABLE IF NOT EXISTS {$this->makeTable($this->table)} (
            `id` int(10) NOT NULL auto_increment,
            `internalKey` int(10) NOT NULL,
            `identifier` varchar(100) NOT NULL default '',
            `provider` varchar(50) NOT NULL default '',
            `createdon` datetime,
            `websiteurl` varchar(255) NOT NULL default '',
            `profileurl` varchar(255) NOT NULL default '',
            `photourl` varchar(255) NOT NULL default '',
            `displayname` varchar(100) NOT NULL default '',
            `description` TEXT NOT NULL default '',
            `firstname` varchar(100) NOT NULL default '',
            `lastname` varchar(100) NOT NULL default '',
            `gender` varchar(50) NOT NULL default '',
            `language` varchar(50) NOT NULL default '',
            `age` int(3) default NULL,
            `birthday` int(2) default NULL,
            `birthmonth` int(2) default NULL,
            `birthyear` int(4) default NULL,
            `email` varchar(100) NOT NULL default '',
            `emailverified` varchar(100) NOT NULL default '',
            `phone` varchar(100) NOT NULL default '',
            `address` varchar(255) NOT NULL default '',
            `country` varchar(100) NOT NULL default '',
            `region` varchar(100) NOT NULL default '',
            `city` varchar(100) NOT NULL default '',
            `zip` varchar(25) NOT NULL default '',
            PRIMARY KEY  (`id`),
            UNIQUE KEY `unique_fields` (`internalKey`,`provider`),
            KEY `identifier` (`identifier`)
            ) ENGINE=MyISAM COMMENT='Datatable for HybridAuth plugin.'
        ");
    }
}
