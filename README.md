# ProVeg API extension

This extension provides customized interfaces for applications to communicate
with ProVeg CiviCRM. This is useful for synchronizing contact data, coming from
form submissions or other requests, with the CiviCRM instance in a streamlined
way without the need to understand the underlying data structure in CiviCRM.

## Requirements

Depending on the API call being issued, there may be dependencies. Those are
explicitly listed in the following API call documentation.

## Installation

Install like any other CiviCRM extension.

## Configuration

There is currently no user interface available for configuring the extension.

## Usage

The extension provides API calls. Invoking them may be done using any API method
supported by CiviCRM, e.g. the CiviCRM REST API. The site key and an API key may
be necessary.

### API calls

The extension provides the following API calls:

#### Donation/membership signup

- Entity: `ProvegDonation`
- Action: `Submit`

| Parameter               | Type   | Cardinality | Required                              | Allowed values                               | Description                                                                                                                                                                                        |
|-------------------------|--------|-------------|---------------------------------------|----------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `membership_type_id`    | int    | 1           | no                                    | Valid CiviCRM membership type IDs            | The CiviCRM membership type ID.                                                                                                                                                                    |
| `membership_subtype_id` | int    | 1           | yes, when membership_type_id provided | Valid CiviCRM membership custom field values | Mapping to the CiviCRM custom field "Beitragstyp"                                                                                                                                                  |
| `amount`                | int    | 1           | yes                                   | positive integers                            | The amount in EUR cents.                                                                                                                                                                           |
| `frequency`             | int    | 1           | yes                                   | positive integers                            | Number of installments per year. 0 for one-off.                                                                                                                                                    |
| `gender`                | string | 1           | no                                    | m or f                                       | The gender to use for selecting the salutation.                                                                                                                                                    |
| `first_name`            | string | 1           | yes                                   | maxlength 64                                 | The first name.                                                                                                                                                                                    |
| `last_name`             | string | 1           | yes                                   | maxlength 64                                 | The last name.                                                                                                                                                                                     |
| `email`                 | string | 1           | yes                                   | a valid e-mail address                       | The e-mail address.                                                                                                                                                                                |
| `street_address`        | string | 1           | yes                                   | maxlength 96                                 | The street address.                                                                                                                                                                                |
| `city`                  | string | 1           | yes                                   | maxlength 64                                 | The locality.                                                                                                                                                                                      |
| `postal_code`           | string | 1           | yes                                   | maxlength 64                                 | The postal/zip code.                                                                                                                                                                               |
| `country`               | string | 1           | yes                                   | Two-letter ISO 3166-1 code                   | The country.                                                                                                                                                                                       |
| `payment_instrument_id` | string | 1           | yes                                   | sepa or paypal                               | The payment method.                                                                                                                                                                                |
| `iban`                  | string | 1           | yes, when payment_instrument is sepa  | a valid IBAN                                 | The IBAN code.                                                                                                                                                                                     |
| `bic`                   | string | 1           | yes, when payment_instrument is sepa  | a valid BIC                                  | The BIC code.                                                                                                                                                                                      |
| `account_holder`        | string | 1           | no                                    |                                              | The account holder, if different from donor.                                                                                                                                                       |
| `newsletter`            | int    | 1           | yes                                   | 0 or 1                                       | Whether the donor wants to receive the newsletter. This will be passed to the ProvegNewsletterSubscription:submit API call internally to subscribe the contact to the configured newsletter group. |
| `receive_date`          | int    | 1           | no                                    | A UNIX timestamp                             | The date/time to use for recording when the contribution was received. Defaults to the request time of the API call.                                                                               |
| `contribution_source`   | string | 1           | no                                    |                                              | Some text to set the "Contribution source" field to for identifying where the contribution came from. Defaults to "ProVeg API"                                                                     |


#### Newsletter subscription

- Entity: `ProvegNewsletterSubscription`
- Action: `Submit`

| Parameter  | Type   | Cardinality | Required                           | Allowed values         | Description                                                                                                                                                                                              |
|------------|--------|-------------|------------------------------------|------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| email      | string | 1           | yes, if contact_id is not provided | a valid e-mail address | The e-mail address.                                                                                                                                                                                      |
| newsletter | int    | 1           | yes                                | 0 or 1                 | Whether to subscribe or unsubscribe the contact to/from the configured newsletter group.                                                                                                                 |
| contact_id | int    | 1           | yes, if email is not provided      | a valid contact_id     | The CiviCRM entity ID of the contact to (un-)subscribe to/from the configured newsletter group. Note: This is usually used internally only, as contact IDs are not neccesarily known outside the system. |
