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
$transaction = (new Webpay(Configuration::forTestingWebpayPlusNormal()))->getNormalTransaction();
$tokenWS = filter_input(INPUT_POST, 'token_ws');
$result = $transaction->getTransactionResult($tokenWS);
$output = $result->detailOutput;
$plugin = BuyCoursesPlugin::create();

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

