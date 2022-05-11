<?php

namespace humhub\modules\smartVillage;

use Yii;
use yii\helpers\Url;

class Module extends \humhub\components\Module
{
    /**
    * @inheritdoc
    */

    /**
     * @var string Prefix for REST API endpoint URLs
     */
    const API_URL_PREFIX = 'api/v2/';

    public function getConfigUrl()
    {
        return Url::to(['/smartVillage/admin']);
    }

    /**
    * @inheritdoc
    */
    public function disable()
    {
        // Cleanup all module data, don't remove the parent::disable()!!!
        parent::disable();
    }

    /**
     * Add REST API endpoint rules
     *
     * @param array $rules
     * @param string $moduleId Provide module id if you want to make if disabled from settings of the module "REST API"
     */
    public function addRules($rules, $moduleId = null)
    {
        if ($moduleId !== null && !$this->isActiveModule($moduleId)) {
            return;
        }

        foreach ($rules as $r => $rule) {
            if (isset($rule['pattern'])) {
                $rules[$r]['pattern'] = self::API_URL_PREFIX . ltrim($rule['pattern'], '/');
            }
        }

        Yii::$app->urlManager->addRules($rules);
    }


    /**
     * Check if the module is active for additional REST API endpoints
     *
     * @param string $moduleId
     * @return bool
     */
    public function isActiveModule($moduleId)
    {
        $apiModules = (array)$this->settings->getSerialized('apiModules');

        return !isset($apiModules[$moduleId]) || $apiModules[$moduleId];
    }
}
