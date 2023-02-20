# Learning with Texts

[![Latest Stable Version](https://poser.pugx.org/hugofara/lwt/v)](https://packagist.org/packages/hugofara/lwt)
[![License](https://poser.pugx.org/hugofara/lwt/license)](https://packagist.org/packages/hugofara/lwt)
[![PHP Version Require](https://poser.pugx.org/hugofara/lwt/require/php)](https://packagist.org/packages/hugofara/lwt)
![Composer Continuous Integration](https://github.com/hugofara/lwt/actions/workflows/php.yml/badge.svg)
[![Docker Image](https://github.com/HugoFara/lwt/actions/workflows/docker-image.yml/badge.svg)](https://github.com/HugoFara/lwt/actions/workflows/docker-image.yml)
[![Discord Server](https://badgen.net/discord/members/zAE8GXMKFa?icon=discord)](https://discord.gg/zAE8GXMKFa)

**Learning with Texts** (LWT) is a tool for language learning by reading. It is a self-hosted web application.

<div style="text-align: center;">
<img src="https://github.com/HugoFara/lwt/raw/master/img/lwt_icon_big.jpg" />
</div>

You feel that you won't learn much by translating dumb sentences or using grammar books? Learning With Texts offers you the possibility to learn by reading texts in your target language. Concept: when you don't know a word in a text, just click it. We show you the translation, and you will have regular tests to remember it. Ready to go?

**THIS IS A THIRD PARTY VERSION**. This version is not the
official one, and brings many improvements and new features.
It is quicker, has smaller database size,
and is open for contributions. The official version is on
[source forge](https://sourceforge.net/projects/learning-with-texts)

## Installation

As it is self-hosted, you will need a server, which can be your computer.

* Get the [latest GitHub release](https://github.com/HugoFara/lwt/releases).
  * You can also try to download the [latest stable version](https://github.com/HugoFara/lwt/archive/refs/heads/master.zip) if you want the cutting-edge updates (that may include some bugs).

### Linux

Start a shell in the downloaded folder an run: ``./INSTALL.sh``. You may need to run ``chmod +x ./INSTALL.sh`` first.

### Docker

Ready to go sample of  `docker-compose.yml` and `.env` are here [./docker-example/](./docker-example/)  
Detailed instructions: [./docker-example/README.md](./docker-example/README.md)

### Other Systems

* **Please see**: [docs/install.md](docs/install.md) for detailed instructions.
* Create ``connect.inc.php`` with an existing database user. Everything is explained at [docs/info.html](https://hugofara.github.io/lwt/docs/info.html#install).

And you are ready to go!

Please note that a composer repo is available, as well as an installation using Docker.

## Description

LWT is a language learning web application. To learn a language, you
need to practice, and we guide you in reading exercises.

First copy/paste any text you want to read. It can be raw text or an RSS feed.

![Adding French text](https://github.com/HugoFara/lwt/raw/master/img/05.jpg)

Then, we parse the text. Unknown words will be displayed with different colors,
just click them to see it in a dictionary.

![Learning French text](https://github.com/HugoFara/lwt/raw/master/img/06.jpg)

Read as much as you want!

To make sure you memorize new words, you can take review exercises.

![Reviewing French word](https://github.com/HugoFara/lwt/raw/master/img/07.jpg)

The difference with popular remembering software like
[Anki](https://apps.ankiweb.net/) is that we keep track of the
context to help you. By the way, we also ship
an Anki exporter.

## Features

* **Full list**: [docs/features.md](docs/features.md)

Features included from the official LWT software:

* Support for almost 40 languages.
* Text parsing for roman languages, right-to-left,
and East-Asian ideogram systems
* Translate words on-the-fly
* Add an audio track and read it online
* Practice words you don't remember
* Statistics to record your progress

### Features not in the official LWT

* **Full list**: [docs/newfeatures.md](docs/newfeatures.md)

Features that were added by the community:

* Support for mobile
* Automatically import texts from RSS feeds
* Support for different themes
* Display translations of terms with status in the reading frame
* Multiwords selection (click and hold on a word
→ move to another word → release mouse button)
* Bulk translate new words in the reading frame
* Text to speech
* Optional "ignore all" button in read texts
* Key bindings in the reading frame
* Selecting terms according to a text tag
* Two database backup modes (new or old structure)

### Improvements compared to the official LWT

* Database improvements (db size is much smaller now)
* Longer (>9) expressions can now be saved (up to 250 characters)
* Save text/audio position in the reading frame
* Google api (use 'ggl.php' instead of '*<http://translate.google.com>' for Google Translate)
* Improved Search/Query for Words/Texts
* Term import with more options (i.e.: combine translations, multiple tag import)
* Support for MeCab for Japanese word-by-word automatic translation.
* You can include video files from popular video platforms.
* Code documentation.
* Code is well organised, making debugging and contribution easier.

## Contribute

* **Complete explanation**: [docs/contribute.md](docs/contribute.md)

To contribute, you need to clone or fork this repository, and [Composer](https://getcomposer.org/download/).
The composer package is at [hugofara/lwt](https://packagist.org/packages/hugofara/lwt).

Run ``git clone https://github.com/HugoFara/lwt``

Next, got to the lwt folder and use ``composer install --dev``.

In short:

```bash
git clone https://github.com/HugoFara/lwt
cd lwt
composer install --dev
```

## Branches

* The stable branch is *master*. Last commit on this branch is
considered to be bug-free.
* The *dev* branch is for unstable versions.
* The *official* branch is for the official LWT Releases.
Any other branch if considered under development.

## Useful links

* General documentation at [docs/info.html](https://hugofara.github.io/lwt/docs/info.html).
* Please find more help at [docs/index.html](https://hugofara.github.io/lwt/docs/index.html).
* You can also contact the community using [GitHub](https://github.com/hugofara/lwt) or
[Discord](https://discord.gg/zAE8GXMKFa).

## Alternatives

* *See also*: [docs/links.md](docs/links.md)
LWT is written for web, but you have many choices if you want a standalone installation:
* [LingL](https://github.com/gustavklopp/LingL) is a wonderful alternative written in Python, by [gustavklopp](https://github.com/gustavklopp).
* [FLTR ◆ Foreign Language Text Reader](https://sourceforge.net/projects/foreign-language-text-reader/), a Java clone, by the same
author as LWT ([lang-learn-guy](https://sourceforge.net/u/lang-learn-guy/profile/))

## Unlicense

Under unlicense, view [UNLICENSE.md](UNLICENSE.md), please look at [http://unlicense.org/].

**Let's learn new languages!**
