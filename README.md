# µDashboard - an advanced dashboard API for Drupal

This initial version is a raw export of the *ucms_dashboard* from the
https://github.com/makinacorpus/drupal-ucms Drupal module suite. Only namespaces
have been changed, and a few utility functions moved from the *ucms_contrib*
module.

It should be stable enough to use.


## Runtime configuration

### Enable top toolbar

```php
$conf['udashboard.context_pane_enable'] = true;
```


### Enable context pane

```php
$conf['udashboard.context_pane_enable'] = true;
```


### Enable admin pages breadcrumb alteration

This is a very specific setting for usage with UCMS.

```php
$conf['udashboard.breadcrumb_alter'] = true;
```


## Display configuration

### Disable custom CSS

If you wish to embed this module's CSS or custom LESS into your own custom
theme, you might wish to disable CSS loading:

```php
$conf['udashboard.disable_css'] = true;
```


### Drupal seven theme fixes

By setting this to ``true``, seven fixes will always be included:

```php
$conf['udashboard.seven_force'] = true;
```

By setting it to ``false``, the will be always dropped.

By removing the variable or setting it to ``null`` seven admin theme will be
automatically detected at runtime and fixes will be loaded if necessary.

