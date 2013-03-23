<?php

define('PURL_SERVER', '');

define('PURL_DOMAIN', '');

define('MAINTAINER_ID', '');

define('MAINTAINER_PASSWORD', '');

define('OMEKA_SERVER', '');

define('PUBLIC_ITEM_PATH', '/items/show/');

/**
 * PurlHookPlugin
 *
 * @author Aemilius
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */
class PurlHookPlugin extends Omeka_Plugin_AbstractPlugin {

    protected $_filters = array('item_citation');

    protected $_hooks = array('install', 'after_save_item', 'after_delete_item');

    public function hookInstall($args) {

        $db = $this->_db;

        $purlTable = "CREATE TABLE IF NOT EXISTS `$db->Purls`(
        `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `item_id` INT(10) UNSIGNED NOT NULL,
        `target_url` VARCHAR(255) NOT NULL,
        `purl` VARCHAR(255) NOT NULL,
        `purl_type` VARCHAR(3) NOT NULL,
        `purl_status` TINYINT(1) NOT NULL DEFAULT 0,
        `creation_date` DATE NOT NULL,
        `last_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
        )
        ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        $db->query($purlTable);

        $purlLogTable = "CREATE TABLE IF NOT EXISTS `$db->PurlLogs`(
        `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `item_id` INT(10) UNSIGNED NOT NULL,
        `operation` VARCHAR(255) NOT NULL,
        `message` VARCHAR(1020) DEFAULT NULL,
        `is_error` TINYINT(1) NOT NULL DEFAULT 0,
        `last_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
        )
        ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        $db->query($purlLogTable);

        $this->_sendAlign();
    }

    public function filterItemCitation($citation, $args) {

        $item = $args['item'];

        $purlTable = $this->_db->getTable('Purl');

        $purlRecord = $purlTable->findOneByItemId($item->id);

            if(!empty($purlRecord)) {

                $citation .= '<hr />Purl: ' . $purlRecord->purl;
            }

        return $citation;
    }

    public function hookAfterSaveItem($args) {

        // item
        $item = $args['record'];

        // purl record
        $purlTable = $this->_db->getTable('Purl');

        $purlRecord = $purlTable->findOneByItemId($item->id);

        // avoid launching a purl create/update job on every item save
        // can this check (db query for pur record) slow down UI?

        if ($item->public) { // public item

            if (empty($purlRecord)) { // no purl for existing item; create

                $this->_sendCreate($item->id);

            } else { // a purl

                if ($purlRecord->purl_type != '302') { // update existing, non 302 purl; set type to 302

                    $this->_sendUpdate($item->id, $purlRecord->id, '302');
                }
            }

        } else { // non public item

            if (!empty($purlRecord)) { // a purl

                if ($purlRecord->purl_type != '404') { // update existing, non 404 purl; set type to 404

                    $this->_sendUpdate($item->id, $purlRecord->id, '404');
                }
            }
        }
    }

    public function hookAfterDeleteItem($args) {

        $item = $args['record'];

        $purlTable = $this->_db->getTable('Purl');

        $purlRecord = $purlTable->findOneByItemId($item->id);

        // avoid launching a purl deletion job on every delete

        if (!empty($purlRecord)) {

            $this->_sendDelete($item->id, $purlRecord->id); // it would be better to pass complex objects..., but cannot pass...
        }
    }

    // send align
    private function _sendAlign() {

        $jobDispatcher = Zend_Registry::get('bootstrap')->getResource('jobs');

        // think no queue is needed for this one shot op...

        $jobDispatcher->sendLongRunning('Job_AlignPurl');
    }

    // send create
    private function _sendCreate($itemId) {

        $jobDispatcher = Zend_Registry::get('bootstrap')->getResource('jobs');

        $jobDispatcher->setQueueNameLongRunning('purl_create_queue');

        $jobDispatcher->sendLongRunning('Job_CreatePurl', array('itemId' => $itemId));
    }

    // send update
    private function _sendUpdate($itemId, $purlRecordId, $purlType) {

        $jobDispatcher = Zend_Registry::get('bootstrap')->getResource('jobs');

        $jobDispatcher->setQueueNameLongRunning('purl_update_queue');

        $jobDispatcher->sendLongRunning('Job_UpdatePurl', array('itemId' => $itemId, 'purlRecordId' => $purlRecordId, 'purlType' => $purlType));
    }

    // send delete
    private function _sendDelete($itemId, $purlRecordId) {

        $jobDispatcher = Zend_Registry::get('bootstrap')->getResource('jobs');

        $jobDispatcher->setQueueNameLongRunning('purl_delete_queue');

        $jobDispatcher->sendLongRunning('Job_DeletePurl', array('itemId' => $itemId, 'purlRecordId' => $purlRecordId));
    }
}

$purlHookPlugin = new PurlHookPlugin();

$purlHookPlugin->setUp();

?>
