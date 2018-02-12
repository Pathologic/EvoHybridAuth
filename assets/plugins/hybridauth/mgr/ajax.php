<?php
define('MODX_API_MODE', true);
define('IN_MANAGER_MODE', 'true');

include_once(__DIR__ . "/../../../../index.php");
include_once(MODX_BASE_PATH . 'assets/plugins/hybridauth/vendor/autoload.php');
$modx->db->connect();
if (empty ($modx->config)) {
    $modx->getSettings();
}
if (!isset($_SESSION['mgrValidated'])) {
    die();
}
$modx->invokeEvent('OnManagerPageInit', array('invokedBy' => 'EvoHybridAuth'));
$mode = (isset($_REQUEST['mode']) && is_scalar($_REQUEST['mode'])) ? $_REQUEST['mode'] : null;
$out = null;
$controllerClass = '\EvoHybridAuth\Manager\Controller';
$controller = new $controllerClass($modx);
if ($controller instanceof \SimpleTab\AbstractController) {
    if (!empty($mode) && method_exists($controller, $mode)) {
        $out = call_user_func_array(array($controller, $mode), array());
    } else {
        $out = call_user_func_array(array($controller, 'listing'), array());
    }
    $controller->callExit();
}

echo($out = is_array($out) ? json_encode($out) : $out);
