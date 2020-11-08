{$page.page_name='checkout'}

{extends file='checkout/checkout.tpl'}

{block name='content'}
    <section id="content">
        <div class="cart-grid row">
            <div class="cart-grid-body col-xs-12 col-lg-12">
                <div class="card cart-container">
                    <div class="card-block">
                        <h1 class="h1">{l s='Pay by Walleta' mod='walleta'}</h1>
                    </div>

                    <hr class="separator">

                    <div class="cart-overview">
                        <h3>{l s='An error occurred' mod='walleta'}:</h3>

                        {if isset($paymentErrors)}
                            {foreach from=$paymentErrors item='error'}
                                <div class="alert alert-warning">
                                    {$error}
                                </div>
                            {/foreach}
                        {/if}
                    </div><!-- /.cart-overview -->
                </div><!-- /.card -->

                {block name='continue_shopping'}
                    <a class="label" href="{$link->getPageLink('order', true, NULL, 'step=3')|escape:'html':'UTF-8'}">
                        <i class="material-icons">chevron_left</i>{l s='Other payment methods' mod='walleta'}
                    </a>
                {/block}
            </div><!-- /.cart-grid-body -->
        </div><!-- /.cart-grid -->
    </section>
{/block}
