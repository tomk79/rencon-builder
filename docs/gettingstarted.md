# 基本的なビルドの手順

## composer.json

```json
{
    "require": {
        "tomk79/filesystem": "*"
    },
    "autoload": {
        "psr-4": {
            "app01\\": [
                "src"
            ]
        },
        "files": [
            "php/sample.php"
        ]
    }
}
```

## rencon-builder.json

```json
{
    "name": "Application Sample",
    "app_id": "app01",
    "version": "1.0.0-alpha.1",
    "dist": "dist/app01.php",
    "resources": "resources/",
    "middleware": [
        "app01\\middleware\\sample::middleware"
    ],
    "route": {
        "": {
            "title": "Home",
            "page": "app/pages/index.php"
        },
        "dynamic.{routeParam1?}.route": {
            "title": "Dinamic route",
            "page": "app01\\dinamicRoute::start"
        },
        "test": {
            "title": "Test",
            "page": "app01\\test::start"
        },
        "test.post": {
            "title": "Post Test",
            "allow_methods": "post",
            "page": "app01\\test::post"
        }
    },
    "theme": "app/theme/theme.php",
    "config_template": "app/config.php"
}
```

## ビルドコマンド

```bash
php rencon-builder.php
```
