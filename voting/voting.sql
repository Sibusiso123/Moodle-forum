-- phpMyAdmin SQL Dump
-- version 4.0.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 06, 2017 at 09:54 AM
-- Server version: 5.6.12-log
-- PHP Version: 5.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";



CREATE TABLE IF NOT EXISTS `messages` (
  `mes_id` int(11) NOT NULL AUTO_INCREMENT,
  `msg` text NOT NULL,
  `up` int(11) NOT NULL DEFAULT '0',
  `down` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`mes_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

INSERT INTO `messages` (`mes_id`, `msg`, `up`, `down`) VALUES
(1, 'We will conquer', 0, 0),
(2, 'God willing', 0, 0);


CREATE TABLE IF NOT EXISTS `voting_ip` (
  `ip_id` int(11) NOT NULL,
  `mes_id_fk` int(11) NOT NULL,
  `ip_add` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
