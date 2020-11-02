<?php

/**
 * @property Walleta $module
 */
class WalletaRequestModuleFrontController extends ModuleFrontController
{
    /**
     * This class should be use by your Instant Payment
     * Notification system to validate the order remotely
     */
    public function postProcess()
    {
        $cart = $this->context->cart;

        if (
            $cart->nbProducts() <= 0 ||
            $cart->id_customer === 0 ||
            $cart->id_address_delivery === 0 ||
            $cart->id_address_invoice === 0 ||
            !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
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

        try {
            $customer = new Customer((int)($cart->id_customer));
            $address = new Address((int)$cart->id_address_invoice);

            $params = $this->getPaymentRequestParams($cart, $customer, $address);

            if (!$params['payer_national_code']) {
                $this->setErrorTemplate($this->module->l('National Code is required.'));
                return;
            }

            if (!$params['payer_mobile']) {
                $this->setErrorTemplate($this->module->l('Mobile is required.'));
                return;
            }

            $response = (new \Walleta\Client\HttpRequest)
                ->post('https://cpg.walleta.ir/payment/request.json', $params);

            if (!$response->isSuccess()) {
                $errors = (array)$response->getErrorMessage();
                if ($response->getErrorType() === 'validation_error') {
                    $errors = array_merge($errors, $response->getValidationErrors());
                }
                $this->setErrorTemplate($errors);
                return;
            }

            $redirectUrl = 'http://cpg.walleta.test/ticket/' . $response->getData('token');
            Tools::redirect($redirectUrl, '');
        } catch (Exception $ex) {
            $this->setErrorTemplate($this->module->l('Error to get a payment token.'));
        }
    }

    /**
     * @param Cart $cart Cart Object
     * @param Customer $customer Customer Object
     * @param Address $address Address Object
     * @return array
     */
    protected function getPaymentRequestParams($cart, $customer, $address)
    {
        $currency = new Currency($cart->id_currency);

        $shippingCost = $this->module->formatMoney($currency, $cart->getTotalShippingCost(null, false));
        $totalAmount = $this->module->formatMoney($currency, $cart->getOrderTotal(true, Cart::BOTH));

        $callbackUrl = $this->context->link->getModuleLink($this->module->name, 'verify', [
            'id_cart' => $cart->id,
            'id_module' => (int)$this->module->id,
            'key' => $customer->secure_key,
        ], true);

        $data = [
            'merchant_code' => Configuration::get('WALLETA_MERCHANT_CODE'),
            'invoice_reference' => $cart->id,
            'invoice_date' => date('c'),
            'invoice_amount' => $totalAmount,
            'payer_first_name' => $customer->firstname,
            'payer_last_name' => $customer->lastname,
            'payer_national_code' => $this->module->getCustomerNationalCode(),
            'payer_mobile' => $this->module->getCustomerMobile($address),
            'callback_url' => $callbackUrl,
            'description' => 'پرداخت سفارش #' . $cart->id,
            'items' => [],
        ];

        foreach ($cart->getProducts() as $product) {
            $data['items'][] = [
                'reference' => $product['id_product'],
                'name' => $product['name'],
                'quantity' => $product['quantity'],
                'unit_price' => $this->module->formatMoney($currency, $product['price']),
                'unit_discount' => 0,
                'unit_tax_amount' => $this->module->formatMoney($currency, $product['price_wt'] - $product['price']),
                'total_amount' => $this->module->formatMoney($currency, $product['total_wt']),
            ];
        }

        if ($shippingCost > 0) {
            $shippingCostInclTax = $this->module->formatMoney($currency, $cart->getTotalShippingCost());

            $data['items'][] = [
                'name' => 'هزینه ارسال',
                'quantity' => 1,
                'unit_price' => $shippingCost,
                'unit_discount' => 0,
                'unit_tax_amount' => $shippingCostInclTax - $shippingCost,
                'total_amount' => $shippingCostInclTax,
            ];
        }

        return $data;
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
