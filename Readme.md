# Z Gatekeep Wp

A lightweight, professional WordPress plugin for role-based login redirection and URL access management.

## Description

RoleFlow provides a centralized interface to control the user journey after authentication. It allows administrators to define specific landing pages for different user roles and protect sensitive site areas using an integrated Access Guard system. 

The plugin is designed to be compatible with major membership and e-commerce platforms, ensuring a seamless experience regardless of the login method used.

## Key Features

### Login Redirection
* Define custom destination URLs for any WordPress user role.
* Set a global fallback URL for users without specific assignments.
* High-priority execution (priority 9999) to ensure compatibility with other plugins.
* Full support for WooCommerce, BuddyPress, BuddyBoss, and Ultimate Member.

### Access Guard
* Restrict access to specific URLs or paths based on user roles.
* Support for "Logged-Out" visitor rules to protect public-facing pages.
* Multiple matching modes: Exact URL, Path match, and Wildcards (e.g., /members/*).
* Drag-and-drop rule prioritization to manage overlapping access logic.

### Technical Overview
* Clean, tabbed administrative interface.
* Optimized for performance with early-exit logic and minimal database footprint.
* Secure data handling using WordPress nonces and standard sanitization protocols.

## Installation

1. Upload the `roleflow` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Settings > Login Redirect** to configure your rules.

## Usage

### Configuring Redirects
Under the **Login Redirect** tab, select a user role and enter the destination URL. You can use relative paths (e.g., `/dashboard`) or absolute URLs (e.g., `https://example.com/home`).

### Setting Access Rules
Under the **Access Guard** tab, define a source path you wish to protect. Select the role that should be restricted and the destination where they should be sent if they attempt to access the protected path.

## Requirements
* WordPress 5.8 or higher
* PHP 7.4 or higher

## License
This project is licensed under the GPL-2.0+ License.
