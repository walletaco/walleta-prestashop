<?php

/**
 * @property Walleta $module
 */
class WalletaRequestModuleFrontController extends ModuleFrontController
{
    const PAYMENT_REQUEST_URL = 'https://cpg.walleta.ir/payment/request.json';
    const PAYMENT_GATEWAY_URL = 'https://cpg.walleta.ir/ticket/';

    public $ssl = true;
    public $display_column_left = false;

    /**
     * @return void
     * @throws \Exception
     */
    public function initContent()
    {
        parent::initContent();

        $this->canBeUsed();

        $customer = new Customer((int)($this->context->cart->id_customer));
        $address = new Address((int)$this->context->cart->id_address_invoice);
        $result = [
            'errors' => [],
            'redirect' => null,
        ];

        if (Tools::isSubmit('national_code') && Tools::isSubmit('mobile')) {
            $result = $this->processPaymentRequest($customer, $address);

            if ($result['redirect']) {
                $this->redirectTo($result['redirect'], '');
            }
        }

        $this->context->smarty->assign([
            'paymentErrors' => $result['errors'],
            'mobile' => $this->module->getCustomerMobile($address),
            'nationalCode' => $this->module->getCustomerNationalCode(),
        ]);

        $this->setTemplate($this->module->buildTemplatePath('payment_request'));
    }

    /**
     * @param Customer $customer Customer Object
     * @param Address $address Address Object
     * @return array
     */
    protected function processPaymentRequest($customer, $address)
    {
        $result = [
            'errors' => [],
            'redirect' => null,
        ];

        try {
            $params = $this->getPaymentRequestParams($this->context->cart, $customer, $address);

            if (!$params['payer_national_code']) {
                $result['errors'][] = $this->module->l('National Code is required.', 'request');
            }

            if (!$params['payer_mobile']) {
                $result['errors'][] = $this->module->l('Mobile is required.', 'request');
            }

            if ($result['errors']) {
                return $result;
            }

            $client = new \Walleta\Client\HttpRequest();
            $response = $client->post(self::PAYMENT_REQUEST_URL, $params);

            if (!$response->isSuccess()) {
                $result['errors'][] = $response->getErrorMessage();
                if ($response->getErrorType() === 'validation_error') {
                    $result['errors'] = array_merge($result['errors'], $response->getValidationErrors());
                }

                return $result;
            }

            $result['redirect'] = self::PAYMENT_GATEWAY_URL . $response->getData('token');
        } catch (\Exception $ex) {
            $result['errors'][] = $this->module->l('Error to get a payment token.', 'request');
        }

        return $result;
    }

    /**
     * @return void
     */
    protected function canBeUsed()
    {
        if (
            $this->context->cart->nbProducts() <= 0 ||
            $this->context->cart->id_customer === 0 ||
            $this->context->cart->id_address_delivery === 0 ||
            $this->context->cart->id_address_invoice === 0 ||
            !$this->module->active) {
            $this->redirectTo('index.php?controller=order&step=1');
        }

        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] === 'walleta') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'request'));
        }

        if (!$this->module->checkCurrency($this->context->cart)) {
            $this->redirectTo('index.php?controller=order&step=1');
        }
    }

    /**
     * @param string $url Url
     * @param string|null $base_uri Base URI
     * @return void
     */
    protected function redirectTo($url, $base_uri = null)
    {
        if ($base_uri === null) {
            $base_uri = __PS_BASE_URI__;
        }

        Tools::redirect($url, $base_uri);
        die;
    }

    /**
     * @param Cart $cart Cart Object
     * @param Customer $customer Customer Object
     * @param Address $address Address Object
     * @return array
     * @throws \Exception
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
}
