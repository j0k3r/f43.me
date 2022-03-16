# f43.me

![CI](https://github.com/j0k3r/f43.me/workflows/CI/badge.svg)
[![Coverage Status](https://coveralls.io/repos/j0k3r/f43.me/badge.svg?branch=master&service=github)](https://coveralls.io/github/j0k3r/f43.me?branch=master)

## What's that?

I'm reading a lot of feeds in the subway. Mostly when I go to work and when I come back home. We are lucky in Paris because we have data network in the subway, but sometimes, network is saturated and you can't load the webpage of an item of your feed. You're stuck with only 3 lines from the feed...

That's why I've built a kind of proxy for RSS feeds that I read the most, called [**f43.me**](http://f43.me/).

> It's kind of a shortcut for "Feed For Free" (Feed = f, For = 4, Free = 3). Tada

Anyway, it's simple:

 * fetch items from a feed
 * grab the content
 * make it readable
 * store it
 * create a new feed with readable items

![f43.me screenshot](https://user-images.githubusercontent.com/62333/158586771-2137ea59-e882-4f89-9dd4-5eb657b843c5.png)

## Contents

<!-- MarkdownTOC autolink="true" -->

- [Workflow](#workflow)
    - [Improvers](#improvers)
    - [Extractors](#extractors)
    - [Parsers](#parsers)
    - [Converters](#converters)
- [How to use it](#how-to-use-it)
    - [Requirements](#requirements)
    - [Install](#install)
        - [Without RabbitMQ](#without-rabbitmq)
        - [With RabbitMQ](#with-rabbitmq)
    - [Try it](#try-it)
- [License](#license)

<!-- /MarkdownTOC -->

## Workflow

When it grabs a new item, there are several steps before we can say the item *is* readable. Let me introduce **improvers**, **extractors**, **converters** and **parsers**.

All of them works in a chain, we'll go thru all of them until we find one that match.

> For curious people, this workflow happen in the [`Extractor->parseContent`](https://github.com/j0k3r/f43.me/blob/000dd43db9ab4429344918a2263bee3bf8aace24/src/AppBundle/Content/Extractor.php#L68) method.

### Improvers

Most of social aggregator add great value to an url. For example, Reddit and HackerNews add a comments link and Reddit also provide a category, a little preview for video or image, etc...

These things are important to keep while it's still necessary to fetch the content of the target url.

This is where improver helps.

An improver use 3 methods:

 * `match`: tells if this improver will work on the given host (host that came for the main feed url)
 * `updateUrl`: can do whatever it wants to update the url of an item (for Reddit, we extract the url from the `[link]`)
 * `updateContent`: add interesting information previous (or after) the readable content (for  Reddit we just put the readable content **after** the item content. *This method will be called AFTER the parser described below.*

You can find some examples in the [improver folder](https://github.com/j0k3r/f43.me/tree/master/src/AppBundle/Improver) (atm Reddit & HackerNews).

### Extractors

Parser that gets html content from an url and find what can be the most interesting part for the user is important. But, most of the time they fail when it comes to images (like from Imgur, Flickr) or from social network (like Tumblr, Twitter or Facebook).

These online service provides API to retrieve content from the their platform. Extractors will use them to grab the *real* content.

An extractor uses 2 methods:

 * `match`: tells if this extractor needs to work on that item (usually a bunch of regex & host matching)
 * `getContent`: it will call the related API or url to fetch the content from the match parameters found in the `match` method (like Twitter ID, Flickr ID, etc...) and return a clean html

You can find some of them in the [extractor folder](https://github.com/j0k3r/f43.me/tree/master/src/AppBundle/Extractor) (Flickr, Twitter, Github, etc...)

### Parsers

When we have the (most of the time, little) content from the feed, we use parsers to grab the html from the url and make it readable.

This involve 2 kind of parser:

 * the **Internal**, which uses a local PHP libray, called [graby](https://github.com/j0k3r/graby).
 * the **External**, which uses the excellent [Mercury Parser API](https://github.com/postlight/mercury-parser) from Postlight Labs.

### Converters

And finally, we can use some converters to transform HTML code to something different.

For example, Instagram embed code doesn't include the image itself (this part is usually done in javascript). The Instagram converter use the Instagram extractor to retrieve the image of an embed code and put it back in the feed item content.

You can find some of them in the [converter folder](https://github.com/j0k3r/f43.me/tree/master/src/AppBundle/Converter) (only Instagram for the moment)

## How to use it

### Requirements

 - PHP >= 7.4 (with `pdo_mysql`)
 - Nodejs 14 (for assets)
 - MySQL >= 5.7
 - [RabbitMQ](https://www.rabbitmq.com/), which is optional (see below)
 - [Supervisor](http://supervisord.org/) (only if you use RabbitMQ)

For each external API that improvers / extractors / parsers use, you will need an api key:

 * Tumblr: https://www.tumblr.com/oauth/apps
 * Imgur: https://api.imgur.com/oauth2/addclient
 * Mercury: https://mercury.postlight.com/web-parser/
 * Twitch: https://www.twitch.tv/kraken/oauth2/clients/new
 * GitHub: https://github.com/settings/applications/new

### Install

You should generate a password using `php bin/console security:hash-password --empty-salt` and then create a `.env.local` with your hashed password:

```
ADMINPASS="MY_HASHED_PASSWORD"
```

> ⚠️ Don't forget to escape _understable_ variable, ie: all `$` following by a letter will be interpreted as a variable in PHP. If your hashed password is `$2y$13$BvprBNLfp6eKHtqLyN1.w.z214Q5LMEvF9LKJTn44hrMIBt3pzwNW`, the `$BvprBNLfp6eKHtqLyN1` part will be interpreted as a variable by PHP. You must escape it in your `.env.local`:
>
> ```
> ADMINPASS="$2y$13\$BvprBNLfp6eKHtqLyN1.w.z214Q5LMEvF9LKJTn44hrMIBt3pzwNW"
> ```

Follow these steps:

```bash
git clone git@github.com:j0k3r/f43.me.git
cd f43.me
SYMFONY_ENV=prod composer install -o --no-dev
yarn install
php bin/console doctrine:schema:create --env=prod
yarn build
```

#### Without RabbitMQ

You just need to define these 3 cronjobs (replace all `/path/to/f43.me` with real value):

```bash
# fetch content for existing feed
*/2 * * * * php /path/to/f43.me/bin/console feed:fetch-items --env=prod old

# fetch content for fresh created feed
*/5 * * * * php /path/to/f43.me/bin/console feed:fetch-items --env=prod new

# cleanup old item. You can remove this one if you want to keep ALL items
0   3 * * * php /path/to/f43.me/bin/console feed:remove-items --env=prod
```

You can also run a command to fetch all new items from a given feed, using its slug:

```bash
php /path/to/f43.me/bin/console feed:fetch-items --env=prod --slug=reddit -t
```

#### With RabbitMQ

1. You'll need to declare exchanges and queues. Replace `guest` by the user of your RabbitMQ instance (`guest` is the default one):

   ```bash
   php bin/rabbit vhost:mapping:create -p guest app/config/rabbit_vhost.yml
   ```

2. You now have one queue and one exchange defined `f43.fetch_items` which will receive messages to fetch new items

3. Enable these 2 cronjobs which will periodically push messages in queues (replace all `/path/to/banditore` with real value):

   ```bash
   # fetch content for existing feed
   */2 * * * * php /path/to/f43.me/bin/console feed:fetch-items --env=prod old --use_queue

   # cleanup old item. You can remove this one if you want to keep ALL items
   0   3 * * * php /path/to/f43.me/bin/console feed:remove-items --env=prod
   ```

4. Setup Supervisor using the [sample file](data/supervisor.conf) from the repo. You can copy/paste it into `/etc/supervisor/conf.d/` and adjust path. The default file will launch 3 workers for fetching items.

   Once you've put the file in the supervisor conf repo, run `supervisorctl update && supervisorctl start all` (`update` will read your conf, `start all` will start all workers)


### Try it

You can use the built-in Docker image using `docker-compose`:

```bash
docker-compose up --build
```

You should be able to access the interface using `http://localhost:8100/app_dev.php`

## License

f43.me is released under the MIT License. See the bundled [LICENSE file](LICENSE) for details.
