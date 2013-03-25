<?php

/**
 * Purl
 *
 * @author Aemilius
 */
class Purl extends Omeka_Record_AbstractRecord {

    // id of the item object associated with this purl
    public $item_id;

    // target url - item url in omeka
    public $target_url;

    // purl
    public $purl;

    // purl type - 302 value is default
    public $purl_type;

    // 1, 0 - active, tombstoned
    public $purl_status;

    // purl creation date
    public $creation_date;

}

?>