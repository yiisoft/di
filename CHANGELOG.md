# Yii Dependency Injection Change Log

## 1.5.1 under development

- no changes in this release.

## 1.5.0 November 22, 2026

- New #411: Add `$resetters` parameter to `StateResetter` constructor (@vjik)
- Chg #411: Deprecate `StateResetter::setResetters()`, use the constructor instead (@vjik)
- Chg #412: Replace Psalm with PHPStan (@vjik)
- Enh #397: Explicitly import functions in "use" section (@mspirkov)
- Enh #409: Remove unused `ext-mbstring` from `composer.json` requirements (@vjik)
- Enh #411: Allow closures with integer keys as resetters in `StateResetter::setResetters()` (@vjik)
- Enh #412: Add a check that the `ContainerInterface::class` definition is a container instance (@vjik)
- Enh #412: Narrow `BuildingException::getSolution()` and `NotFoundException::getSolution()` methods result type 
  to `string` (@vjik)
- Enh #413: Add PHP 8.6 support (@roxblnfk)
- Enh #416: Remove extra development files from the distribution archive (@vjik)
- Bug #401: Fix delegate lookup in `Container::has()` (@samdark)
- Bug #411: Fix previously retrieved `StateResetter` instances missing services created later (@vjik, @samdark)
- Bug #411: Fix `CompositeContainer` to return the same `StateResetter` instance on each `get()` call (@vjik)

## 1.4.1 December 01, 2025

- Enh #393: Add PHP 8.5 support (@vjik)

## 1.4.0 May 30, 2025

- New #380: Add `TagReference::id()` method (@vjik)
- Chg #390: Change PHP constraint in `composer.json` to `8.1 - 8.4` (@vjik)
- Enh #324: Make `BuildingException` and `NotFoundException` friendly (@np25071984)
- Enh #384: Make `$config` parameter in `Container` constructor optional (@np25071984)
- Enh #387: Improve container performance (@samdark)
- Bug #390: Explicitly mark nullable parameters (@vjik)

## 1.3.0 October 14, 2024

- Enh #352: Support parameters name binding (@xepozz)
- Enh #353: Add shortcut for tag reference #333 (@xepozz)
- Enh #356: Improve usage `NotFoundException` for cases with definitions (@vjik)
- Enh #364: Minor refactoring to improve performance of container (@samdark)
- Enh #375: Raise minimum PHP version to `^8.1` and refactor code (@vjik)
- Enh #376: Add default value `true` for parameter of `ContainerConfig::withStrictMode()` and
 `ContainerConfig::withValidate()` methods (@vjik)

## 1.2.1 December 23, 2022

- Chg #316: Fix exception messages (@xepozz)
- Bug #317: Fix delegated container (@xepozz)

## 1.2.0 November 05, 2022

- Chg #310: Adopt to `yiisoft/definition` version `^3.0` (@vjik)
- Enh #308: Raise minimum PHP version to `^8.0` and refactor code (@xepozz, @vjik)

## 1.1.0 June 24, 2022

- Chg #263: Raise minimal required version of `psr/container` to `^1.1|^2.0` (@xepozz, @vjik)

## 1.0.3 June 17, 2022

- Enh #302: Improve performance collecting tags (samdark)
- Enh #303: Add support for `yiisoft/definitions` version `^2.0` (@vjik)

## 1.0.2 February 14, 2022

- Bug #297: Fix method name `TagHelper::extractTagFromAlias` (@rustamwin)

## 1.0.1 December 21, 2021

- Bug #293: Fix `ExtensibleService` normalization bug (@yiiliveext)

## 1.0.0 December 03, 2021

- Initial release.
