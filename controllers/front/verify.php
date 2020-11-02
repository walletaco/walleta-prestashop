<?php

/**
 * @property Walleta $module
 */
class WalletaVerifyModuleFrontController extends ModuleFrontController
{
    /**
     * @return false|void
     * @throws \PrestaShopException
     */
    public function postProcess()
    {
        if (Tools::isSubmit('id_cart') === false || Tools::isSubmit('key') === false) {
            return false;
        }

        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] === 'walleta') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        $paymentStatus = Tools::getValue('status');
        $secureKey = Tools::getValue('key');
        $cartId = Tools::getValue('id_cart');
        $cart = new Cart((int)$cartId);

        if (!Validate::isLoadedObject($cart)) {
            $this->setErrorTemplate($this->module->l('An error occured. Please contact the merchant to have more informations'));
            return;
        }

        $customer = new Customer((int)$cart->id_customer);

        if ($secureKey !== $customer->secure_key) {
            $this->setErrorTemplate($this->module->l('An error occured. Please contact the merchant to have more informations'));
            return;
        }

        if ($paymentStatus !== 'success') {
            $this->setErrorTemplate($this->module->l('Payment is canceled.'));
            return;
        }

        try {
            $params = $this->getPaymentVerifyParams($cart);

            $response = (new \Walleta\Client\HttpRequest)
                ->post('https://cpg.walleta.ir/payment/verify.json', $params);

            if (!$response->isSuccess()) {
                $this->setErrorTemplate($response->getErrorMessage());
                return;
            }

            if ($response->getData('is_paid') !== true) {
                $this->setErrorTemplate('Order is not paid.');
                return;
            }

            $total = (float)$cart->getOrderTotal(true, Cart::BOTH);

            $result = $this->module->validateOrder(
                $cart->id,
                Configuration::get('PS_OS_PAYMENT'),
                $total,
                $this->module->displayName,
                null,
                [],
                (int)$cart->id_currency,
                false,
                $customer->secure_key
            );

            if (!$result) {
                $this->setErrorTemplate($this->module->l('Unable to save the order.'));
                return;
            }

            $this->setTemplate('module:walleta/views/templates/front/status.tpl');

            $this->context->smarty->assign([
                'redirectUrl' => '',
            ]);
        } catch (Exception $ex) {
            $this->setErrorTemplate($ex->getMessage());
        }
    }

    /**
     * @param Cart $cart Cart Object
     * @return array
     */
    protected function getPaymentVerifyParams($cart)
    {
        $currency = new Currency($cart->id_currency);
        $totalAmount = $this->module->formatMoney($currency, $cart->getOrderTotal(true, Cart::BOTH));

        return [
            'merchant_code' => Configuration::get('WALLETA_MERCHANT_CODE'),
            'token' => Tools::getValue('token'),
            'invoice_reference' => $cart->id,
            'invoice_amount' => $totalAmount,
        ];
    }

    /**
     * @param array|string $message
     * @return void
     * @throws \PrestaShopException
     */
    protected function setErrorTemplate($message)
    {
        $this->setTemplate('module:walleta/views/templates/front/error.tpl');

        $this->context->smarty->assign([
            'errors' => (array)$message,
        ]);
    }
}
