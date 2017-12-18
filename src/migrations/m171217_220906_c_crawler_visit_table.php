<?php

namespace dolphiq\sitemap\migrations;

use Craft;
use craft\db\Migration;

/**
 * m171217_220906_c_crawler_visit_table migration.
 */
class m171217_220906_c_crawler_visit_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->tableExists('{{%dolphiq_sitemap_crawler_visits}}')) {

            $this->createTable(
                '{{%dolphiq_sitemap_crawler_visits}}',
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                    'name' => $this->string(150)->notNull()->defaultValue(''),
                ]
            );
            return true;
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTableIfExists('{{%dolphiq_sitemap_crawler_visits}}');
        return true;
    }
}
