<?php

class Job_CreatePurl extends Job_Purl {

    private $_itemId;

    public function perform() {

        try {

            createPurl($this->_itemId);

            $this->_log($this->_itemId, 'create');

        } catch (Exception $e) {

            $this->_log($this->_itemId, 'create', 1, $e->getMessage());
        }
    }

    public function setItemId($itemId) {

        $this->_itemId = (int) $itemId;
    }

}

?>