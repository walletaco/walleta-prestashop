{extends file='checkout/checkout.tpl'}

{block name="content"}
	<section id="content">
		<div class="row">
			<div class="col-md-12">
				<section id="checkout-payment-step" class="checkout-step -current -reachable js-current-step">
					<h1 class="text-xs-center">{l s='Order payment' mod='walleta'}</h1>

					<div>
						<h3>{l s='An error occurred' mod='walleta'}:</h3>
						<ul class="alert alert-danger">
							{foreach from=$errors item='error'}
								<li>{$error|escape:'htmlall':'UTF-8'}</li>
							{/foreach}
						</ul>
					</div>

					<a href="{$link->getPageLink('order', true, NULL, "step=3")}"
					   class="btn btn-default">
						{l s='Other payment methods' mod='walleta'}
					</a>
				</section>
			</div>
		</div>
	</section>
{/block}
