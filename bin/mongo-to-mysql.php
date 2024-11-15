<?php

require_once __DIR__ . '/../vendor/autoload.php';

$mysql = new PDO('mysql:host=localhost;dbname=f43me', 'root', 'root');
$mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$mongo = new MongoDB\Driver\Manager('mongodb://localhost:27017');

$feedIds = [];
$rows = $mongo->executeQuery('f43me.Feed', new MongoDB\Driver\Query([]));

foreach ($rows as $i => $row) {
    if ($i > 0 && 0 === $i % 50) {
        echo ' (' . $i . ')' . "\n";
    }

    $row = (array) $row;
    $cached = $row['last_item_cached_at']->toDateTime()->format('Y-m-d h:m:s');
    $created = $row['created_at']->toDateTime()->format('Y-m-d h:m:s');
    $updated = (new DateTime())->format('Y-m-d h:m:s');
    $sql = 'INSERT INTO feed SET name=:name, description=:description, link=:link, host=:host, logo=:logo, color=:color, parser=:parser, formatter=:formatter, nb_items=:nb_items, slug=:slug, is_private=:is_private, sort_by=:sort_by, last_item_cached_at=:last_item_cached_at, created_at=:created_at, updated_at=:updated_at';

    $sth = $mysql->prepare($sql);
    $sth->bindParam('name', $row['name'], PDO::PARAM_STR);
    $sth->bindParam('description', $row['description'], PDO::PARAM_STR);
    $sth->bindParam('link', $row['link'], PDO::PARAM_STR);
    $sth->bindParam('host', $row['host'], PDO::PARAM_STR);
    $sth->bindParam('logo', $row['logo'], PDO::PARAM_STR);
    $sth->bindParam('color', $row['color'], PDO::PARAM_STR);
    $sth->bindParam('parser', $row['parser'], PDO::PARAM_STR);
    $sth->bindParam('formatter', $row['formatter'], PDO::PARAM_STR);
    $sth->bindParam('nb_items', $row['nb_items'], PDO::PARAM_INT);
    $sth->bindParam('slug', $row['slug'], PDO::PARAM_STR);
    $sth->bindParam('is_private', $row['is_private'], PDO::PARAM_BOOL);
    $sth->bindParam('sort_by', $row['sort_by'], PDO::PARAM_STR);
    $sth->bindParam('last_item_cached_at', $cached, PDO::PARAM_STR);
    $sth->bindParam('created_at', $created, PDO::PARAM_STR);
    $sth->bindParam('updated_at', $updated, PDO::PARAM_STR);

    $sth->execute();

    $feedIds[(string) $row['_id']] = $mysql->lastInsertId();

    echo '.';
}

echo "\n";

$rows = $mongo->executeQuery('f43me.FeedItem', new MongoDB\Driver\Query([]));

foreach ($rows as $i => $row) {
    if ($i > 0 && 0 === $i % 50) {
        echo ' (' . $i . ')' . "\n";
    }

    $row = (array) $row;
    $published = $row['published_at']->toDateTime()->format('Y-m-d h:m:s');
    $created = $row['created_at']->toDateTime()->format('Y-m-d h:m:s');
    $updated = $row['updated_at']->toDateTime()->format('Y-m-d h:m:s');
    $sql = 'INSERT INTO item SET feed_id=:feed_id, title=:title, link=:link, permalink=:permalink, content=:content, published_at=:published_at, created_at=:created_at, updated_at=:updated_at';

    $feed = (array) $row['feed'];

    // in case the item is associated to a deleted item
    if (!isset($feedIds[(string) $feed['$id']])) {
        echo 'S';
        continue;
    }

    $feedId = $feedIds[(string) $feed['$id']];

    $sth = $mysql->prepare($sql);
    $sth->bindParam('feed_id', $feedId, PDO::PARAM_INT);
    $sth->bindParam('title', $row['title'], PDO::PARAM_STR);
    $sth->bindParam('link', $row['link'], PDO::PARAM_STR);
    $sth->bindParam('permalink', $row['permalink'], PDO::PARAM_STR);
    $sth->bindParam('content', $row['content'], PDO::PARAM_STR);
    $sth->bindParam('published_at', $published, PDO::PARAM_STR);
    $sth->bindParam('created_at', $created, PDO::PARAM_STR);
    $sth->bindParam('updated_at', $updated, PDO::PARAM_STR);

    $sth->execute();

    echo '.';
}

echo "\n";
