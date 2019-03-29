<?php
/**
 * sitemap plugin for Craft CMS 3.x
 *
 * Craft 3 plugin that provides an easy way to enable and manage a xml sitemap for search engines like Google
 *
 * @link      https://github.com/Dolphiq/craft3-plugin-sitemap
 * @copyright Copyright (c) 2017 Johan Zandstra
 */

namespace dolphiq\sitemap\services;

use Craft;
use craft\base\Component;
use craft\db\Table;
use craft\events\ConfigEvent;
use craft\events\RebuildConfigEvent;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use dolphiq\sitemap\records\SitemapEntry;

/**
 * SitemapService Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Johan Zandstra
 * @package   Sitemap
 * @since     1.0.0
 */
class SitemapService extends Component
{
    /**
     * Key for the project config
     */
    const PROJECT_CONFIG_KEY = 'dolphiq_sitemap_entries';
    // Public Methods
    // =========================================================================

    /**
     * Save a new entry to the project config
     *
     * @param \dolphiq\sitemap\records\SitemapEntry $record
     *
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception
     * @throws \yii\base\NotSupportedException
     * @throws \yii\web\ServerErrorHttpException
     * @return bool
     */
    public function saveEntry(SitemapEntry $record): bool
    {
        // is it a new one?
        $isNew = empty($record->id);
        if ($isNew) {
            $record->uid = StringHelper::UUID();
        } else {
            if (!$record->uid) {
                $record->uid = Db::uidById(SitemapEntry::tableName(), $record->id);
            }
        }

        if (!$record->validate()) {
            return false;
        }
        $path = self::PROJECT_CONFIG_KEY . ".{$record->uid}";

        $uidById = $record->type === 'section' ? Db::uidById(Table::SECTIONS, $record->linkId) : Db::uidById(
            Table::CATEGORIES,
            $record->linkId
        );

        // set it in the config
        Craft::$app->getProjectConfig()->set(
            $path,
            [
                'linkId'     => $uidById,
                'type'       => $record->type,
                'priority'   => $record->priority,
                'changefreq' => $record->changefreq,
            ]
        );

        if ($isNew) {
            $record->id = Db::idByUid(SitemapEntry::tableName(), $record->uid);
        }

        return true;
    }

    /**
     * Delete an entry from project config
     *
     * @param \dolphiq\sitemap\records\SitemapEntry $record
     */
    public function deleteEntry(SitemapEntry $record)
    {
        $path = self::PROJECT_CONFIG_KEY . ".{$record->uid}";
        Craft::$app->projectConfig->remove($path);
    }

    /**
     * handleChangedSiteMapEntry
     *
     * @param \craft\events\ConfigEvent $event
     */
    public function handleChangedSiteMapEntry(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];
        $id = Db::idByUid(SitemapEntry::tableName(), $uid);

        if ($id === null) {
            // new one
            $record = new SitemapEntry();
        } else {
            // update an existing one
            $record = SitemapEntry::findOne((int) $id);
        }

        $idByUid = $event->newValue['type'] === 'section' ? Db::idByUid(
            Table::SECTIONS,
            $event->newValue['linkId']
        ) : Db::idByUid(Table::CATEGORIES, $event->newValue['linkId']);

        $record->uid = $uid;
        $record->linkId = $idByUid;
        $record->type = $event->newValue['type'];
        $record->priority = $event->newValue['priority'];
        $record->changefreq = $event->newValue['changefreq'];
        $record->save();
    }

    /**
     * handleDeletedSiteMapEntry
     *
     * @param \craft\events\ConfigEvent $event
     *
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function handleDeletedSiteMapEntry(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];
        // grab the record
        $record = SitemapEntry::find()->where(['uid' => $uid])->one();
        if ($record === null) {
            return;
        }

        // delete it
        $record->delete();
    }

    /**
     * rebuildProjectConfig
     *
     * @param \craft\events\RebuildConfigEvent $e
     */
    public function rebuildProjectConfig(RebuildConfigEvent $e)
    {
        /** @var SitemapEntry[] $records */
        $records = SitemapEntry::find()->all();
        $e->config[self::PROJECT_CONFIG_KEY] = [];
        foreach ($records as $record) {
            $e->config[self::PROJECT_CONFIG_KEY][$record->uid] = [
                'linkId' => $this->getUidById($record),
                'type' => $record->type,
                'priority' => $record->priority,
                'changefreq' => $record->changefreq,
            ];
        }
    }

    public function getUidById(SitemapEntry $record)
    {
        $uid = $record->type === 'section' ? Db::uidById(
            Table::SECTIONS,
            $record->linkId
        ) : Db::uidById(Table::CATEGORIES, $record->linkId);

        return $uid;
    }
}
