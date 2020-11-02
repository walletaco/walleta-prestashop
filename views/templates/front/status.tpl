{extends "$layout"}

{block name="content"}
    <section>
        <p>{l s='You have successfully submitted your payment form.' mod='walleta'}</p>

        <p class="cart_navigation">
            <a class="btn btn-primary" href="{$redirectUrl}">
                {l s='Order details' mod='walleta'}
            </a>
        </p>
    </section>
{/block}
