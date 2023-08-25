# Changelog

All notable changes to `url-signer` will be documented in this file

## 2.1.0 - 2023-08-25

### What's Changed

- fix typo in test description by @debuqer in https://github.com/spatie/url-signer/pull/52
- feat: switch to DateTimeInterface by @alanpoulain in https://github.com/spatie/url-signer/pull/53

### New Contributors

- @debuqer made their first contribution in https://github.com/spatie/url-signer/pull/52
- @alanpoulain made their first contribution in https://github.com/spatie/url-signer/pull/53

**Full Changelog**: https://github.com/spatie/url-signer/compare/2.0.2...2.1.0

## 2.0.2 - 2023-04-06

- revert breaking change introduced in previous version

## 2.0.1 - 2023-04-05

### What's Changed

- Bump dependabot/fetch-metadata from 1.3.5 to 1.3.6 by @dependabot in https://github.com/spatie/url-signer/pull/41
- feat: switch DateTime to DateTimeInterface by @pauljosephkrogulec in https://github.com/spatie/url-signer/pull/45

### New Contributors

- @dependabot made their first contribution in https://github.com/spatie/url-signer/pull/41
- @pauljosephkrogulec made their first contribution in https://github.com/spatie/url-signer/pull/45

**Full Changelog**: https://github.com/spatie/url-signer/compare/2.0.0...2.0.1

## 2.0.0 - 2022-11-12

- internal rewrite
- do not rely on League packages anymore
- lifetime is now in seconds instead of days
- drop support for older PHP versions

## 1.2.3 - 2022-09-20

### What's Changed

- Fix php-cs-fixer by @erikn69 in https://github.com/spatie/url-signer/pull/37
- Fix bug validating url without query by @emmanuel-tilleuls in https://github.com/spatie/url-signer/pull/38

### New Contributors

- @erikn69 made their first contribution in https://github.com/spatie/url-signer/pull/37
- @emmanuel-tilleuls made their first contribution in https://github.com/spatie/url-signer/pull/38

**Full Changelog**: https://github.com/spatie/url-signer/compare/1.2.2...1.2.3

## 1.2.2 - 2021-04-20

- add missing abstract method in the BaseUrlSigner (#35)

## 1.2.1 - 2021-02-05

- improve depdendencies

## 1.2.0 - 2020-12-02

- support PHP 8.0

## 1.1.0 - 2020-07-20

- replace league/url with league/uri & league/uri-components (#25)

## 1.0.2 - 2017-04-09

- use `hash_equals` to avoid timing attacks

## 1.0.1

- Fixed: using empty signature keys is not allowed

## 1.0.0

- First release
