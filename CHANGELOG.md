# Changelog

## [[1.5.0]](https://github.com/Payfast/zencart-aggregation/releases/tag/v1.5.0)

### Added

- **PHP 8.3 Support**: Full compatibility with PHP 8.3, including comprehensive type declarations.
- Added strict type declarations for all class properties and method signatures.
- Added return type declarations for all methods.
- Implemented modern PHP array syntax (`[]` instead of `array()`).
- Added explicit visibility modifiers for class constants (`public const`).

### Changed

- Updated all classes to use typed properties for better type safety.
- Modernised array syntax throughout the codebase.
- Enhanced null safety with proper type unions (e.g., `array|false`).
- Improved code quality with stricter type checking.

### Fixed

- Resolved potential null pointer issues with proper type declarations.
- Enhanced error handling with strict type enforcement.
- Fixed an installation issue that could cause a MySQL error (`Duplicate entry '1' for key 'PRIMARY'`) when creating
  default payment status records.
- Fixed order confirmation emails not being sent after successful payment (Tekmart) by adding defensive
  `class_exists()` checks in the ITN handler, sending confirmation emails via `zen_mail`, wrapping shipping module
  initialisation in a try/catch to suppress errors in the ITN context, and guarding the guest checkout session check
  against undefined keys.

## [[1.4.0]](https://github.com/Payfast/zencart-aggregation/releases/tag/v1.4.0)

### Added

- Updated branding to use the Payfast by Network logo.
- Revised configuration branding to Payfast Aggregation.

### Fixed

- Resolved a Payfast signature mismatch caused by HTML content being included in the `item_description` field by
  sanitising and normalising the value before signature generation.
- Prevented Payfast ITN failures by defensively defining missing Zen Cart category constants in the ITN context,
  eliminating fatal errors without requiring Zen Cart core modifications.

## [[1.3.0]](https://github.com/Payfast/zencart-aggregation/releases/tag/v1.3.0)

### Added

- Updated the Payfast common library to version 1.4.0.
- Code quality improvements.

## [[1.2.0]](https://github.com/Payfast/zencart-aggregation/releases/tag/v1.2.0)

### Added

- Branding update.
- Integration with the Payfast common library.
- Code quality improvements.

### Security

- General testing to ensure compatibility with latest Zencart version (2.0.1).

## [[1.1.4]](https://github.com/Payfast/zencart-aggregation/releases/tag/v1.1.4)

### Added

- Various ZenCart Notifiers for better compatibility.
- Code quality improvements.

### Fixed

- Update guest orders correctly with addresses.

### Security

- General testing to ensure compatibility with latest Zencart version (1.5.8).

## [[1.1.3]](https://github.com/Payfast/zencart-aggregation/releases/tag/v1.1.3)

### Added

- Update for PHP 8.0.

### Fixed

- General Fixes.

### Security

- General testing to ensure compatibility with latest Zencart version (1.5.8).
