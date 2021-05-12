# PrestaShop integration for TrustProfile and WebwinkelKeur

Learn more about [WebwinkelKeur](https://www.webwinkelkeur.nl).

Learn more about [TrustProfile](https://www.trustprofile.io).

To install the PrestaShop integration, download it from the [release
page][releases] and upload it to your shop.

[releases]: https://github.com/webwinkelkeur/prestashop/releases

## Changelog

### 2.4 (2021-05-12)

* Fix default configuration values for old PrestaShop versions.

### 2.3 (2020-11-18)

* Speed up the query that retrieves orders for which an invite should be sent,
  by allowing the relevant indexes to be used.

### 2.2 (2020-09-16)

* Update sidebar script so that it only makes one HTTP request.

### 2.1 (2020-06-18)

* Enable the JavaScript integration for all shops in a multistore install, not
  just the currently selected shop.

### 2.0 (2020-06-04)

* Refactored the module to support both WebwinkelKeur and TrustProfile on a
  single codebase.
