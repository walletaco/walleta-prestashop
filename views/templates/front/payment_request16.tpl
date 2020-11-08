{capture name=path}{l s='Pay by Walleta' mod='walleta'}{/capture}

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<form action="{$link->getModuleLink('walleta', 'request', [], true)|escape:'html'}" method="post">

    {if isset($paymentErrors)}
        {foreach from=$paymentErrors item='error'}
            <div class="alert alert-warning">
                {$error}
            </div>
        {/foreach}
    {/if}

    <div class="box walleta-box">
        <h3 class="page-subheading">{l s='Pay by Walleta' mod='walleta'}</h3>

        <p class="cheque-indent">
            <strong class="dark">
                {l s='Please enter your National ID and mobile number to continue the payment process.' mod='walleta'}
            </strong>
        </p>

        <p>
            <label>{l s='National Code' mod='walleta'}</label>
            <input type="text" name="national_code" value="{$nationalCode}" maxlength="10" class="form-control">

            <label>{l s='Mobile' mod='walleta'}</label>
            <input type="text" name="mobile" value="{$mobile}" maxlength="11" class="form-control">
        </p>
    </div>
    <p class="cart_navigation clearfix" id="cart_navigation">
        <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}"
           class="button-exclusive btn btn-default">
            <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='walleta'}
        </a>
        <button type="submit" class="button btn btn-default button-medium">
            <span>{l s='Continue' mod='walleta'}<i class="icon-chevron-right right"></i></span>
        </button>
    </p>
</form>
