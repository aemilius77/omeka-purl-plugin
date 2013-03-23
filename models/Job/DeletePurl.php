<?php

class Job_DeletePurl extends Job_Purl {

    private $_itemId;

    private $_purlRecordId;

    public function perform() {

        try {

            $purlTable = $this->_db->getTable('Purl');

            $purlRecord = $purlTable->find($this->_purlRecordId);

            deletePurl($this->_itemId, $purlRecord);

            $this->_log($this->_itemId, 'delete');

        } catch (Exception $e) {

            $this->_log($this->_itemId, 'delete', 1, $e->getMessage());
        }
    }

    public function setItemId($itemId) {

        $this->_itemId = (int) $itemId;
    }

    public function setPurlRecordId($purlRecordId) {

        $this->_purlRecordId = (int) $purlRecordId;
    }

}

?>