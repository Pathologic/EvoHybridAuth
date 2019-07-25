<?php
include_once MODX_BASE_PATH . 'assets/plugins/hybridauth/vendor/autoload.php';

use \EvoHybridAuth\Wrapper as EvoHybridAuth;
use \EvoHybridAuth\Manager\Plugin;

$e = $modx->event;
if (!isset($userModel)) {
    $userModel = '\modUsers';
}
switch ($e->name) {
    case 'OnWebPageInit':
    case 'OnPageNotFound':
        $user = new $userModel($modx);
        if (($uid = $modx->getLoginUserID('web')) && $user->edit($uid)->getID()) {
            if ($user->checkBlock()) {
                $user->logout();
                $modx->sendRedirect($modx->makeUrl(isset($logoutPage) ? $logoutPage : $modx->config['site_start'], '',
                    '', 'full'));
            }
        }

        if (!empty($_REQUEST['hauth_action']) || !empty($_REQUEST['hauth_start']) || !empty($_REQUEST['hauth_done'])) {
            /** @var EvoHybridAuth $HybridAuth */
            $HybridAuth = new EvoHybridAuth($modx);
            if (!empty($_REQUEST['hauth_action'])) {
                switch ($_REQUEST['hauth_action']) {
                    case 'login':
                        if (!empty($_REQUEST['provider'])) {
                            $HybridAuth->login($_REQUEST['provider']);
                        } else {
                            $HybridAuth->refresh();
                        }
                        break;
                    case 'unbind':
                        $HybridAuth->unbind($_REQUEST['provider']);
                        $HybridAuth->refresh();
                        break;
                }
            } else {
                $HybridAuth->processAuth();
            }
        }
        break;
    case 'OnWebAuthentication':
        $modx->event->setOutput(!empty($_SESSION['HybridAuth']['verified']));
        unset($_SESSION['HybridAuth']['verified']);
        break;
    case 'OnWebLogout':
        $HybridAuth = new EvoHybridAuth($modx);
        $HybridAuth->logout();
        break;
    case 'OnWUsrFormRender':
        global $modx_lang_attribute;
        $Plugin = new Plugin($modx, $modx_lang_attribute);
        if ($output = $Plugin->render()) {
            $modx->event->addOutput($output);
        }
        break;
    case 'OnWebDeleteUser':
        $modx->db->delete($modx->getFullTableName('ha_user_service'), "`internalKey` = {$userid}");
        break;
}
