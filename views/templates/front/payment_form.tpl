<form id="payment-form" method="POST" action="{$action}">
    <label>{l s='National Code' mod='walleta'}</label>
    <input type="text" name="national_code" value="" maxlength="10">

    {if !$mobile}
        <label>{l s='Mobile' mod='walleta'}</label>
        <input type="text" name="mobile" value="{$mobile}" maxlength="11">
    {/if}

    <p></p>
</form>
