-- phpMyAdmin SQL Dump
-- version 2.6.2-pl1
-- http://www.phpmyadmin.net
-- 
-- Хост: localhost
-- Время создания: Апр 12 2017 г., 13:07
-- Версия сервера: 5.0.27
-- Версия PHP: 5.3.6
-- 
-- БД: `xo`
-- 

-- --------------------------------------------------------

-- 
-- Структура таблицы `tables`
-- 

CREATE TABLE `tables` (
  `id` int(11) NOT NULL auto_increment,
  `state` varchar(255) NOT NULL,
  `symbol` enum('X','O') NOT NULL,
  `anti_symbol` enum('X','O') NOT NULL,
  `started` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `status` int(11) NOT NULL default '1',
  `winner` enum('X','O') default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=11 ;
