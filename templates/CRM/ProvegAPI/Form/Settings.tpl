{*------------------------------------------------------------+
| ProVeg API extension                                        |
| Copyright (C) 2017-2019 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                      |
|         J. Schuppe (schuppe@systopia.de)                    |
+-------------------------------------------------------------+
| This program is released as free software under the         |
| Affero GPL license. You can redistribute it and/or          |
| modify it under the terms of this license which you         |
| can read by viewing the included agpl.txt or online         |
| at www.gnu.org/licenses/agpl.html. Removal of this          |
| copyright header is strictly prohibited without             |
| written permission from the original author(s).             |
+------------------------------------------------------------*}

<br/>
<h3>{ts domain="com.proveg.api"}General Settings{/ts}</h3>
<div class="crm-section">
  <div class="label">{$form.log_api_calls.label}</div>
  <div class="content">{$form.log_api_calls.html}</div>
  <div class="clear"></div>
</div>

<br/>
<h3>{ts domain="com.proveg.api"}Mailing Self-Service (<code>ProvegSelfservice.*</code>){/ts}</h3>
<div class="crm-section">
  <div class="label">{$form.selfservice_xcm_profile.label}</div>
  <div class="content">{$form.selfservice_xcm_profile.html}</div>
  <div class="clear"></div>
</div>


<br/><h3>{ts domain='com.proveg.api'}Mailing Config{/ts}</h3><br/>

<div class="crm-section">
  <div class="label">{$form.mailing_confirmation_endpoint.label} <a onclick='CRM.help("{ts domain='com.proveg.api'}Confirmation Endpoint{/ts}", {literal}{"id":"id-provegapi-confirmation-endpoint","file":"CRM\/ProvegAPI\/Form\/Settings"}{/literal}); return false;' href="#" title="{ts domain='com.proveg.api'}Help{/ts}" class="helpicon">&nbsp;</a></div>
  <div class="content">{$form.mailing_confirmation_endpoint.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.mailing_unsubscription_endpoint.label} <a onclick='CRM.help("{ts domain='com.proveg.api'}Confirmation Endpoint{/ts}", {literal}{"id":"id-provegapi-unsubscribe-endpoint","file":"CRM\/ProvegAPI\/Form\/Settings"}{/literal}); return false;' href="#" title="{ts domain='com.proveg.api'}Help{/ts}" class="helpicon">&nbsp;</a></div>
  <div class="content">{$form.mailing_unsubscription_endpoint.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.mailing_default_group_id.label} <a onclick='CRM.help("{ts domain='com.proveg.api'}Default Group{/ts}", {literal}{"id":"id-provegapi-default-group","file":"CRM\/ProvegAPI\/Form\/Settings"}{/literal}); return false;' href="#" title="{ts domain='com.proveg.api'}Help{/ts}" class="helpicon">&nbsp;</a></div>
  <div class="content">{$form.mailing_default_group_id.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.mailing_xcm_profile.label} <a onclick='CRM.help("{ts domain='com.proveg.api'}Confirmation Endpoint{/ts}", {literal}{"id":"id-provegapi-xcm-profile","file":"CRM\/ProvegAPI\/Form\/Settings"}{/literal}); return false;' href="#" title="{ts domain='com.proveg.api'}Help{/ts}" class="helpicon">&nbsp;</a></div>
  <div class="content">{$form.mailing_xcm_profile.html}</div>
  <div class="clear"></div>
</div>

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
