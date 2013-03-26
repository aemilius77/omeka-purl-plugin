<?php

/**
 * Job_AlignPurl
 *
 * this method checks if there are already present purls;
 * (purl plugin might have been uninstalled and then reinstalled)
 *
 * @author Aemilius
 */
class Job_AlignPurl extends Job_Purl {

    public function perform() {

        if(USE_BATCH_XML) {

            $this->_performBatchUpload();

        } else {

            $this->_performProgressiveAlign();
        }
    }

    private function _performProgressiveAlign() {

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

    private function _performBatchUpload() {

        try {

            $itemTable = $this->_db->getTable('Item');

            $publicItems = $itemTable->findBy(array('public' => 1)); // public items

            if (count($publicItems) > 0) {

                $purlTable = $this->_db->getTable('Purl');

                // compose XML instance for batch upload

                $xmlDoc = new DOMDocument('1.0', 'ISO-8859-1');

                $root = $xmlDoc->createElement('purls');

                $xmlDoc->appendChild($root);

                // purl records values

                $purlRecordsValues = array();

                foreach ($publicItems as $publicItem) { // public item

                    $purlRecord = $purlTable->findBy(array('item_id' => $publicItem->id)); // already present purl

                    if (empty($purlRecord)) { // no purl for this public item; create

                        $targetURL = OMEKA_SERVER . PUBLIC_ITEM_PATH . $publicItem->id;

                        $purlId = generatePurlId($publicItem->id);

                        // purl element and attributes

                        $purlElement = $xmlDoc->createElement('purl');

                        $purlElement->setAttribute('id', $purlId);

                        $purlElement->setAttribute('type', '302');

                        // maintainers element and uid(s)

                        $maintainersElement = $xmlDoc->createElement('maintainers');

                        $uidElement = $xmlDoc->createElement('uid');

                        $uidElement->appendChild($xmlDoc->createTextNode(MAINTAINER_ID));

                        $maintainersElement->appendChild($uidElement);

                        // target element and url attribute

                        $targetElement = $xmlDoc->createElement('target');

                        $targetElement->setAttribute('url', $targetURL);

                        // append to purl element

                        $purlElement->appendChild($maintainersElement);

                        $purlElement->appendChild($targetElement);

                        // append purl element to root

                        $root->appendChild($purlElement);

                        // values for purl records in omeka db

                        $purlRecordsValues[] = array('itemId' => $publicItem->id, 'targetUrl' => $targetURL, 'purlValue' => PURL_SERVER . $purlId);
                    }

                    release_object($publicItem);
                }

                if ($xmlDoc->relaxNGValidate(dirname(__FILE__) . '/../../purls.rng')) { // rng validation

                    $authenticatedHttpClient = getAuthenticatedHttpClient();

                    $remotePath = '/admin/purls';

                    $request = $authenticatedHttpClient->post($remotePath, array('Content-Type' => 'text/xml'), $xmlDoc->saveXML());

                    $response = $request->send();

                    $statusCode = $response->getStatusCode();

                    if (strripos((string) $statusCode, '2') !== false) {

                        foreach ($purlRecordsValues as $purlRecordValues) {

                            createPurlRecord($purlRecordValues['itemId'], $purlRecordValues['targetUrl'], $purlRecordValues['purlValue']);

                            updateItemIdentifierTexts($purlRecordValues['itemId'], $purlRecordValues['purlValue']);
                        }
                    }
                }

                $this->_log(0, 'batch upload align'); // 0 means all item
            }

        } catch (Exception $e) {

            $this->_log(0, 'batch upload align', 1, $e->getMessage());
        }
    }

}

?>