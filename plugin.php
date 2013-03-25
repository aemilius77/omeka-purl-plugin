<?php

/**
 * put a purl.ini file in the root dir (Purl/purl.ini)
 *
 * [purlz]
 * purl_server = "http://your.purlz.server"
 * purl_domain = "/your/domain"
 * maintainer_id = "username"
 * maintainer_password = "password"
 * use_batch_xml = false
 * [omeka]
 * omeka_server = "http://your.omeka.server"
 * public_item_path = "/items/show/"
 */

$properties = parse_ini_file(dirname(__FILE__).'/purl.ini');

define('PURL_SERVER', $properties['purl_server']);

define('PURL_DOMAIN', $properties['purl_domain']);

define('MAINTAINER_ID', $properties['maintainer_id']);

define('MAINTAINER_PASSWORD', $properties['maintainer_password']);

define('USE_BATCH_XML', $properties['use_batch_xml']);

define('OMEKA_SERVER', $properties['omeka_server']);

define('PUBLIC_ITEM_PATH', $properties['public_item_path']);

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

    /**
     * hookInstall
     *
     * when you install the plugin, this method launches a long running job to create a purl for every already existing public item
     *
     * @param type $args
     */
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

        $this->_sendAlign(); // align
    }

    /**
     * filterItemCitation
     *
     * @param string $citation
     * @param type $args
     * @return string
     */
    public function filterItemCitation($citation, $args) {

        $item = $args['item'];

        $purlTable = $this->_db->getTable('Purl');

        $purlRecord = $purlTable->findOneByItemId($item->id);

            if(!empty($purlRecord)) {

                $citation .= '<hr />Purl: ' . $purlRecord->purl;
            }

        return $citation;
    }

    /**
     * hookAfterSaveItem
     *
     * this method is called after EVERY item save (insert or update);
     * to avoid launching a purl create/update job when unnecessary, this method contains some logic;
     * you could launch a job on every save (delegating all logic to the job), but you'd have a great deal of running jobs;
     *
     * @param type $args
     */
    public function hookAfterSaveItem($args) {

        // item
        $item = $args['record'];

        // purl record
        $purlTable = $this->_db->getTable('Purl');

        $purlRecord = $purlTable->findOneByItemId($item->id);

        if ($item->public) { // public item

            if (empty($purlRecord)) { // if there isn't a purl for an existing public item

                $this->_sendCreate($item->id); // create a purl

            } else { // if there is an associated purl

                if ($purlRecord->purl_type != '302') { // if the purl's type is not 302

                    $this->_sendUpdate($item->id, $purlRecord->id, '302'); // update the purl; set the type to 302
                }
            }

        } else { // non public item

            if (!empty($purlRecord)) { // there is an associated purl

                if ($purlRecord->purl_type != '404') { // if the purl's type is not 404

                    $this->_sendUpdate($item->id, $purlRecord->id, '404'); // update the purl; set the type to 404
                }
            }
        }
    }

    /**
     * hookAfterDeleteItem
     *
     * this method contains some logic to avoid launching a purl deletion job on every delete
     *
     * @param type $args
     */
    public function hookAfterDeleteItem($args) {

        // item
        $item = $args['record'];

        // purl record
        $purlTable = $this->_db->getTable('Purl');

        $purlRecord = $purlTable->findOneByItemId($item->id);

        if (!empty($purlRecord)) { // if there is an associated purl

            $this->_sendDelete($item->id, $purlRecord->id); // delete purl
        }
    }

    // send align
    private function _sendAlign() {

        $jobDispatcher = Zend_Registry::get('bootstrap')->getResource('jobs');

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