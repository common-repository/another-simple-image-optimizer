# Another simple image optimizer

WordPress plugin to automatically optimize uploaded images (jpg, png, gif, webp, svg, avif) using the [Spatie image-optimizer library][1] and binary files on your host system.

New uploaded images with all image sizes (thumbnails) will be optimized automatically.

Previously uploaded images can be updated in two ways:

1. Use the command line interface (cli) and run `wp media regenerate`, which also triggers the optimization chain. If you don't have cli access, some other plugin, that regenerates existing thumbnails should work, too (not tested, yet).
2. The media library has a new column "File size" in the list view, which displays the new and the old file size. If an image is not optimized, there is a link named "Optimize" to run the optimizer directly.

__Warning:__ Images are replaced with the optimized ones. Create a backup before testing this plugin. If this is a deal-breaker for you, don't use it.

__Important:__ If the needed binary files aren't installed, this plugin won't optimize anything. Don't use it, if you don't know, how to install them or if your web hoster doesn't provide them.

__Notice:__ WordPress has no support for SVG and AVIF files. Technically this plugin can optimize them, but I didn't run any tests with plugins, that add SVG/AVIF support to WordPress. I was able to optimize svg and avif files automatically in a local test setup (see [`prepare-and-run-tests.sh` in the tests folder](https://codeberg.org/raffaelj/wordpress-another-simple-image-optimizer/src/branch/main/tests/prepare-and-run-tests.sh) and search for `enable_svg_avif_upload`).

## Optimization tools

Spatie image-optimizer will use these optimizers if they are present on your system:

* [JpegOptim][7]
* [Optipng][9]
* [Pngquant 2][10]
* [SVGO 1][11]
* [Gifsicle][12]
* [cwebp][14]
* [avifenc][16]

You can find information about how to install the binaries in the [spatie image-optimizer README][2].

## Why another image optimization plugin?

After some research the first search results pages were full of SEO spam. I don't need a CDN. I don't want to send uploaded images via limited API access to a third party provider (who uses the same binaries anyway). I just want to upload huge jpg files and let some magic in the background remove all meta data and drastically shrink that file. Also I don't like ads and bloatware.

So to avoid SEO spam I began to search for "wordpress spatie", "wordpress jpegoptim" and "wordpress optipng" to find good candidates. I found some, but they didn't match my expectations (e. g. no gif support, outdated, only cli...). So I wrote my own implementation with the goal to keep it simple.

## Inspiration and similar projects

While searching existing plugins, that aren't bloated, I found the following interesting projects. If you don't like my plugin, maybe some of these fit your needs:

* https://github.com/TypistTech/image-optimize-command
  * only cli, needs wp-cli to be installed via composer
* https://github.com/ainsleyclark/squidge
  * looks really good, converts jpg and png to webp and avif formats
  * no pngquant support
  * no gif or svg support
* https://github.com/gsarig/slimage
  * only jpegoptim and optipng
* https://github.com/lstellway/wp-optimize-images
  * implemented psliwa/image-optimizer
  * installation only via composer as mu-plugin
* https://github.com/globalis-ms/wp-cubi-imagemin
  * also implemented psliwa/image-optimizer
  * installation only via composer

## Installation

Copy this repository into `wp-content/plugins` and name it `another-simple-image-optimizer` or install it from the official [plugin repository][17].

## Notes

<del>I use `symfony/process` v6.0.x for PHP 8.0 compatibility. Since v6.1 it requires PHP 8.1.</del>

PHP 8.0 reaches EOL at Nov 2023 and `symfony/process` 6.0 is very outdated (from Feb 2022). So it felt wrong to stay backwards compatible anymore.

I decided against the [`psliwa/image-optimizer`][3] library, because it doesn't support webp. Also `spatie/image-optimizer` is easier to configure and I was already familiar with it, because I used that in a different project.

The plugin name/slug `simple-image-optimizer` was taken, so I chose `another-simple-image-optimizer`.

Choosing such a long slug was a bad idea. It is used as text domain for i18n strings. Template code became much more unreadable.

I used static methods, because I couldn't find a way to register classes/methods in hooks without loading them beforehand. So I cant't use `$this` or `__construct()`, but classes are only loaded, when needed to improve performance.

## Development

### Inspiration, links

* How to publish plugin
  * https://wordpress.org/plugins/developers/add/
  * https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
  * https://developer.wordpress.org/plugins/wordpress-org/
  * https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/
  * https://developer.wordpress.org/plugins/plugin-basics/header-requirements/
  * https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/
* Sync git repo with wp svn
  * https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/
  * https://github.com/10up/action-wordpress-plugin-deploy
  * https://nabeel.blog/2016/10/24/wordpress-plugin-git-svn/
  * https://github.com/rayrutjes/wp-plugin-git-svn-sync
  * https://github.com/kasparsd/wp-deploy
* How to find unique plugin slug
  * https://plugins.svn.wordpress.org/
  * https://wordpress.stackexchange.com/a/290402

### Update dependencies

```bash
composer update --no-dev -o
# composer update --no-dev --ignore-platform-reqs -o
```

### Publish new version

* Update version number in `plugin.php` and in `readme.txt`
* `git tag` like normal
* run `deploy.sh` to publish latest git tag to WordPress Plugin Directory using SVN:

```bash
# chmod +x ./deploy.sh
./deploy.sh

# type y, Enter
# No ssh keys for WordPress/SVN :-(
# Use OS keyring or KeePassXC auto type with wordpress.org web login to enter password
```

### Update trunk

```bash
# git checkout main
rm -r wp-svn/trunk && mkdir wp-svn/trunk
git archive HEAD | tar -x -C wp-svn/trunk
cd wp-svn
svn add trunk/* --force
svn commit -m "synced main branch to wp/svn trunk" --username raffaelj
```

### Update only `readme.txt`

```bash
# git checkout main
cp readme.txt wp-svn/trunk/
cp readme.txt wp-svn/tags/0.1.3/
cd wp-svn
svn commit -m "Tested up to 6.3"
```

### Submit initial version

```bash
git archive -o ../another-simple-image-optimizer.zip 0.1.1
```

## License

Code: MIT, see [LICENSE][6] for more information.

Images (in `tests/images`): [CC-BY](https://creativecommons.org/licenses/by/4.0/) Raffael Jesche.

## Credits and third party resources

The following libraries are used in this project (Thanks!):

* [spatie/image-optimizer][1] by Spatie, MIT license
* [symfony/process][5] by Fabien Potencier, MIT license
* [psr/log][4] by PHP Framework Interoperability Group, MIT license

These optimizers are used under the hood:

* [JpegOptim][7] by [Timo Kokkonen][8], GPL-3.0 license
* [Optipng][9] by Cosmin Truta, zlib license
* [Pngquant 2][10] by Kornel Lesiński, GPL-3.0 license
* [SVGO 1][11] by Kir Belevich, MIT license
* [Gifsicle][12] by [Eddie Kohler][13], GPL-2.0 license
* [cwebp][14] by Google Inc, [some kind of permissive license][15]
* [avifenc][16] by Joe Drago, BSD License

[1]: https://github.com/spatie/image-optimizer
[2]: https://github.com/spatie/image-optimizer#optimization-tools
[3]: https://github.com/psliwa/image-optimizer
[4]: https://github.com/php-fig/log
[5]: https://github.com/symfony/process
[6]: https://codeberg.org/raffaelj/wordpress-another-simple-image-optimizer/src/branch/main/LICENSE
[7]: https://github.com/tjko/jpegoptim
[8]: https://github.com/tjko
[9]: http://optipng.sourceforge.net/
[10]: https://pngquant.org/
[11]: https://github.com/svg/svgo
[12]: http://www.lcdf.org/gifsicle/
[13]: https://www.read.seas.harvard.edu/~kohler/
[14]: https://developers.google.com/speed/webp/docs/precompiled
[15]: https://chromium.googlesource.com/webm/libwebp/+/refs/heads/main/COPYING
[16]: https://github.com/AOMediaCodec/libavif/blob/main/doc/avifenc.1.md
[17]: https://de.wordpress.org/plugins/another-simple-image-optimizer/
