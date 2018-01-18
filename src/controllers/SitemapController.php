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

use dolphiq\sitemap\models\SitemapEntryModel;
use dolphiq\sitemap\records\SitemapEntry;
use dolphiq\sitemap\records\SitemapCrawlerVisit;
use dolphiq\sitemap\Sitemap;

use Craft;
use craft\db\Query;
use craft\web\Controller;
use craft\helpers\UrlHelper;

use Jaybizzle\CrawlerDetect\CrawlerDetect;

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
class SitemapController extends Controller
{
    private $_sourceRouteParams = [];
    protected $allowAnonymous = ['index'];
    // Public Methods
// =========================================================================


    /**
     * @inheritdoc
     */
    private function getUrl($uri, $siteId)
    {
        if ($uri !== null) {
            $path = ($uri === '__home__') ? '' : $uri;
            return UrlHelper::siteUrl($path, null, null, $siteId);
        }

        return null;
    }

    /**
     * Handle a request going to our plugin's index action URL,
     * e.g.: actions/sitemap/default
     *
     * @return mixed
     */
    public function actionIndex()
    {

        try {
            // try to register the searchengine visit
            $CrawlerDetect = new CrawlerDetect;

            // Check the user agent of the current 'visitor'
            if($CrawlerDetect->isCrawler()) {
                // insert into table!
                $visit = new SitemapCrawlerVisit();
                $visit->name = $CrawlerDetect->getMatches();
                $visit->save();
            }
        } catch(\Exception $err) {

        }
        Craft::$app->response->format = \yii\web\Response::FORMAT_RAW;
        $headers = Craft::$app->response->headers;
        $headers->add('Content-Type', 'text/xml');

        $dom = new \DOMDocument('1.0','UTF-8');
        $dom->formatOutput = true;

        $urlset = $dom->createElement('urlset');
        $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $dom->appendChild($urlset);

        foreach($this->_createEntrySectionQuery()->all() as $item) {
            $loc = $this->getUrl($item['uri'], $item['siteId']);
            if($loc === null) continue;
            
            $url = $dom->createElement('url');
            $urlset->appendChild($url);
            $url->appendChild($dom->createElement('loc', $loc));
            $url->appendChild($dom->createElement('priority', $item['priority']));
            $url->appendChild($dom->createElement('changefreq', $item['changefreq']));
            $dateUpdated = strtotime($item['dateUpdated']);
            $url->appendChild($dom->createElement('lastmod', date('Y-m-d\TH:i:sP', $dateUpdated)));

        }

        foreach($this->_createEntryCategoryQuery()->all() as $item) {
            $loc = $this->getUrl($item['uri'], $item['siteId']);
            if($loc === null) continue;

            $url = $dom->createElement('url');
            $urlset->appendChild($url);
            $url->appendChild($dom->createElement('loc', $loc));
            $url->appendChild($dom->createElement('priority', $item['priority']));
            $url->appendChild($dom->createElement('changefreq', $item['changefreq']));
            $dateUpdated = strtotime($item['dateUpdated']);
            $url->appendChild($dom->createElement('lastmod', date('Y-m-d\TH:i:sP', $dateUpdated)));

        }
        return $dom->saveXML();
    }

    private function _createEntrySectionQuery(): Query
    {
        return (new Query())
            ->select([
                'elements_sites.uri uri',
                'elements_sites.dateUpdated dateUpdated',
                'elements_sites.siteId',
                'sitemap_entries.changefreq changefreq',
                'sitemap_entries.priority priority',
            ])
            ->from(['{{%sections}} sections'])
            ->innerJoin('{{%dolphiq_sitemap_entries}} sitemap_entries', '[[sections.id]] = [[sitemap_entries.linkId]] AND [[sitemap_entries.type]] = "section"')
            ->leftJoin('{{%structures}} structures', '[[structures.id]] = [[sections.structureId]]')
            ->innerJoin('{{%sections_sites}} sections_sites', '[[sections_sites.sectionId]] = [[sections.id]] AND [[sections_sites.hasUrls]] = 1')
            ->innerJoin('{{%entries}} entries', '[[sections.id]] = [[entries.sectionId]]')
            ->innerJoin('{{%elements}} elements', '[[entries.id]] = [[elements.id]] AND [[elements.enabled]] = 1')
            ->innerJoin('{{%elements_sites}} elements_sites', '[[elements_sites.elementId]] = [[elements.id]] AND [[elements_sites.enabled]] = 1')
            ->innerJoin('{{%sites}} sites', '[[elements_sites.siteId]] = [[sites.id]]')

            ->groupBy(['elements_sites.id']);
    }

    private function _createEntryCategoryQuery(): Query
    {
        return (new Query())
            ->select([

                'elements_sites.uri uri',
                'elements_sites.dateUpdated dateUpdated',
                'elements_sites.siteId',
                'sitemap_entries.changefreq changefreq',
                'sitemap_entries.priority priority',
            ])
            ->from(['{{%categories}} categories'])
            ->innerJoin('{{%dolphiq_sitemap_entries}} sitemap_entries', '[[categories.groupId]] = [[sitemap_entries.linkId]] AND [[sitemap_entries.type]] = "category"')
            ->innerJoin('{{%categorygroups_sites}} categorygroups_sites', '[[categorygroups_sites.groupId]] = [[categories.groupId]] AND [[categorygroups_sites.hasUrls]] = 1')
            ->innerJoin('{{%elements}} elements', '[[elements.id]] = [[categories.id]] AND [[elements.enabled]] = 1')
            ->innerJoin('{{%elements_sites}} elements_sites', '[[elements_sites.elementId]] = [[elements.id]] AND [[elements_sites.enabled]] = 1')
            ->innerJoin('{{%sites}} sites', '[[elements_sites.siteId]] = [[sites.id]]')
            ->groupBy(['elements_sites.id']);
    }

}

?>
