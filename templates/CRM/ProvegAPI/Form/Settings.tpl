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
<div class="crm-section">
  <div class="label">{$form.selfservice_link_request_sender.label}</div>
  <div class="content">{$form.selfservice_link_request_sender.html}</div>
  <div class="clear"></div>
</div>
<div class="crm-section">
  <div class="label">{$form.selfservice_link_request_template_contact_known.label}</div>
  <div class="content">{$form.selfservice_link_request_template_contact_known.html}</div>
  <div class="clear"></div>
</div>
<div class="crm-section">
  <div class="label">{$form.selfservice_link_request_template_contact_unknown.label}</div>
  <div class="content">{$form.selfservice_link_request_template_contact_unknown.html}</div>
  <div class="clear"></div>
</div>
<div class="crm-section">
  <div class="label">{$form.selfservice_link_request_template_contact_ambiguous.label}</div>
  <div class="content">{$form.selfservice_link_request_template_contact_ambiguous.html}</div>
  <div class="clear"></div>
</div>


<br/>
<h3>{ts domain="com.proveg.api"}Personalised Links (Hash Links){/ts}</h3>
<div id="help">{ts domain="com.proveg.api"}You can use this function to generate tokens that contain a personalised link for contacts. This identifies the contact in an secure and anonymous way in any interaction with your website. However, since the personalised link would be sent out by email, this link can cause havoc if received by multiple contacts sharing the same email address. To avoid this, the token will be replaced with the fallback value below in that scenario.{/ts}</div>
{foreach from=$hash_links item=hash_link_index}
<div class="pv-hashlink-spec pv-hashlink-{$hash_link_index}" style="border-style: groove;">
  <h3>{ts 1=$hash_link_index domain="com.proveg.api"}Personalised Link #%1{/ts}</h3>
  <div class="crm-section crm-pv-hashlink-spec">
    {capture assign=field_name}hash_link_{$hash_link_index}{/capture}
    <div class="label">{$form.$field_name.label}</div>
    <div class="content">{$form.$field_name.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section crm-pv-hashlink-spec">
    {capture assign=field_name}hash_link_name_{$hash_link_index}{/capture}
    <div class="label">{$form.$field_name.label}</div>
    <div class="content">{$form.$field_name.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section crm-pv-hashlink-spec">
    {capture assign=field_name}hash_link_html_{$hash_link_index}{/capture}
    <div class="label">{$form.$field_name.label}</div>
    <div class="content">{$form.$field_name.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section crm-pv-hashlink-spec">
    {capture assign=field_name}hash_link_fallback_html_{$hash_link_index}{/capture}
    <div class="label">{$form.$field_name.label}</div>
    <div class="content">{$form.$field_name.html}</div>
    <div class="clear"></div>
  </div>
</div>
{/foreach}

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

{literal}
<script>
cj(document).ready(function() {
  /**
   * Make sure only one empty picker is showing
   */
  function hash_links_show_specs() {
    // first: identify the last picker that has a value
    let link_count = cj("div.pv-hashlink-spec").length;
    let last_link = 0;
    for (let i=1; i <= link_count; i++) {
      let selector = "div.pv-hashlink-" + i + " [name^=hash_link_]";
      if (cj(selector).val().length > 0) {
        last_link = i;
      }
    }

    // then: show every one before this, and hide every after
    for (let i=1; i <= link_count; i++) {
      let selector = "div.pv-hashlink-" + i;
      if (i <= last_link + 1) {
        cj(selector).show();
      } else {
        cj(selector).hide();
      }
    }
  }
  cj("[name^=hash_link_]").change(hash_links_show_specs);
  hash_links_show_specs();
});
</script>
{/literal}