# Changelog

All notable changes to this project are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.1.1] - 2026-10-26

### Fixed
- **Google Credentials JSON** source failed with "Initialization vector must be 32 bytes": the value was decrypted twice, once by ScopeConfig (Encrypted backend model declared in `config.xml`) and again in `EnterpriseConfig::getCredentials()`.

## [1.1.0] - 2026-10-23

### Added
- **Google Credentials Source** setting to choose between a file path on the server and an encrypted JSON.
- **Google Credentials JSON** setting: the Service Account JSON content is pasted in the admin and stored encrypted in the database (no file needed on the server). JSON is validated on save and the setting is flagged as sensitive (excluded from `app:config:dump`).

### Changed
- Added `magento/module-config` dependency.
- `EnterpriseConfig::getCredentialsFile()` replaced by `EnterpriseConfig::getCredentials()`, which returns either the file path or the decoded JSON array.

### Upgrade notes
- Backward compatible: the source defaults to **File path on server**, so an existing path setting keeps working without any change.

## [1.0.0] - 2026-04-17

### Added
- reCAPTCHA v3 token validation through the Google Cloud reCAPTCHA Enterprise API.
- Google Cloud Project ID and credentials JSON file path settings, with fallback to `GOOGLE_APPLICATION_CREDENTIALS` / Application Default Credentials.
- Dedicated log file `var/log/lohoy_recaptcha_enterprise.log`.
