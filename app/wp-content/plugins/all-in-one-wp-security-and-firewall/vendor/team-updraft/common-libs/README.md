# Common Libraries

This project contains many useful libraries that are currently used and can be reused across our projects. They are kept here for easy maintenance and also so that consumers get a uniform interface and things dont break across versions or on updates.

## How to publish a new release?

- Once the pipeline passes, locate the `create_new_release` job in the `publish` stage.
- Run the job with the variable `TAG` set to the desired version, for example: `0.0.1`.
- The `TAG` must follow semantic versioning.
- This will publish the tag to the Composer registry.

## Using the package

This `common-libs` is published as a composer package, which can be installed in any of the projects which needs any of its features.
To install the package, simply run - `composer require team-updraft/common-libs`.

And then you can use any of the libraries present here.

**Note**: Please note that this is a private package and needs authentication. You can either authenticate through Personal Access Token or deploy token -

Personal access token -

```JSON
    "config": {
        "gitlab-token": {
            "source.updraftplus.com": "<pat>"
        },
        "platform-check": false
    },
```

Deploy token -

```JSON
 "config": {
        "http-basic": {
            "source.updraftplus.com": {
              "username": "",
              "password": ""
            }
        },
        "platform-check": false
 },
```

## Using the package from a particular git branch

In certain situations you might want to use the latest code from a git branch instead of using the published package (`main` branch).
In that case you can tweak your `composer.json` to install from a branch -

**Note** - Please note that this is just for testing. The branch code might be in development, the branch might be deleted later, therefore just use this only if you want to test something which is not yet merged, but you have to test it.

```JSON
    "repositories": [
        {
            "type": "package",
            "package": {
                "name": "team-updraft/common-libs",
                "version": "dev-<branch_name>",
                "dist": {
                    "url": "https://source.updraftplus.com/api/v4/projects/28/jobs/artifacts/<branch_name>/download?job=common-libs",
                    "type": "zip"
                }
            }
        }
    ],
    "require": {
        "team-updraft/common-libs": "dev-<branch_name>"
    }
```

## Libraries included in the package -

Here are all the libraries included in this package -

### TeamUpdraft theme

The compiled theme is present in the `updraft-theme` folder.

In the project you will be using the library like this -

- Just include the main entry point PHP file -

  ```php
    if (!class_exists('TU_Theme')) require_once(UD_CENTRAL_DIR.'/vendor/team-updraft/common-libs/updraft-theme/theme.php');
  ```

- Then initialize the library by calling -

  ```php
    $tu_theme = TU_Theme::instance();
  ```

- Then load the components either on front-end or backend or both -

  ```php
    $tu_theme->load_assets_on_frontend();
    $tu_theme->load_assets_on_backend();
  ```

- There's a filter available filter to restrict adding the `updraft-theme` colors. As soon as you disable that, all the components will be discolored. In your project you have to add a CSS to add all those variables with the same name with the colors of your project you want.

  ```php
    // Disable loading theme colors.
    add_filter('tu_theme_load_colors', '__return_false');
  ```

  By default it is true and theme colors will be loaded.

- Components: Each component has a documentation in its own folder, on how to use it.

CHANGELOG

- TWEAK: Port from previous semaphore classes to Updraft_Semaphore_3_0 in updraft-tasks
- FIX: Wrong query value in `delete_task_meta` method
- TWEAK: Make the logging format uniform
- FIX: Wrong DB Schema reference
- TWEAK: Logging on the semaphore
