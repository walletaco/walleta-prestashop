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
                        {if isset($paymentErrors)}
                            {foreach from=$paymentErrors item='error'}
                                <div class="alert alert-warning">
                                    {$error}
                                </div>
                            {/foreach}
                        {/if}

                        <form method="POST" action="{$link->getModuleLink('walleta', 'request', [], true)|escape:'html'}">
                            <p>
                                {l s='Please enter your National ID and mobile number to continue the payment process.' mod='walleta'}
                            </p>

                            <section class="form-fields">
                                <div class="form-group row ">
                                    <label class="col-md-3 form-control-label">
                                        {l s='National Code' mod='walleta'}
                                    </label>
                                    <div class="col-md-6">
                                        <input class="form-control" name="national_code" value="{$nationalCode}" maxlength="10">
                                    </div>
                                </div>

                                <div class="form-group row ">
                                    <label class="col-md-3 form-control-label">
                                        {l s='Mobile' mod='walleta'}
                                    </label>
                                    <div class="col-md-6">
                                        <input class="form-control" name="mobile" value="{$mobile}" maxlength="11">
                                    </div>
                                </div>
                            </section>

                            <footer class="form-footer clearfix">
                                <button type="submit" class="continue btn btn-primary float-xs-right">
                                    {l s='Continue' mod='walleta'}
                                </button>
                            </footer>
                        </form>
                    </div><!-- /.cart-overview -->
                </div><!-- /.card -->

                {block name='continue_shopping'}
                    <a class="label" href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
                        <i class="material-icons">chevron_left</i>{l s='Other payment methods' mod='walleta'}
                    </a>
                {/block}
            </div><!-- /.cart-grid-body -->
        </div><!-- /.cart-grid -->
    </section>
{/block}
