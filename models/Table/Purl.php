<?php

/**
 * Table_Purl - an Omeka_Db_Table type
 *
 * @author Aemilius
 */
class Table_Purl extends Omeka_Db_Table {

    public function findOneByItemId($itemId) {

        $select = $this->getSelectForFindBy(array('item_id' => $itemId));

        return $this->fetchObject($select);
    }

}

?>
