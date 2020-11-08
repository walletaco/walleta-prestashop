<?php

/**
 * @property Walleta $module
 */
class WalletaVerifyModuleFrontController extends ModuleFrontController
{
    const PAYMENT_VERIFY_URL = 'https://cpg.walleta.ir/payment/verify.json';

    public $ssl = true;
    public $display_column_left = false;

    /**
     * @return void
     * @throws \Exception
     */
    public function postProcess()
    {
        if (Tools::isSubmit('id_cart') === false || Tools::isSubmit('key') === false) {
            return;
        }

        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] === 'walleta') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'verify'));
        }

        $paymentStatus = Tools::getValue('status');
        $secureKey = Tools::getValue('key');
        $cartId = Tools::getValue('id_cart');
        $cart = new Cart((int)$cartId);

        if (!Validate::isLoadedObject($cart)) {
            $this->setErrorTemplate(
                $this->module->l('An error occurred. Please contact the merchant to have more information.', 'verify')
            );
            return;
        }

        if ($cart->orderExists()) {
            $order = $this->getOrder($cart->id);

            if (Validate::isLoadedObject($order) && $order->hasBeenPaid()) {
                $this->setTemplate($this->module->buildTemplatePath('payment_success'));
            } else {
                $this->setTemplate($this->module->l('Order has already been placed.', 'verify'));
            }

            return;
        }

        $customer = new Customer((int)$cart->id_customer);

        if ($secureKey !== $customer->secure_key) {
            $this->setErrorTemplate(
                $this->module->l('An error occurred. Please contact the merchant to have more information.', 'verify')
            );
            return;
        }

        if ($paymentStatus !== 'success') {
            $this->setErrorTemplate($this->module->l('Payment is canceled.', 'verify'));
            return;
        }

        try {
            $params = $this->getPaymentVerifyParams($cart);

            $client = new \Walleta\Client\HttpRequest();
            $response = $client->post(self::PAYMENT_VERIFY_URL, $params);

            if (!$response->isSuccess()) {
                $this->setErrorTemplate($response->getErrorMessage());
                return;
            }

            if ($response->getData('is_paid') !== true) {
                $this->setErrorTemplate($this->module->l('Order is not paid.', 'verify'));
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
                $this->setErrorTemplate($this->module->l('Unable to save the order.', 'verify'));
                return;
            }

            $this->setTemplate($this->module->buildTemplatePath('payment_success'));
        } catch (\Exception $ex) {
            $this->setErrorTemplate($ex->getMessage());
        }
    }

    /**
     * @param int $cartId Cart Id
     * @return \Order
     * @throws \Exception
     */
    public function getOrder($cartId)
    {
        $orderId = Order::getOrderByCartId($cartId);

        return new Order($orderId);
    }

    /**
     * @param Cart $cart Cart Object
     * @return array
     * @throws \Exception
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
        $this->context->smarty->assign([
            'paymentErrors' => (array)$message,
        ]);

        $this->setTemplate($this->module->buildTemplatePath('payment_failed'));
    }
}
