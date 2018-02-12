<?php

namespace EvoHybridAuth\Manager;

use AssetsHelper;
use DLTemplate;
use EvoHybridAuth\UserService;
use Helpers\FS;

/**
 * Class Plugin
 */
class Plugin extends \SimpleTab\Plugin
{
    public $pluginName = 'HybridAuth';
    public $tpl = 'assets/plugins/hybridauth/mgr/tpl/hybridauth.tpl';
    public $jsListDefault = 'assets/plugins/hybridauth/mgr/js/scripts.json';
    public $cssListDefault = 'assets/plugins/hybridauth/mgr/css/styles.json';
    public $table = 'ha_user_service';

    protected $checkTemplate = false;
    protected $renderEvent = 'OnWUsrFormRender';
    protected $checkId = false;

    /**
     * @param $modx
     * @param string $lang_attribute
     */
    public function __construct($modx, $lang_attribute = 'en')
    {
        $this->modx = $modx;
        $this->lang_attribute = $lang_attribute;
        $modx->event->_output = "";
        $this->DLTemplate = DLTemplate::getInstance($this->modx);
        $this->fs = FS::getInstance();
        $this->assets = AssetsHelper::getInstance($modx);
    }

    /**
     * @return array
     */
    public function getTplPlaceholders()
    {
        $ph = array(
            'lang'        => $this->lang_attribute,
            'uid'         => $this->modx->event->params['id'],
            'tabName'     => $this->modx->event->params['tabName'],
            'url'         => $this->modx->config['site_url'] . 'assets/plugins/hybridauth/mgr/ajax.php',
            'site_url'    => $this->modx->config['site_url'],
            'manager_url' => MODX_MANAGER_URL,
        );

        return $ph;
    }

    /**
     * @return string
     */
    public function render()
    {
        $this->createTable();
        $output = $this->prerender();
        if ($output !== false) {
            $ph = $this->getTplPlaceholders();
            $ph['js'] = $this->renderJS($this->jsListDefault, $ph);
            $ph['styles'] = $this->renderJS($this->cssListDefault, $ph);
            $output = $this->DLTemplate->parseChunk('@CODE:' . $output, $ph);
        }

        return $output;
    }

    /**
     * @return mixed
     */
    public function createTable()
    {
        $model = new UserService($this->modx);

        return $model->createTable();
    }
}
