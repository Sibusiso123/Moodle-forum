CREATE TABLE `posts` (/*posts is the table*/
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `text` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `rating_info` (
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `rating_action` varchar(30) NOT NULL,
   CONSTRAINT UC_rating_info UNIQUE (user_id, post_id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `posts` (`id`, `text`) VALUES
(1, 'Is our Moodle working madoda!!'),
(2, 'Moodle Up voting'),
(3, 'Moodle Down voting'),
(4, 'Software Design');
