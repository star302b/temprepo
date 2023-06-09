## To activate Picanova API

You should set API User and API Key here
WooCommerce -> Picanova API Settings

```
/wp-admin/admin.php?page=custom-options
```

## How To enable Picanova Shipping option

In order to enable this shipping option you should go to the next place:
WooCommerce -> Settings -> Shipping -> Picanova Shipping
And check "Enable" option.

```
/wp-admin/admin.php?page=wc-settings&tab=shipping&section=picanova_api
```

## How To set percenage for increase/decrease prices from API

You should go to the next place:
WooCommerce -> Picanova API Settings

Zero or empty value = no changes for the price
Above zero value = increase the price
Below zero value = decrease the price

```
/wp-admin/admin.php?page=custom-options
```