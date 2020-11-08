{capture name=path}{l s='Pay by Walleta' mod='walleta'}{/capture}

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<div class="box walleta-box">
    <h3 class="page-subheading">{l s='Pay by Walleta' mod='walleta'}</h3>

    {if isset($paymentErrors)}
        {foreach from=$paymentErrors item='error'}
            <div class="alert alert-warning">
                {$error}
            </div>
        {/foreach}
    {/if}
</div>

<p class="cart_navigation clearfix" id="cart_navigation">
    <a href="{$link->getPageLink('order', true, NULL, 'step=3')|escape:'html':'UTF-8'}"
       class="button-exclusive btn btn-default">
        <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='walleta'}
    </a>
</p>
