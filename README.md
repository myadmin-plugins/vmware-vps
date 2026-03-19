# MyAdmin VMware VPS Plugin

[![Tests](https://github.com/detain/myadmin-vmware-vps/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-vmware-vps/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-vmware-vps/version)](https://packagist.org/packages/detain/myadmin-vmware-vps)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-vmware-vps/downloads)](https://packagist.org/packages/detain/myadmin-vmware-vps)
[![License](https://poser.pugx.org/detain/myadmin-vmware-vps/license)](https://packagist.org/packages/detain/myadmin-vmware-vps)

A MyAdmin plugin that provides VMware VPS provisioning, lifecycle management, and billing integration. It registers event hooks for service activation, deactivation, IP changes, and administrative settings within the MyAdmin hosting platform.

## Requirements

- PHP 8.2 or later
- ext-soap
- Symfony EventDispatcher 5.x, 6.x, or 7.x

## Installation

```sh
composer require detain/myadmin-vmware-vps
```

## Features

- Service activation and deactivation hooks for VMware VPS instances
- IP address change management through the VMware API
- Administrative menu integration for license management
- Configurable per-slice pricing and out-of-stock controls
- Event-driven architecture via Symfony EventDispatcher

## Testing

```sh
composer install
vendor/bin/phpunit
```

## License

Licensed under the LGPL-2.1. See [LICENSE](https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html) for details.
