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
                        <p>{l s='Your order is complete.' mod='walleta'}</p>

                        <div class="clearfix">
                            <a class="btn btn-primary float-xs-right"
                               href="{$link->getPageLink('history', true)|escape:'html':'UTF-8'}">
                                {l s='Order details' mod='walleta'}
                            </a>
                        </div>
                    </div><!-- /.cart-overview -->
                </div><!-- /.card -->
            </div><!-- /.cart-grid-body -->
        </div><!-- /.cart-grid -->
    </section>
{/block}
