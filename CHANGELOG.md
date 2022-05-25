# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [v0.2]

New endpoint for space membership requests, updated conversations to have a read/unread status and versioning

### Added

- added endpoint for membership requests `/space/{id}/membership/{userid}/request` to spaces
- added read/unread status of conversations with a new `/mail` that includes

  ```
    status: 'read' | 'unread',
    seen_at: '2022-05-20 11:56:27'
  ```

  with a logic to mark conversations read on calling `/mail/{id}/entries`


### Changed

- decided for versioning and increased the version form 0.1 to 0.2

### Fixed

- added privacy policy check to new `/auth/register` if legal module is installed (https://www.humhub.com/de/marketplace/legal)

## [v0.1]

Initial setup of the new module to extend functionalities of https://github.com/humhub for Smart Village App.

### Added

- added registration endpoint `/auth/register` with accepting the following mandatory fields

  ```
  {
    account: {
      username: "username",
      email: "email@email.email"
    },
    profile: {
      firstname: "firstname",
      lastname: "lastname"
    },
    password: {
      newPassword: "newPassword",
      newPasswordConfirm: "newPasswordConfirm"
    }
  }
  ```
