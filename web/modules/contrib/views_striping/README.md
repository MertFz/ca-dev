# Views Striping

The Views Striping module adds CSS classes to a view's rows to create stripes
of alternating colours.

You may need to define CSS stylings for the 'odd' and 'even' classes in your
theme. (See https://www.drupal.org/project/drupal/issues/3332049.)

The following striping types are provided:

- alternating: stripes that switch each row
- field value: stripes that switch each time the value of a particular field
  changes

This works for the following view styles:

- table
- aggregated table from https://www.drupal.org/project/views_aggregator

## Requirements

This module requires no modules outside of Drupal core.

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

## Configuration

1. Visit the Views advanced settings at /admin/structure/views/settings/advanced
2. In the 'Display Extenders' section, enable the 'Row striping' display
   extender.
3. Edit your view.
4. In the Table settings, select one of the striping types.

## Uninstallation

Before uninstalling the module you have to disable row striping in Views.
This is due to a core issue reported here:
https://www.drupal.org/project/drupal/issues/2635728

1. Go to /admin/structure/views/settings/advanced
2. Uncheck the box 'Row striping'
3. Proceed to /admin/modules/uninstall to uninstall the module.
