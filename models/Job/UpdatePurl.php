<?php

/**
 * Job_UpdatePurl
 *
 * @author Aemilius
 */
class Job_UpdatePurl extends Job_Purl {

    private $_itemId;

    private $_purlRecordId;

    private $_purlType;

    public function perform() {

        try {

            $purlTable = $this->_db->getTable('Purl');

            $purlRecord = $purlTable->find($this->_purlRecordId);

            updatePurl($this->_itemId, $purlRecord, $this->_purlType);

            $this->_log($this->_itemId, 'update', 0, "set to $this->_purlType - purl record # $purlRecord->id");

        } catch (Exception $e) {

            $this->_log($this->_itemId, 'update', 1, $e->getMessage());
        }
    }

    public function setItemId($itemId) {

        $this->_itemId = (int) $itemId;
    }

    public function setPurlRecordId($purlRecordId) {

        $this->_purlRecordId = (int) $purlRecordId;
    }

    public function setPurlType($purlType) {

        $this->_purlType = (string) $purlType;
    }

}

?>