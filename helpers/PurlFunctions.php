<?php

require_once(dirname(__FILE__).'/../lib/guzzle.phar');

use Guzzle\Http\Client;
use Guzzle\Plugin\Cookie\CookiePlugin;
use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;

function createPurl($itemId) {

    $authenticatedHttpClient = getAuthenticatedHttpClient();

    $targetURL = OMEKA_SERVER . PUBLIC_ITEM_PATH . $itemId;

    $data = array('target' => $targetURL, 'maintainers' => MAINTAINER_ID, 'type' => '302');

    $purlId = generatePurlId($itemId);

    $remotePath = '/admin/purl' . $purlId;

    $request = $authenticatedHttpClient->post($remotePath, null, $data);

    $response = $request->send();

    $statusCode = $response->getStatusCode();

    if (strripos((string) $statusCode, '2') !== false) {

        $purlRecord = new Purl(); // new purl

        $purlRecord->item_id = $itemId;

        $purlRecord->target_url = $targetURL;

        $purlRecord->purl = PURL_SERVER . $purlId;

        $purlRecord->purl_type = '302';

        $purlRecord->purl_status = 1;

        $purlRecord->creation_date = date('Y-m-d');

        $purlRecord->save(true); // save new purl

        updateItemIdentifierTexts($itemId, $purlRecord); // update identifier texts
    }
}

function updatePurl($itemId, $purlRecord, $type) {

    $authenticatedHttpClient = getAuthenticatedHttpClient();

    $purlId = generatePurlId($itemId);

    $remotePath = '/admin/purl' . $purlId;

    $request = $authenticatedHttpClient->put($remotePath);

    $request->getQuery()->set('type', $type);

    $request->getQuery()->set('seealso', '');

    $request->getQuery()->set('maintainers', MAINTAINER_ID);

    $request->getQuery()->set('target', $purlRecord->target_url);

    $request->setBody('');

    $response = $request->send();

    $statusCode = $response->getStatusCode();

    if (strripos((string) $statusCode, '2') !== false) {

        $purlRecord->purl_type = $type;

        $purlRecord->save(true);
    }
}

function deletePurl($itemId, $purlRecord) {

    $authenticatedHttpClient = getAuthenticatedHttpClient();

    $remotePath = '/admin/purl' . generatePurlId($itemId);

    $request = $authenticatedHttpClient->delete($remotePath);

    $response = $request->send();

    $statusCode = $response->getStatusCode();

    if (strripos((string) $statusCode, '2') !== false) {

        $purlRecord->purl_status = 0;

        $purlRecord->save(true);
    }
}

function generatePurlId($itemId) {

    $purlId = PURL_DOMAIN . '/' . $itemId;

    return $purlId;
}

function updateItemIdentifierTexts($itemId, $purlRecord) {

    $dcIdentifierTexts = get_db()->getTable('ElementText')->findBy(array('record_id' => $itemId, 'element_id' => 43));

    $firstFreeIndex = 0;

    if (!empty($dcIdentifierTexts)) {

        $firstFreeIndex = count($dcIdentifierTexts);
    }

    $options = array('overwriteElementTexts' => false); // does it work? need to use $firstFreeIndex not to get rid of any other identifier

    $elementTexts = array(
        'Dublin Core' => array(
            'Identifier' => array($firstFreeIndex =>
                array('text' => $purlRecord->purl, 'html' => false))
        )
    );

    update_item($itemId, $options, $elementTexts); // update item identifiers; this fires save again
}

function getAuthenticatedHttpClient() {

    $authenticationParams = array('id' => MAINTAINER_ID, 'passwd' => MAINTAINER_PASSWORD, 'referrer' => OMEKA_SERVER);

    $remotePath = '/admin/login/login-submit.bsh';

    $client = new Client(PURL_SERVER);

    $cookiePlugin = new CookiePlugin(new ArrayCookieJar());

    $client->addSubscriber($cookiePlugin);

    $request = $client->post($remotePath, null, $authenticationParams);

    $request->send();

    return $client;
}

?>