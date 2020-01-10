<?php

/**
 *
 * @package chamilo.plugin.buycourses
 *
 */

require_once '../../config.php';
use ChamiloSession as Session;
use Transbank\Webpay\Configuration;
use Transbank\Webpay\Webpay;

Session::read('_user');
$plugin = BuyCoursesPlugin::create();
$transkbankParams = $plugin->getTransbankParams();
$configuration = new Configuration();

if((int)$transkbankParams['integration'] == 1){
    $transaction = (new Webpay(Configuration::forTestingWebpayPlusNormal()))->getNormalTransaction();
} else {
    $commerceCode = $transkbankParams['commerce_code'];
    $privateKeyWebPay = $transkbankParams['private_key'];
    $publicCertWebPay = $transkbankParams['public_cert'];
    $webPayCert = $configuration->getWebpayCert();

    $configuration->setEnvironment(Webpay::PRODUCCION);
    $configuration->setCommerceCode($commerceCode);
    $configuration->setPrivateKey($privateKeyWebPay);
    $configuration->setPublicCert($publicCertWebPay);
    $configuration->setWebpayCert($webPayCert);

    $transaction = (new Webpay($configuration))->getNormalTransaction();
}

$tokenWS = filter_input(INPUT_POST, 'token_ws');
$result = $transaction->getTransactionResult($tokenWS);
$output = $result->detailOutput;


$form = new FormValidator(
    'return-form',
    'post',
    $result->urlRedirection
);

if($output->responseCode == 0){

    $byOrderReference = $output->buyOrder;
    $sale = $plugin->getSaleReference($byOrderReference);

    echo '<script>window.localStorage.clear();</script>';
    echo '<script>window.localStorage.setItem("authorizationCode","'.$output->authorizationCode.'");</script>';
    echo '<script>window.localStorage.setItem("amount","'.$output->amount.'");</script>';
    echo '<script>window.localStorage.setItem("responseCode", "'.$output->responseCode.'");</script>';

    $plugin->completeSale($sale['id']);
    $form->addHidden('response',$output->responseCode);
    $form->addHidden('token_ws',$tokenWS);
    $form->display();

} else {

    $byOrderReference = $output->buyOrder;
    $sale = $plugin->getSaleReference($byOrderReference);
    $plugin->cancelSale($sale['id']);
    $form->addHidden('response',$output->responseCode);
    $form->addHidden('token_ws',$tokenWS);
    $form->display();
}

echo '
        <script>
            document.getElementById("return-form").submit();
        </script>
    ';

