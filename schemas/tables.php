<?php
/**
 * Created by PhpStorm.
 * User: parsingphase
 * Date: 11/09/14
 * Time: 09:52
 */

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

$tables = [];

/**
 * CREATE TABLE `users` (
 * `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
 * `email` VARCHAR(100) NOT NULL DEFAULT '',
 * `password` VARCHAR(255) DEFAULT NULL,
 * `salt` VARCHAR(255) NOT NULL DEFAULT '',
 * `roles` VARCHAR(255) NOT NULL DEFAULT '',
 * `name` VARCHAR(100) NOT NULL DEFAULT '',
 * `time_created` INT NOT NULL DEFAULT 0,
 * PRIMARY KEY (`id`),
 * UNIQUE KEY `unique_email` (`email`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *
 *
 * CREATE TABLE `user_custom_fields` (
 * user_id INT(11) UNSIGNED NOT NULL,
 * attribute VARCHAR(50) NOT NULL DEFAULT '',
 * value VARCHAR(255) DEFAULT NULL,
 * PRIMARY KEY (user_id, attribute)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

 */

$users = new Table('users');
$users->addColumn('id', Type::INTEGER, ['unsigned' => true, 'autoincrement' => true]);
$users->setPrimaryKey(['id']);
$users->addColumn('email', Type::STRING)->setLength(100)->setNotnull(true); // login key
$users->addUniqueIndex(['email']);
$users->addColumn('password', Type::STRING)->setLength(255)->setNotnull(true);
$users->addColumn('salt', Type::STRING)->setLength(255)->setNotnull(true);
$users->addColumn('roles', Type::STRING)->setLength(255)->setNotnull(true);
$users->addColumn('name', Type::STRING)->setLength(100)->setNotnull(true); // display name
$users->addColumn('time_created', Type::INTEGER, ['unsigned' => true]); // epoch time?

$tables['users'] = $users;

$userCustomFields = new Table('user_custom_fields');
$userCustomFields->addColumn('user_id', Type::INTEGER, ['unsigned' => true]);
$userCustomFields->addColumn('attribute', Type::STRING)->setLength(50)->setNotnull(true)->setDefault('');
$userCustomFields->setPrimaryKey(['user_id', 'attribute']);
$userCustomFields->addColumn('value', Type::STRING)->setLength(255);

$tables['user_custom_fields'] = $userCustomFields;
/*
 * CREATE TABLE `blog_post` (
  `id` integer PRIMARY KEY AUTOINCREMENT,
  `time` text NOT NULL,
  `subject` text NOT NULL,
  `body` text NOT NULL,
  `security` text NOT NULL
  );

 */

$blogPost = new Table('blog_post');
$blogPost->addColumn('id', Type::INTEGER, ['unsigned' => true, 'autoincrement' => true]);
$blogPost->setPrimaryKey(['id']);
$blogPost->addColumn('time', Type::DATETIME)->setNotnull(true); // see if sqlite handles this?
$blogPost->addColumn('subject', Type::STRING)->setLength(255)->setNotnull(true);
$blogPost->addColumn('body', Type::TEXT)->setNotnull(true);
$blogPost->addColumn('security', Type::STRING)->setNotnull(true); //TODO Enums not supported, use ints / class consts?

$tables['blog_post'] = $blogPost;


return $tables;
