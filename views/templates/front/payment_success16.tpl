{capture name=path}{l s='Pay by Walleta' mod='walleta'}{/capture}

<div class="box walleta-box">
    <h3 class="page-subheading">{l s='Pay by Walleta' mod='walleta'}</h3>

    <p>{l s='Your order is complete.' mod='walleta'}</p>
</div>

<p class="cart_navigation clearfix" id="cart_navigation">
    <a class="button btn btn-default button-medium" href="{$link->getPageLink('history', true)|escape:'html':'UTF-8'}">
        <span>{l s='Order details' mod='walleta'}<i class="icon-chevron-right right"></i></span>
    </a>
</p>
