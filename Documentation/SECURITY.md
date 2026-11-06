# 🔐 HRConvert2 Security Policy

HRConvert2 is built for public-facing deployment, which means it is built to be attacked. If you have found a way in, we want to hear about it.

> [!IMPORTANT]
> **Never report a vulnerability in a public issue, pull request or discussion.** A public report puts every installation at risk before a fix exists. Use the private channel below.

---

## ✅ Supported Versions

Security fixes are made for these three, and nothing else.

| Version | What it is | Supported |
| :--- | :--- | :---: |
| **The latest tagged release** | The newest `vX.Y.Z` tag. `php convertCore.php -u` installs it when `--Automatic Update Target Version--` is `'latest'`. | ✅ |
| **The latest Docker image** | `zelon88/hrconvert2:latest`, published from the latest tagged release. | ✅ |
| **Bleeding Edge** | The `master` branch. An update installs it when `--Automatic Update Target Version--` is `'edge'`. | ✅ |
| **Any older tagged release** | Fixes are never backported. Update to the latest tagged release to receive them. | ❌ |

---

## 📬 Reporting A Vulnerability

1. Open the **Security** tab of [zelon88/HRConvert2](https://github.com/zelon88/HRConvert2).
2. Choose **Report a vulnerability**.
3. Describe it in the private advisory that opens. Only the maintainer and you can read it.

**Every report is accepted and read**, whatever its severity, and whether or not it carries a proof of concept.

Every part of this repository is covered: the core, the Engine, the pipelines, the bundled ScanCore, the GUIs, the language packs, the Docker image, the build scripts and the documentation. A vulnerability in a third party dependency is covered too when HRConvert2 is how it is reached. Please report it upstream as well.

### 📝 A useful report says

| | |
| :--- | :--- |
| **Version** | A tag, the latest Docker image, or a commit on `master`. |
| **Installation** | The output of `php convertCore.php -v`, where there is one. |
| **Configuration** | The settings involved, by their labels in `config.php`. |
| **Reproduction** | The steps, and a proof of concept if you have one. |
| **Impact** | What an attacker gains. |

---

## 🔄 How A Report Is Handled

1. The report is acknowledged in its private advisory.
2. It is reproduced against every supported version.
3. A fix is made on Bleeding Edge, and ships in the next tagged release and the latest Docker image.
4. The advisory is published once the fix has shipped. `CHANGELOG.txt` records the fix with the advisory's GHSA number.

Nothing about a vulnerability is made public before it is patched.

---

## 🏆 Credit

**Every reporter is credited publicly once the vulnerability is patched**, in the published advisory and in `CHANGELOG.txt`, under the name or handle you choose. If you would rather not be named, say so and you will not be.

---

## 🧱 The Security Model

> [!NOTE]
> **The sandbox is the security boundary.** Every dependency that parses an uploaded file is assumed to be exploitable, so it runs inside bubblewrap with no network. Sanitizers, filters and lint checks are convenience layers and are never relied on as a boundary.

A way past a convenience layer is still a vulnerability, and is still credited, even when the sandbox contains it.

| Read more | About |
| :--- | :--- |
| [ABOUT_NETWORK_ISOLATION.txt](ABOUT_NETWORK_ISOLATION.txt) | How a conversion is kept off the network. |
| [ABOUT_SCAD_SANITIZATION.txt](ABOUT_SCAD_SANITIZATION.txt) | How an uploaded OpenSCAD source is rewritten before it renders. |
| [ABOUT_DATA_DIRECTORY_PROTECTION.txt](ABOUT_DATA_DIRECTORY_PROTECTION.txt) | How converted files are kept from being browsed. |
| [ABOUT_DEPENDENCIES.txt](ABOUT_DEPENDENCIES.txt) | How every dependency is audited before it is adopted. |

The plain text source of truth for this policy is [SECURITY.txt](SECURITY.txt).
