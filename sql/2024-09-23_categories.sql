UPDATE `article_category` SET `title` = 'Vzdělávací opory' WHERE `id` = '1';

ALTER TABLE `article`
    ADD `org` int(10) NULL AFTER `id`;

ALTER TABLE `article`
    ADD FOREIGN KEY (`org`) REFERENCES `organization` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `article`
    ADD `date_start` datetime NULL AFTER `date_updated`,
    ADD `date_end` datetime NULL AFTER `date_start`;