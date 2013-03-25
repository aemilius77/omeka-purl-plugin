<?php

/**
 * Job_SavePurl
 *
 * if you want to let the job decide what to do ON EVERY SAVE, use this class;
 *
 * @author Aemilius
 */
class Job_SavePurl extends Job_Purl {

    private $_itemId;

    public function perform() {

        try {

            $itemTable = $this->_db->getTable('Item');

            $item = $itemTable->find($this->_itemId);

            $purlTable = $this->_db->getTable('Purl');

            $purlRecord = $purlTable->findOneByItemId($this->_itemId);

            if ($item->public) { // public item

                if (empty($purlRecord)) { // no purl

                    createPurl($this->_itemId);

                    $this->_log($this->_itemId, 'create');

                } else { // a purl

                    if ($purlRecord->purl_type != '302') {

                        updatePurl($item->id, $purlRecord, '302');

                        $this->_log($this->_itemId, 'modify', 0, "set to 302 - purl record # $purlRecord->id");
                    }
                }

            } else { // non public item

                if (!empty($purlRecord)) { // a purl

                    if ($purlRecord->purl_type != '404') {

                        updatePurl($item->id, $purlRecord, '404');

                        $this->_log($this->_itemId, 'modify', 0, "set to 404 - purl record # $purlRecord->id");
                    }
                }
            }

        } catch (Exception $e) {

            $this->_log($this->_itemId, 'modify', 1, $e->getMessage());
        }
    }

    public function setItemId($itemId) {

        $this->_itemId = (int) $itemId;
    }

}

?>