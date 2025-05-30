# Yii Dependency Injection Change Log

## 1.4.0 May 30, 2025

- New #380: Add `TagReference::id()` method (@vjik)
- Chg #390: Change PHP constraint in `composer.json` to `8.1 - 8.4` (@vjik)
- Enh #324: Make `BuildingException` and `NotFoundException` friendly (@np25071984)
- Enh #384: Make `$config` parameter in `Container` constructor optional (@np25071984)
- Enh #387: Improve container performance (@samdark)
- Bug #390: Explicitly mark nullable parameters (@vjik)

## 1.3.0 October 14, 2024

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
