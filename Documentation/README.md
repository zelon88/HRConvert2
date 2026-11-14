# 📚 HRConvert2: README - User Guide

Welcome to the official `Documentation/` folder! This document outlines the contents of the `Documentation/` folder and how to make use of them.

---

## 🛠️ Core Infrastructure Specifications

### Legal & Distribution
* **Intellectual Property:** HRConvert2 is Copyright © 2026 by Justin Grimes ([GitHub](https://www.github.com/zelon88)).
* **Licensing Model:** Protected under the strict terms of the **GNU GPLv3 Open-Source License**. Refer to the [Official License Portal](https://www.gnu.org/licenses/gpl-3.0.html) for distribution requirements.

### System Intent
This application provides a highly accessible web interface enabling file conversions directly on a server infrastructure using any modern web browser. It needs no account, & optional accounts, off by default, let a user keep files of their own.

| Metric | Minimum Operational Requirements |
| :--- | :--- |
| **Hardware** | Raspberry Pi Model B+ (or any standard x86 / x64 architecture computing platform) |
| **Environment** | Debian Linux + Apache Web Server v2.4 + PHP v8.0 or greater |

> ⚙️ **Core Dependencies:** The authoritative list of dependencies is `Resources/Contract/depends.php` and nothing else. Run `php convertCore.php -v` to see what this installation has.

---

## 🗺️ The Central Documentation Library

Every significant subject inside this folder comes packaged in **two distinct flavors**:

* `.txt` files act as rigid, uniform, and hyper-concise engineering specification stripped of unnecessary fluff or prose.
* `.md` files resemble a premium, colorful user manual that adds clear aesthetic value for hobbyists who enjoy interacting with it.

```text
📁 /Documentation
   ├── 📄 ABOUT_DOCUMENTATION.txt   <-- The Authoritative Source of Truth
   └── 🎨 ABOUT_DOCUMENTATION.md    <-- The Vibrant User Manual
```

This structural separation guarantees an absolute boundary between raw, unyielding engineering blueprints and polished, accessible user instructions.

---

## 📑 Table of Contents

Navigate the complete documentation library using the directory structure below.

### ⚙️ Command Line & System Architecture
* [ABOUT_COMMAND_LINE_ARGUMENTS.md](ABOUT_COMMAND_LINE_ARGUMENTS.md) / [.txt](ABOUT_COMMAND_LINE_ARGUMENTS.txt) — CLI execution parameters.
* [ABOUT_DEPENDENCIES.txt](ABOUT_DEPENDENCIES.txt) — How a dependency is declared, found, approved & audited.
* [DEPENDENCY_APPROVALS.txt](DEPENDENCY_APPROVALS.txt) — The approval record for the application's own dependencies. Each pipeline carries its own.
* [2026_SUPPLY_CHAIN_AUDIT.xlsx](2026_SUPPLY_CHAIN_AUDIT.xlsx) — The 2026 supply chain audit the approval records were written from.
* [DOCKER_BUILD_INSTRUCTIONS.txt](DOCKER_BUILD_INSTRUCTIONS.txt) — Container deployment guide.

### 💾 Asset Generation
* [CREATING_PRE-BAKED_UEFI_ASSET_IMAGES.md](CREATING_PRE-BAKED_UEFI_ASSET_IMAGES.md) / [.txt](CREATING_PRE-BAKED_UEFI_ASSET_IMAGES.txt) — Prebaked UEFI boot assets.
* [ABOUT_CREATING_BOOTABLE_ISO_IMAGES.md](ABOUT_CREATING_BOOTABLE_ISO_IMAGES.md) / [.txt](ABOUT_CREATING_BOOTABLE_ISO_IMAGES.txt) — ISO creation protocols.

### 🛠️ Developer & Pipeline Resources
* [CODING_CONVENTIONS.md](CODING_CONVENTIONS.md) / [.txt](CODING_CONVENTIONS.txt) — Code style guidelines.
* [CREATING_CONVERSION_PIPELINES.txt](CREATING_CONVERSION_PIPELINES.txt) — Processing engine manual.
* [CREATING_GUIS.txt](CREATING_GUIS.txt) — Frontend design notes.
* [CREATING_LANGUAGE_PACKS.txt](CREATING_LANGUAGE_PACKS.txt) — Localization frameworks.

### 🔐 Security
* [SECURITY.md](SECURITY.md) / [.txt](SECURITY.txt) — The security policy. Supported versions, how to report a vulnerability privately & how reporters are credited.
* [ABOUT_SCAD_SANITIZATION.txt](ABOUT_SCAD_SANITIZATION.txt) — How an uploaded OpenSCAD source is stripped of its file references before it renders.
* [ABOUT_NETWORK_ISOLATION.txt](ABOUT_NETWORK_ISOLATION.txt) — How a sandbox profile is arrived at & what every permission in it costs.
* [ABOUT_DATA_DIRECTORY_PROTECTION.txt](ABOUT_DATA_DIRECTORY_PROTECTION.txt) — How the hosted DATA tree is served as inert content.
* [ABOUT_MANAGED_LOCATIONS.txt](ABOUT_MANAGED_LOCATIONS.txt) — Who owns each location on disk, & what root will & will not do inside them.
* [ABOUT_DEFENSIVE_MEMORY_MANAGEMENT.md](ABOUT_DEFENSIVE_MEMORY_MANAGEMENT.md) — Memory leak prevention strategies.
* [ABOUT_QUALITY_CONTROL.txt](ABOUT_QUALITY_CONTROL.txt) — The static analysis gate every change has to pass.

### 🧩 The Engine & Its Components
* [ABOUT_ENGINE_CONTRACT.txt](ABOUT_ENGINE_CONTRACT.txt) — What an application must supply for the Engine to run.
* [ABOUT_ENGINE_ABSORPTION.txt](ABOUT_ENGINE_ABSORPTION.txt) — How core logic was moved into the Engine.
* [ENGINE_MIGRATION_STATUS.txt](ENGINE_MIGRATION_STATUS.txt) — How far the migration has actually got, & the check that proves each line.
* [ABOUT_COMPONENTS.txt](ABOUT_COMPONENTS.txt) — How a detachable component is versioned & loaded.
* [ABOUT_PIPELINE_COMPONENTS.txt](ABOUT_PIPELINE_COMPONENTS.txt) — The contracts every conversion pipeline obeys.
* [ABOUT_WORKFLOWS.txt](ABOUT_WORKFLOWS.txt) — Workflows. Their format, their libraries, how one runs & what a page is told.
* [ABOUT_MANAGERS.txt](ABOUT_MANAGERS.txt) — The manager processes & the protocol between them.
* [ABOUT_ENVIRONMENT_MANAGER.txt](ABOUT_ENVIRONMENT_MANAGER.txt) — The privileged repair process & what it may change.
* [ABOUT_RESOURCE_RATE_LIMITING.md](ABOUT_RESOURCE_RATE_LIMITING.md) / [.txt](ABOUT_RESOURCE_RATE_LIMITING.txt) — Budgets, data locations & scheduled cleanup.
* [ABOUT_CAPABILITY_DETECTION.txt](ABOUT_CAPABILITY_DETECTION.txt) — How the application decides what it can do on this host.
* [ABOUT_COHESION_PASS.txt](ABOUT_COHESION_PASS.txt) — How a function is judged to belong where it lives.
* [ABOUT_LOGGING.txt](ABOUT_LOGGING.txt) — The three logging tiers & which to use.
* [DEVELOPMENT_NOTES.txt](DEVELOPMENT_NOTES.txt) — Why each subsystem is shaped the way it is. Engineering intent & the history behind it, by subsystem.

### 🌐 API, Meta, & Diagnostics
* [ABOUT_DOCUMENTATION.md](ABOUT_DOCUMENTATION.md) / [.txt](ABOUT_DOCUMENTATION.txt) — Handbook standards.
* [ABOUT_REST_API.md](ABOUT_REST_API.md) / [.txt](ABOUT_REST_API.txt) — Endpoint routing schemes.
* [CHANGELOG.txt](CHANGELOG.txt) — Version revision logs.
* [ERROR_DESCRIPTIONS.txt](ERROR_DESCRIPTIONS.txt) — Debugging status codes.

### 📋 Project Information
* [index.html](index.html) — Document root protection mechanism. Part of a valid installation (this is a working codebase after all).
* [INSTALLATION_INSTRUCTIONS.txt](INSTALLATION_INSTRUCTIONS.txt) — Server provisioning steps.
* [LICENSE](LICENSE) — GNU GPLv3.
* [README.md](README.md) — Main user guide (This document).
* [SUPPORTING_THIS_PROJECT.md](SUPPORTING_THIS_PROJECT.md) / [.txt](SUPPORTING_THIS_PROJECT.txt) — Contribution guidelines.
* [ICON_CREDITS.txt](ICON_CREDITS.txt) — Asset attribution registry.

---