<?php

class Job_AlignPurl extends Job_Purl {

    public function perform() {

        $itemTable = $this->_db->getTable('Item');

        $publicItems = $itemTable->findBy(array('public' => 1));

        $purlTable = $this->_db->getTable('Purl');

        foreach ($publicItems as $publicItem) {

            $purlRecord = $purlTable->findBy(array('item_id' => $publicItem->id));

            if (empty($purlRecord)) {

                try {

                    createPurl($publicItem->id);

                    $this->_log($publicItem->id, 'align');

                    release_object($publicItem);

                } catch (Exception $e) {

                    $this->_log($publicItem->id, 'align', 1, $e->getMessage());
                }
            }
        }
    }

}

?>