<?php
/**
 * Created by PhpStorm.
 * User: Pathologic
 * Date: 10.02.18
 * Time: 19:32
 */

namespace EvoHybridAuth\Manager;

use EvoHybridAuth\UserService;
use SimpleTab\AbstractController;

/**
 * Class Controller
 * @package EvoHybridAuth\Manager
 */
class Controller extends AbstractController
{
    public $rfName = 'internalKey';

    public $dlParams = array(
        "controller"  => "onetable",
        "table"       => "ha_user_service",
        'idField'     => "id",
        "api"         => 1,
        "idType"      => "documents",
        'ignoreEmpty' => 1,
        'JSONformat'  => "new",
        'display'     => 10,
        'offset'      => 0,
        'sortBy'      => "id",
        'sortDir'     => "desc",
    );

    /**
     * AbstractController constructor.
     * @param \DocumentParser $modx
     */
    public function __construct(\DocumentParser $modx)
    {
        parent::__construct($modx);
        $this->data = new UserService($modx);
        $this->dlInit();
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function remove()
    {
        $out = array();
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $out['success'] = false;
        if ($id) {
            if ($this->data->delete($id)) {
                $out['success'] = true;
            }
        }

        return $out;
    }
}
