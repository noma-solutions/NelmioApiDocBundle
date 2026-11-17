CHANGELOG
=========

4.0.0
-----

BC breaks
* Raised minimum requirements to PHP `>=8.2` and Symfony `^5.4|^6.0|^7.0`
  (dropped Symfony 3.4 / 4.x and PHP versions below 8.2).

Added
* PHP attributes support on `Areas`, `Model`, `Operation` and `Security` (still compatible with Doctrine annotations).
* `MappingAwareAnnotationReader` merges PHP attributes and docblock annotations for API metadata.
  Preference (order) is resolved from Doctrine ORM mapping `type` per namespace prefix, with fallback
  `nelmio_api_doc.metadata.default` (default: `annotation`).
  Attribute reading is limited to Nelmio/Swagger metadata; annotation reading stays unfiltered so
  FOSRest / Assert docblocks keep working.
* Optional override via container parameter `noma_solutions_api.metadata_mode_rules` (wins over Doctrine mappings).
* JMS: normalize serialization groups so project `default` and JMS `Default` are treated together.
* JMS: infer property types from PHP typed properties / `@var` when JMS metadata has no type
  (common after migrating Serializer metadata to attributes).

Fixed
* Register `AnnotationRegistry::registerUniqueLoader('class_exists')` on bundle boot so swagger-php's
  whitelist loader no longer blocks JMS / other annotations during `/api/doc` generation.


3.10.2
-----

* Fixes overwriting models with different hashes (name + serialization groups) - again


3.10.1
-----

* Fixes wrong type of int and float values for minimum/maximum properties


3.10.0
-----

* Fixes overwriting models with different hashes (name + serialization groups) 

3.7.0
-----

* Added `@SerializedName` annotation support and name converters when using Symfony >= 4.2.
* Removed pattern added from the Expression Violation message.
* Added FOSRestBundle 3.x support
* Added `@SWG` annotations support at methods level in models

3.3.0
-----

* Usage of Google Fonts was removed. System fonts `serif` / `sans` will be used instead.
  This can lead to a different look on different operating systems.
  You can [re-add Google Fonts again manually by overriding the template](https://symfony.com/doc/current/bundles/NelmioApiDocBundle/faq.html#re-add-google-fonts).

* The Twig template for the Swagger UI now contains blocks to make it easier to overwrite certain parts.
  See the [official documentation](https://symfony.com/doc/current/bundles/NelmioApiDocBundle/customization.html) how to do this.

3.2.0 (2018-03-24)
------------------

* Add a documentation form extension. Use the ``documentation`` option to define how a form field is documented.
* Allow references to config definitions in controllers.
* Using `@Model` implicitely in `@SWG\Schema`, `@SWG\Items` and `@SWG\Property` is deprecated. Use `ref=@Model()` instead.

  Before:
  ```php
  /**
   * This was considered as an array of models.
   *
   * @SWG\Property(@Model(type=FooClass::class))
   */
  ```

  After:
  ```php
  /**
   * For an individual object:
   * @SWG\Property(ref=@Model(type=FooClass::class))
   *
   * For an array:
   * @SWG\Property(type="array", @SWG\Items(ref=@Model(type=FooClass::class)))
   */
  ```

Config
* `nelmio_api_doc.areas` added support to filter by host patterns.

  ```yml
  nelmio_api_doc:
      areas: [ host_patterns: [ ^api\. ] ]
  ```

* Added dependency for "symfony/options-resolver:^3.4.4|^4.0"

3.1.0 (2018-01-28)
------------------

* Added Symfony Validator constraints support

Symfony Forms
* Support for boolean checkbox
* Support for integer

JMS Serializer
* Support JMS `int` (alias for `integer`)
* Also process phpdoc annotations

SwaggerPHP
* Handle `enum` and `default` properties from SwaggerPHP annotation
* Support `@Security` annotations

Config
* `nelmio_api_doc.routes` has been replaced by `nelmio_api_doc.areas`. Please update your config accordingly.

  Before:
  ```yml
  nelmio_api_doc:
      routes: [ path_patterns: [ /api ] ]
  ```

  After:
  ```yml
  nelmio_api_doc:
      areas: [ path_patterns: [ /api ] ]
  ```

3.0.0 (2017-12-10)
------------------

Large refactoring introducing `zircote/swagger-php` for swagger annotations.

See UPGRADE-3.0.md for upgrading instructions.
