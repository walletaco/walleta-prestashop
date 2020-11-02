<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'walleta/lib/walleta.php';

class Walleta extends PaymentModule
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'walleta';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'Mahmood Dehghani';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Walleta');
        $this->description = $this->l('Walleta - Credit Payment Gateway');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        Configuration::updateValue('WALLETA_MERCHANT_CODE', '');

        return parent::install() &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('paymentOptions');
    }

    public function uninstall()
    {
        Configuration::deleteByName('WALLETA_MERCHANT_CODE');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $output = '';

        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitWalletaModule')) == true) {
            $errors = $this->postValidation();

            if (!$errors) {
                $this->postProcess();
            } else {
                foreach ($errors as $error) {
                    $output .= $this->displayError($error);
                }
            }
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitWalletaModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-key"></i>',
                        'desc' => $this->l('Enter your merchant code'),
                        'name' => 'WALLETA_MERCHANT_CODE',
                        'label' => $this->l('Merchant Code'),
                        'class' => 'walleta-text-left',
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'WALLETA_MERCHANT_CODE' => Configuration::get('WALLETA_MERCHANT_CODE'),
        );
    }

    /**
     * Validate form data.
     */
    protected function postValidation()
    {
        $errors = [];

        if (!Tools::getValue('WALLETA_MERCHANT_CODE')) {
            $errors[] = $this->l('Merchant code is required.');
        }

        return $errors;
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            $value = Tools::getValue($key);
            Configuration::updateValue($key, trim($value));
        }
    }

    /**
     * Return payment options available for PS 1.7+
     *
     * @param array Hook parameters
     * @return array
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return [];
        }
        if (!$this->checkCurrency($params['cart'])) {
            return [];
        }
        if (!Configuration::get('WALLETA_MERCHANT_CODE')) {
            return [];
        }

        $address = new Address($params['cart']->id_address_invoice);
        $mobile = $this->getCustomerMobile($address);

        $option = new PaymentOption();
        $option->setCallToActionText($this->l('Pay by Walleta'))
            ->setForm($this->generateForm($mobile));

        return [$option];
    }

    /**
     * @param string $mobile
     * @return string
     */
    protected function generateForm($mobile)
    {
        $this->context->smarty->assign([
            'action' => $this->context->link->getModuleLink($this->name, 'request', [], true),
            'mobile' => $mobile,
        ]);

        return $this->context->smarty->fetch('module:walleta/views/templates/front/payment_form.tpl');
    }

    /**
     * @param Address $address Address Object
     * @return string
     */
    public function getCustomerMobile($address)
    {
        if (\Walleta\Validation::mobile($address->phone_mobile)) {
            return $address->phone_mobile;
        }

        if (\Walleta\Validation::mobile($address->phone)) {
            return $address->phone;
        }

        $mobile = Tools::getValue('mobile', '');
        if (\Walleta\Validation::mobile($mobile)) {
            return $mobile;
        }

        return '';
    }

    /**
     * @return string
     */
    public function getCustomerNationalCode()
    {
        $nationalCode = Tools::getValue('national_code', '');
        if (\Walleta\Validation::nationalCode($nationalCode)) {
            return $nationalCode;
        }

        return '';
    }

    /**
     * @param Cart $cart Cart Object
     * @return bool
     */
    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id === (int)$currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param Currency $currency Currency Object
     * @param float $amount
     * @return float
     */
    public function formatMoney($currency, $amount)
    {
        $amount = (float)(number_format($amount, 2, '.', ''));

        if ($currency->iso_code === 'IRR') {
            $amount = $amount / 10;
        }

        return round($amount);
    }
}
