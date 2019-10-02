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

<br/>
<h3>{ts domain="com.proveg.api"}Personalised Links (Hash Links){/ts}</h3>
<div id="help">{ts domain="com.proveg.api"}You can use this function to generate tokens that contain a personalised link for contacts. This identifies the contact in an secure and anonymous way in any interaction with your website. However, since the personalised link would be sent out by email, this link can cause havoc if received by multiple contacts sharing the same email address. To avoid this, the token will be replaced with the fallback value below in that scenario.{/ts}</div>
{foreach from=$hash_links item=hash_link_index}
  <div class="crm-section crm-pv-hashlink-spec">
    {capture assign=field_name}hash_link_{$hash_link_index}{/capture}
    {$form.$field_name.label}</div>
    <div class="content">{$form.$field_name.html}</div>
    <div class="clear"></div>
  </div>
{/foreach}

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
