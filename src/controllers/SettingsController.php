<?php
/**
 * sitemap plugin for Craft CMS 3.x
 *
 * Craft 3 plugin that provides an easy way to enable and manage a xml sitemap for search engines like Google
 *
 * @link      https://github.com/Dolphiq/craft3-plugin-sitemap
 * @copyright Copyright (c) 2017 Johan Zandstra
 */

namespace dolphiq\sitemap\controllers;

use dolphiq\sitemap\Sitemap;

use Craft;
use craft\db\Query;
use craft\web\Controller;

/**
 * Default Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Johan Zandstra
 * @package   Sitemap
 * @since     1.0.0
 */
class SettingsController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = false;

    private function _createEntrySectionQuery(): Query
    {
        return (new Query())
        ->select([
            'sections.id',
            'sections.structureId',
            'sections.name',
            'sections.handle',
            'sections.type',
            'sections.enableVersioning',
            'structures.maxLevels',
        ])
        ->leftJoin('{{%structures}} structures', '[[structures.id]] = [[sections.structureId]]')
        ->from(['{{%sections}} sections'])
        ->orderBy(['name' => SORT_ASC]);
    }


// Public Methods
// =========================================================================

/**
* Handle a request going to our plugin's index action URL,
* e.g.: actions/sitemap/default
*
* @return mixed
*/
    public function actionIndex(): craft\web\Response
    {
        $this->requireLogin();

        $routeParameters = Craft::$app->getUrlManager()->getRouteParams();

        $source = (isset($routeParameters['source'])?$routeParameters['source']:'CpSection');


        // $allSections = Craft::$app->getSections()->getAllSections();
        $allSections = $this->_createEntrySectionQuery()->all();
        $allStructures = [];
        // print_r($allSections);
        foreach($allSections as $section) {
            $allStructures[] = [
                'id' => $section['id'],
                'type'=> $section['type'],
                'heading' => $section['name'],
                'enabled' => true
            ];
        }
        $variables = [
            'settings' => Sitemap::$plugin->getSettings(),
            'source' => $source,
            'pathPrefix' => ($source == 'CpSettings' ? 'settings/': ''),
            'allStructures' => $allStructures
            // 'allRedirects' => $allRedirects
        ];

        return $this->renderTemplate('sitemap/index', $variables);
    }

    /**
     * Called when saving the settings.
     *
     * @return Response
     */
    public function actionSaveSitemap(): craft\web\Response
    {
        $this->requirePostRequest();
        $this->requireAdmin();
        $request = Craft::$app->getRequest();

    }

    /**
     * Handle a request going to our plugin's actionDoSomething URL,
     * e.g.: actions/sitemap/default/do-something
     *
     * @return mixed
     */
    public function actionDoSomething()
    {
        $result = 'Welcome to the DefaultController actionDoSomething() method';

        return $result;
    }
}
