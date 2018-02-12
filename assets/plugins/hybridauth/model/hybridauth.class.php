<?php namespace EvoHybridAuth;

use \DocumentParser;
use \Exception;
use \modUsers;
use \Hybrid_Auth;
use \Hybrid_Endpoint;
use \Hybrid_Provider_Model;
use \Throwable;
use \jsonHelper;

/**
 * Class HybridAuth
 */
class Wrapper
{
    /** @var DocumentParser $modx */
    protected $modx = null;
    /** @var \Hybrid_Auth $Hybrid_Auth */
    protected $Hybrid_Auth = null;
    protected $config = array();
    /** @var modUsers $initialized */
    protected $userModel = null;
    /** @var UserService */
    protected $serviceModel = null;

    /**
     * HybridAuth constructor.
     * @param DocumentParser $modx
     */
    function __construct(DocumentParser $modx)
    {
        $this->modx = $modx;
        $this->setConfig();
        $userModel = isset($this->config['userModel']) && class_exists($this->config['userModel']) ? $this->config['userModel'] : '\modUsers';
        $this->userModel = new $userModel($modx);
        $this->serviceModel = new UserService($modx);

        $this->loadHybridAuth();

        if (!empty($this->config['HA'])) {
            /** @noinspection PhpIncludeInspection */

            try {
                $this->Hybrid_Auth = new Hybrid_Auth($this->config['HA']);
            } catch (Exception $e) {
                $this->exceptionHandler($e);
            }
        }

        $_SESSION['HybridAuth'] = $this->config;
    }

    protected function setConfig()
    {
        $pluginConfig = array();
        if (isset($this->modx->pluginCache['HybridAuthProps'])) {
            $pluginConfig = $this->modx->parseProperties($this->modx->pluginCache['HybridAuthProps'], 'HybridAuth',
                'plugin');
        }
        $this->config = array_merge(array(
            'rememberme'     => true,
            'registerUsers'  => false,
            'groups'         => '',
            'redirectUri'    => '',
            'loginPage'      => 0,
            'cookieName'     => 'WebLoginPE',
            'cookieLifetime' => 157680000,
            'debug'          => false
        ), $pluginConfig, $this->modx->event->params);
    }


    /**
     * Custom exception handler for Hybrid_Auth
     *
     * @param Throwable $e
     *
     * @return void;
     */
    public function exceptionHandler(Throwable $e)
    {
        $code = $e->getCode();
        if ($code <= 6) {
            $type = 3;
        } else {
            $type = 1;
        }
        if ($this->config['debug']) {
            $this->modx->logEvent(123, $type, $e->getMessage(), 'HybridAuth');
        }
        $this->refresh();
    }


    /**
     * Loads settings for Hybrid_Auth class
     *
     * @return bool|null|string
     */
    public function loadHybridAuth()
    {
        $providers = array();
        $configFile = MODX_BASE_PATH . 'assets/plugins/hybridauth/config/config.php';
        if (file_exists($configFile) && is_readable($configFile)) {
            $providers = include($configFile);
            if (!is_array($providers)) {
                $providers = array();
            }
        }

        if (!$providers) {
            return false;
        }

        $this->config['HA'] = array(
            'base_url'   => !empty($this->config['redirectUri'])
                ? $this->config['redirectUri']
                : $this->modx->makeUrl($this->modx->config['site_start'], '', '', 'full'),
            'debug_mode' => $this->config['debug'],
            'debug_file' => MODX_BASE_PATH . 'assets/cache/ha/error.log',
            'providers'  => $providers,
        );

        return true;
    }


    /**
     * Process Hybrid_Auth endpoint
     *
     * @return void
     */
    public function processAuth()
    {
        if (!empty($_SESSION['HA::STORE'])) {
            try {
                Hybrid_Endpoint::process();
            } catch (Exception $e) {
                $this->exceptionHandler($e);
            }
        }
    }


    /**
     * Checks and login user. Also creates/updated user services profiles
     *
     * @param string $provider Remote service to login
     *
     * @return void
     * @throws Exception
     */
    public function login($provider = '')
    {
        try {
            $this->Hybrid_Auth->authenticate($provider);
        } catch (Exception $e) {
            $this->exceptionHandler($e);
        }
        unset($_SESSION['HA']['error']);
        if ($profile = $this->getServiceProfile($provider)) {
            $profile['provider'] = $provider;
            $uid = $this->modx->getLoginUserID('web');
            // Checking for existing provider record in database
            if (!$this->serviceModel->getService($profile['identifier'], $profile['provider'])->getID()) {
                // Adding new record to current user
                if ($uid) {
                    $profile['internalKey'] = $uid;
                    if (!$this->serviceModel->create($profile)->save()) {
                        $_SESSION['HA']['error'] = 'Unable_to_save_service_profile';
                    }
                } // Find user ot create a new one and add this record to him
                else {
                    $user = $this->findUser($profile);
                    $response = $user;
                    if (!$user) {
                        if ($this->config['registerUsers']) {
                            $response = $this->createUser($profile);
                            if (!$response) {
                                $_SESSION['HA']['error'] = 'Unable_to_create_user';
                            }
                        } else {
                            $_SESSION['HA']['error'] = 'User_registration_is_disabled';
                        }
                    }
                    if (empty($_SESSION['HA']['error']) && $response) {
                        $profile['internalKey'] = $response;
                        $response = $this->serviceModel->create($profile)->save();
                        if (!$response) {
                            $_SESSION['HA']['error'] = 'Unable_to_save_service_profile';
                        }
                    }
                    // id of user found or created
                    $uid = $response;
                }
            } else {
                // Find and use binded MODX user
                if (!$uid = $this->modx->getLoginUserID('web')) {
                    $uid = $this->serviceModel->get('internalKey');
                }
                /** @var modUsers $user */
                if ($this->userModel->edit($uid)->getID()) {
                    $this->serviceModel->set('internalKey', $uid);
                    if (!$this->serviceModel->fromArray($profile)->save()) {
                        $_SESSION['HA']['error'] = 'Unable_to_update_service_profile';
                    }
                } else {
                    $this->serviceModel->delete($this->serviceModel->getID());
                    $this->Login($provider);
                }
            }
            if (empty($_SESSION['HA']['error']) && $uid && !$this->modx->getLoginUserID('web')) {
                $_SESSION['HA']['verified'] = 1;
                // Login
                $_SESSION['HybridAuth']['verified'] = true;
                $rememberme = $this->config['rememberme'] && isset($this->config['cookieLifetime']) ? $this->config['cookieLifetime'] : false;
                $response = $this->userModel->authUser($this->userModel->getID(), $rememberme,
                    $this->config['cookieName'], true);
                if (!$response) {
                    $_SESSION['HA']['error'] = 'Unable_to_login';
                }
            }

            $this->refresh('login');
        }
    }

    /**
     * @param string $provider
     */
    public function unbind($provider = '')
    {
        $uid = $this->modx->getLoginUserID('web');
        $this->serviceModel->deleteService($provider, $uid);
    }

    /**
     * @param $profile
     * @return bool|int|null
     */
    public function findUser($profile)
    {
        $username = $profile['identifier'] . '@' . $profile['provider'];
        $email = !empty($profile['emailVerified']) ? $profile['emailVerified'] : $profile['email'];
        if (empty($email)) {
            $email = $username . '.noemail';
        }
        $username = strtolower($username);
        $email = strtolower($email);

        if ($this->userModel->edit($email)->getID()) {
            $out = $this->userModel->getID();
        } elseif ($this->userModel->edit($username)->getID()) {
            $out = $this->userModel->getID();
        } else {
            $out = false;
        }

        return $out;
    }

    /**
     * @param $profile
     * @return bool|int|null
     */
    public function createUser($profile)
    {
        $this->userModel->create();
        $username = $profile['identifier'] . '@' . $profile['provider'];
        $email = !empty($profile['emailVerified']) ? $profile['emailVerified'] : $profile['email'];
        if (empty($email)) {
            $email = $username . '.noemail';
        }
        $this->userModel->set('username', strtolower($username));
        $this->userModel->set('email', strtolower($email));
        $this->userModel->set('password', md5(rand()));
        $arr = array(
            'fullname' => !empty($profile['lastName'])
                ? $profile['firstName'] . ' ' . $profile['lastName']
                : $profile['firstName'],
            'dob'      => !empty($profile['birthday']) && !empty($profile['birthmonth']) && !empty($profile['birthyear'])
                ? strtotime($profile['birthyear'] . '-' . $profile['birthmonth'] . '-' . $profile['birthday'])
                : '',
            'photo'    => !empty($profile['photoURL'])
                ? $profile['photoURL']
                : '',
            'website'  => !empty($profile['webSiteURL'])
                ? $profile['webSiteURL']
                : '',
            'phone'    => !empty($profile['phone'])
                ? $profile['phone']
                : '',
            'address'  => !empty($profile['address'])
                ? $profile['address']
                : '',
            'country'  => !empty($profile['country'])
                ? $profile['country']
                : '',
            'state'    => !empty($profile['region'])
                ? $profile['region']
                : '',
            'city'     => !empty($profile['city'])
                ? $profile['city']
                : '',
            'zip'      => !empty($profile['zip'])
                ? $profile['zip']
                : '',
        );
        $this->userModel->fromArray($arr);
        if (!empty($this->config['groups'])) {
            $groups = jsonHelper::jsonDecode($this->config['groups'], array('assoc' => true), true);
            foreach ($groups as &$group) {
                $group = $this->modx->db->escape(trim($group));
            }
            if (!empty($groups)) {
                $groups = "'" . implode("','", $groups) . "'";
                $groupNames = $this->modx->db->query("SELECT `id` FROM " . $this->modx->getFullTableName('webgroup_names') . " WHERE `name` IN (" . $groups . ")");
                $webGroups = $this->modx->db->getColumn('id', $groupNames);
                if ($webGroups) {
                    $this->userModel->setUserGroups(0, $webGroups);
                }
            }
        }

        return $this->userModel->save(true);
    }


    /**
     * Destroys all sessions
     *
     * @return void
     */
    public function logout()
    {
        if (is_object($this->Hybrid_Auth)) {
            try {
                $this->Hybrid_Auth->logoutAllProviders();
            } catch (Exception $e) {
                $this->exceptionHandler($e);
            }
        }
        unset($_SESSION['HybridAuth'], $_SESSION['HA'], $_SESSION['HA::STORE'], $_SESSION['HA::CONFIG']);
        $this->refresh('logout');
    }


    /**
     * Gets user profile from service
     *
     * @param string $provider Service provider, like Google, Twitter etc.
     *
     * @return array|boolean
     */
    function getServiceProfile($provider)
    {
        try {
            $providers = $this->Hybrid_Auth->getConnectedProviders();
            $providerId = ucfirst($provider);
            if (is_array($providers) && in_array($provider, $providers)) {
                /** @var Hybrid_Provider_Model $provider */
                $provider = $this->Hybrid_Auth->getAdapter($providerId);
                $profile = $provider->getUserProfile();

                return (array)$profile;
            }
        } catch (Exception $e) {
            $this->exceptionHandler($e);
        }

        return false;
    }


    /**
     * Refreshes the current page. If set, can redirects user to logout/login resource.
     *
     * @param string $action The action to do
     *
     * @return void
     */
    public function refresh($action = '')
    {
        $url = '';
        if ($action == 'login' && !empty($this->config['loginPage']) && $this->config['loginPage'] > 0) {
            $url = $this->modx->makeUrl($this->config['loginPage'], '', '', 'full');
        } elseif ($action == 'logout' && !empty($this->config['logoutPage']) && $this->config['logoutPage'] > 0) {
            $url = $this->modx->makeUrl($this->config['logoutPage'], '', '', 'full');
        }

        if (empty($url)) {
            $url = $this->getUrl(array(), array('action', 'provider', 'hauth_action'));
        }

        $this->modx->sendRedirect($url);
    }


    /**
     * Returns working url
     *
     * @param array $set
     * @param array $unset
     * @return string $url
     */
    public function getUrl($set = array(), $unset = array())
    {
        $url = $this->modx->config['site_url'];
        $request = parse_url($_SERVER['REQUEST_URI']);
        if (isset($request['path'])) {
            $url .= ltrim($request['path'], '/');
        }
        $query = isset($request['query']) ? parse_str($request['query']) : array();
        if (!empty($unset)) {
            foreach ($unset as $var) {
                unset($query[$var]);
            }
        }
        if (!empty($set)) {
            foreach ($set as $var => $value) {
                $query[$var] = $value;
            }
        }
        if ($query) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }


    /**
     * @return array
     */
    public function getProviders()
    {
        if (empty($this->config['HA']['providers'])) {
            return array();
        }

        $out = array();
        $url = $this->getUrl(array('hauth_action' => ''));
        $activeProviders = array();

        if ($uid = $this->modx->getLoginUserID('web')) {
            $activeProviders = $this->serviceModel->getActiveServices($uid);
        }
        $providers = array_keys($this->config['HA']['providers']);
        foreach ($providers as $provider) {
            $active = in_array($provider, $activeProviders);
            $action = $active ? 'unbind' : 'login';
            $out[] = array(
                'url'      => $url . $action . '&amp;provider=' . $provider,
                'provider' => strtolower($provider),
                'title'    => ucfirst($provider),
                'active'   => $active
            );
        }

        return $out;
    }
}
