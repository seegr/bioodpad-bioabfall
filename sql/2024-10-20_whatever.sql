ALTER TABLE `article_category`
    ADD `text` text NULL;

UPDATE `article_category` SET `text` = 'Vítr skoro nefouká a tak by se na první pohled mohlo zdát, že se balónky snad vůbec nepohybují. Jenom tak klidně levitují ve vzduchu.' WHERE `id` = '1';
UPDATE `article_category` SET `text` = 'Jelikož slunce jasně září a na obloze byste od východu k západu hledali mráček marně, balónky působí jako jakási fata morgána uprostřed pouště.' WHERE `id` = '2';
UPDATE `article_category` SET `text` = 'Zkrátka široko daleko nikde nic, jen zelenkavá tráva, jasně modrá obloha a tři křiklavě barevné pouťové balónky, které se téměř nepozorovatelně pohupují ani ne moc vysoko, ani moc nízko nad zemí. ' WHERE `id` = '3';
UPDATE `article_category` SET `text` = 'Kdyby pod balónky nebyla sytě zelenkavá tráva, ale třeba suchá silnice či beton, možná by bylo vidět jejich barevné stíny - to jak přes poloprůsvitné barevné balónky prochází ostré sluneční paprsky.' WHERE `id` = '4';
UPDATE `article_category` SET `text` = 'Jenže kvůli všudy přítomné trávě jsou stíny balónků sotva vidět, natož aby šlo rozeznat, jakou barvu tyto stíny mají. ' WHERE `id` = '6';
UPDATE `article_category` SET `text` = 'Uvidět tak balónky náhodný kolemjdoucí, jistě by si pomyslel, že už tu takhle poletují snad tisíc let. Stále si víceméně drží výšku a ani do stran se příliš nepohybují.' WHERE `id` = '7';
UPDATE `article_category` SET `text` = 'Proti slunci to vypadá, že se slunce pohybuje k západu rychleji než balónky, a možná to tak skutečně je.' WHERE `id` = '8';
UPDATE `article_category` SET `text` = 'Nejeden filozof by mohl tvrdit, že balónky se sluncem závodí, ale fyzikové by to jistě vyvrátili. Z fyzikálního pohledu totiž balónky působí zcela nezajímavě.' WHERE `id` = '9';