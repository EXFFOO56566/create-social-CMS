<?php
function emoticons_upgrade_database() {
	/**update_emoticon_icon('):', 'plugins/emoticons/images/emoticons/Smiley-1_24.png');
	 * update_emoticon_icon('%:', 'plugins/emoticons/images/emoticons/Smiley-2_24.png');
	 * update_emoticon_icon(':‑(', 'plugins/emoticons/images/emoticons/Smiley-3_24.png');
	 * update_emoticon_icon(':$', 'plugins/emoticons/images/emoticons/Smiley-4_24.png');
	 * update_emoticon_icon(':‑|', 'plugins/emoticons/images/emoticons/Smiley-5_24.png');
	 * update_emoticon_icon('|‑O', 'plugins/emoticons/images/emoticons/Smiley-6_24.png');
	 * update_emoticon_icon(':‑&', 'plugins/emoticons/images/emoticons/Smiley-8_24.png');
	 * update_emoticon_icon('%)', 'plugins/emoticons/images/emoticons/Smiley-9_24.png');
	 * update_emoticon_icon('*:*', 'plugins/emoticons/images/emoticons/Smiley-10_24.png');
	 * update_emoticon_icon('B^D', 'plugins/emoticons/images/emoticons/Smiley-11_24.png');
	 * update_emoticon_icon('(-_-)zzz', 'plugins/emoticons/images/emoticons/Smiley-17_24.png');
	 * update_emoticon_icon('@}‑;‑', 'plugins/emoticons/images/emoticons/Smiley-16_24.png');
	 * update_emoticon_icon('}:‑', 'plugins/emoticons/images/emoticons/Smiley-19_24.png');
	 * update_emoticon_icon('(/_;)', 'plugins/emoticons/images/emoticons/Smiley-24_24.png');
	 * update_emoticon_icon('sti-ck$1', 'plugins/emoticons/images/stickers/1.png');
	 * update_emoticon_icon('sti-ck$2', 'plugins/emoticons/images/stickers/2.png');
	 * update_emoticon_icon('sti-ck$3', 'plugins/emoticons/images/stickers/3.png');
	 * update_emoticon_icon('sti-ck$4', 'plugins/emoticons/images/stickers/4.png');
	 * update_emoticon_icon('sti-ck$5', 'plugins/emoticons/images/stickers/5.png');
	 * update_emoticon_icon('sti-ck$6', 'plugins/emoticons/images/stickers/6.png');
	 * update_emoticon_icon('sti-ck$7', 'plugins/emoticons/images/stickers/7.png');
	 * update_emoticon_icon('sti-ck$8', 'plugins/emoticons/images/stickers/8.png');
	 * update_emoticon_icon('sti-ck$9', 'plugins/emoticons/images/stickers/9.png');
	 * update_emoticon_icon('sti-ck$-10', 'plugins/emoticons/images/stickers/10.png');
	 * update_emoticon_icon('sti-ck$-11', 'plugins/emoticons/images/stickers/11.png');
	 * update_emoticon_icon('sti-ck$-12', 'plugins/emoticons/images/stickers/12.png');
	 * update_emoticon_icon('sti-ck$-13', 'plugins/emoticons/images/stickers/13.png');
	 * update_emoticon_icon('sti-ck$-14', 'plugins/emoticons/images/stickers/14.png');
	 * update_emoticon_icon('sti-ck$-15', 'plugins/emoticons/images/stickers/15.png');
	 * update_emoticon_icon('sti-ck$-16', 'plugins/emoticons/images/stickers/16.png');  **/
	add_emoticon(array(
		'title' => 'face',
		'symbol' => '):',
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-1_24.png',
		'category' => 1,
	));

	add_emoticon(array(
		'title' => 'horn',
		'symbol' => '%:',
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-2_24.png',
		'category' => 1,
	));

	add_emoticon(array(
		'title' => 'crying',
		'symbol' => ":‑(",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-3_24.png',
		'category' => 1,
	));

	add_emoticon(array(
		'title' => 'shock',
		'symbol' => ":$",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-4_24.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'no-expression',
		'symbol' => ":‑|",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-5_24.png',
		'category' => 1,
	));

	add_emoticon(array(
		'title' => 'cool',
		'symbol' => "|‑O",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-6_24.png',
		'category' => 1,
	));

	add_emoticon(array(
		'title' => 'eye broken',
		'symbol' => ":‑&",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-8_24.png',
		'category' => 1,
	));

	add_emoticon(array(
		'title' => 'Drunk',
		'symbol' => "%)",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-9_24.png',
		'category' => 1,
	));

	add_emoticon(array(
		'title' => 'Love Face',
		'symbol' => ":*",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-10_24.png',
		'category' => 1,
	));

	add_emoticon(array(
		'title' => 'Happy',
		'symbol' => "B^D",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-11_24.png',
		'category' => 1,
	));

	add_emoticon(array(
		'title' => 'Sleeping',
		'symbol' => "(-_-)zzz",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-17_24.png',
		'category' => 1,
	));

	add_emoticon(array(
		'title' => 'Rose',
		'symbol' => "@}‑;‑",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-16_24.png',
		'category' => 1,
	));

	add_emoticon(array(
		'title' => 'Devil',
		'symbol' => "}:‑",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-19_24.png',
		'category' => 1,
	));

	add_emoticon(array(
		'title' => 'Crying',
		'symbol' => "(/_;)",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-24_24.png',
		'category' => 1,
	));

	//stickers
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$1",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/1.png',
		'category' => 2,
	));

	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$2",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/2.png',
		'category' => 2,
	));

	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$3",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/3.png',
		'category' => 2,
	));

	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$4",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/4.png',
		'category' => 2,
	));

	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$5",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/5.png',
		'category' => 2,
	));

	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$6",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/6.png',
		'category' => 2,
	));

	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$7",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/7.png',
		'category' => 2,
	));

	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$8",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/8.png',
		'category' => 2,
	));

	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$9",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/9.png',
		'category' => 2,
	));

	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$-10",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/10.png',
		'category' => 2,
	));

	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$-11",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/11.png',
		'category' => 2,
	));

	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$-12",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/12.png',
		'category' => 2,
	));

	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$-13",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/13.png',
		'category' => 2,
	));

	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$-14",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/14.png',
		'category' => 2,
	));

	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$-15",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/15.png',
		'category' => 2,
	));

	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$-16",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/16.png',
		'category' => 2,
	));

	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$-17",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/17.png',
		'category' => 2,
	));


	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$-19",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/19.png',
		'category' => 2,
	));

	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$-20",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/20.png',
		'category' => 2,
	));

	add_emoticon(array(
		'title' => 'mocking',
		'symbol' => "#@@",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-25_32.png',
		'category' => 1,
	));

	add_emoticon(array(
		'title' => 'Sad face',
		'symbol' => "($)",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-26_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Laughing',
		'symbol' => "!^!",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-31_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Angry',
		'symbol' => "*/",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-29_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Exclaim',
		'symbol' => "()%",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-28_32.png',
		'category' => 1,
	));

	add_emoticon(array(
		'title' => 'Thinking of you',
		'symbol' => "()#",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-30_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Love',
		'symbol' => "^()",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-34_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Grin',
		'symbol' => "!-&",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-35_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Whaoooo',
		'symbol' => "C/!",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-36_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Speechless',
		'symbol' => "A&D",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-37_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Red face',
		'symbol' => "_(!",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-45_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Tongue in Cheek',
		'symbol' => "ABC",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-39_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Red lip',
		'symbol' => "**!",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-41_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Weldone',
		'symbol' => "#%#",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-43_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Knucles',
		'symbol' => "@KN",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-44_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Running',
		'symbol' => "(R)",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-40_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Passing by',
		'symbol' => "-(^",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-42_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Eyes Poking',
		'symbol' => "{>",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-46_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Togetherness',
		'symbol' => "*/$",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-47_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Clapping',
		'symbol' => "<>",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-48_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Wink',
		'symbol' => "@?>",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-54_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Thumb up',
		'symbol' => "{*}",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-49_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Thumb down',
		'symbol' => ':!_',
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-50_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Father Xmas',
		'symbol' => "xma",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-51_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Tired',
		'symbol' => "_^}",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-55_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Fist',
		'symbol' => "+-/",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-52_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Annoyed',
		'symbol' => "(-)",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-59_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Hungry',
		'symbol' => "LP",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-56_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Smile',
		'symbol' => "[~]",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-57_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Frustration',
		'symbol' => "&~",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-60_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Listen',
		'symbol' => "*~!",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-58_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Star',
		'symbol' => "***",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-53_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Tears',
		'symbol' => "*(*)",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-61_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Jest',
		'symbol' => "(v)",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-62_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Headache',
		'symbol' => "^&*",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-63_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Waving',
		'symbol' => "@{*",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-65_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Princess',
		'symbol' => "*PR*",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-64_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Family',
		'symbol' => "FAM",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-66_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Dancung',
		'symbol' => "#_!",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-67_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Lover cat',
		'symbol' => "@ct",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-68_32.png',
		'category' => 1,
	));

	add_emoticon(array(
		'title' => 'Cat',
		'symbol' => "--*",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-69_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Meditating',
		'symbol' => "=/!",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-70_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Hat',
		'symbol' => "=/;",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-72_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Briefcase',
		'symbol' => ":%/",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-73_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Air Bus',
		'symbol' => "::",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-74_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Ship',
		'symbol' => "[=]",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-75_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Traffic Light',
		'symbol' => "[/]",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-76_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Siren',
		'symbol' => "[?]",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-77_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Bicycle',
		'symbol' => ",:/",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-78_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Dress',
		'symbol' => "[^]",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-79_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Hand Bag',
		'symbol' => "=_=",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-80_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Sneakers',
		'symbol' => "=!=",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-82_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Engagement ring',
		'symbol' => "&^;",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-83_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Truck',
		'symbol' => "trk",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-84_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Umbrela',
		'symbol' => "+_+",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-86_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Back Pack',
		'symbol' => "+_",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-87_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Graduation Cap',
		'symbol' => "/_+",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-88_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Crown',
		'symbol' => "CRW",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-89_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Spectacle',
		'symbol' => "|=>",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-91_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Tie',
		'symbol' => "<@>",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-92_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Pants',
		'symbol' => "-PN",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-93_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Ladies shoe',
		'symbol' => "=:#",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-95_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Lipsticks',
		'symbol' => "00",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-98_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Helicopter',
		'symbol' => "S)!",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-99_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Tube',
		'symbol' => "T/",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-100_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Caravan',
		'symbol' => "!c*",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-102_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Ambulance',
		'symbol' => "+++",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-103_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Fire truck',
		'symbol' => "%F/",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-104_32.png',
		'category' => 1,
	));
	add_emoticon(array(
		'title' => 'Aeroplane',
		'symbol' => "AER",
		'icon' => 'themes/default/plugins/emoticons/images/emoticons/Smiley-109_32.png',
		'category' => 1,
	));

	//stickers
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$21",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/21.png',
		'category' => 2,
	));

	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$22",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/22.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$23",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/23.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$24",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/24.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$25",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/25.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$26",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/26.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$27",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/27.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$28",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/28.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$29",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/29.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$30",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/30.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$31",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/31.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$32",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/32.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$33",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/33.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$34",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/34.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$35",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/35.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$36",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/36.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$37",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/37.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$38",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/38.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$39",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/39.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$40",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/40.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$41",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/41.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$42",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/42.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$43",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/43.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$44",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/44.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$45",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/45.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$46",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/46.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$47",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/47.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$48",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/48.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$49",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/49.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$50",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/50.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$51",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/51.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$52",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/52.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$53",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/53.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$54",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/54.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$55",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/55.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$56",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/56.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$57",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/57.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$58",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/58.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$59",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/59.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$60",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/60.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$61",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/61.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$62",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/62.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$63",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/63.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$64",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/64.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$65",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/65.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$66",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/66.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$67",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/67.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$68",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/68.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$69",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/69.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$70",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/70.png',
		'category' => 2,
	));
	add_emoticon(array(
		'title' => '---',
		'symbol' => "sti-ck$71",
		'icon' => 'themes/default/plugins/emoticons/images/stickers/71.png',
		'category' => 2,
	));
}
