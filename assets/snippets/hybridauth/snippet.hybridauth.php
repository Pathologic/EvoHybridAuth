<?php
include_once(MODX_BASE_PATH . 'assets/plugins/hybridauth/vendor/autoload.php');

use \EvoHybridAuth\Wrapper as EvoHybridAuth;
use \Helpers\Lexicon;

$DLTemplate = \DLTemplate::getInstance($modx);

$lang = isset($lang) ? $lang : $modx->config['manager_language'];
$Lexicon = new Lexicon($modx, array(
    'langDir' => 'assets/snippets/hybridauth/lang/',
    'lang'    => $lang
));
$Lexicon->loadLang('hybridauth');
$langDir = isset($langDir) ? $langDir : 'assets/snippets/hybridauth/lang/';
if (isset($lexicon)) {
    $Lexicon->loadLang($lexicon, $lang, $langDir);
}

if (!isset($tpl)) {
    $tpl = '@CODE:[+providers+][+error+]';
}

if (!isset($providerTpl)) {
    $providerTpl = '@CODE:<a href="[+url+]" class="ha-icon [+classNames+]" rel="nofollow" title="[+title+]">[+title+]</a>';
}

if (!isset($activeProviderTpl)) {
    $activeProviderTpl = $providerTpl;
}

if (!isset($errorTpl)) {
    $errorTpl = '@CODE:<div class="error">[+error+]</div>';
}

$HybridAuth = new EvoHybridAuth($modx);
$providers = $HybridAuth->getProviders();
$error = '';
if (!empty($_SESSION['HA']['error'])) {
    $error = $_SESSION['HA']['error'];
    unset($_SESSION['HA']['error']);
}

$out = array('providers' => $providers, 'error' => $error);
if (!empty($tpl)) {
    $out['providers'] = '';
    foreach ($providers as $provider) {
        $provider['classNames'] = $provider['provider'];
        if ($provider['active']) {
            $provider['classNames'] .= ' active';
        }
        $out['providers'] .= $DLTemplate->parseChunk($provider['active'] ? $activeProviderTpl : $providerTpl,
            $provider);
    }
    $out['error'] = $DLTemplate->parseChunk($errorTpl, array('error' => $Lexicon->getMsg($error)));
    $out = $DLTemplate->parseChunk($tpl, $out);
}

if ($out) {
    $out = $Lexicon->parseLang($out);
}

if (isset($registerCss) && $registerCss == 1) {
    $modx->regClientCss('assets/snippets/hybridauth/css/default.css');
}

return $out;
