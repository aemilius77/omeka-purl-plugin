<?php

require_once(dirname(__FILE__) . '/../../helpers/PurlFunctions.php');

/**
 * Job_Purl
 *
 * @author Aemilius
 */
abstract class Job_Purl extends Omeka_Job_AbstractJob {

    protected function _log($itemId, $operation, $isError = 0, $message = null) {

        $purlLog = new PurlLog();

        $purlLog->item_id = $itemId;

        $purlLog->operation = $operation;

        $purlLog->is_error = $isError;

        if (!empty($message)) {

            $purlLog->message = $message;
        }

        $purlLog->save(true);
    }

}

?>