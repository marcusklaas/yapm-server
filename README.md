php yapm server
===============

Password library endpoint for [yet-another-password-manager](https://github.com/marcusklaas/yet-another-password-manager).

Requires PHP >= 5.4.

This is a work in progress!

Getting started
---------------

- Clone repository: `$ git clone https://github.com/marcusklaas/yapm-server`
- Enter directory: `$ cd yapm-server`
- Install dependencies using composer: `$ composer install`

Config
------

The configuration is in config.yml

```yaml
master_key.allow_edit: bool
```
Allows you to toggle the ability to change the master key.
