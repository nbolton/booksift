<?php

switch ($mod->action) {
	
	default:
		
		header("Location: ./?mod=news&action=browse");
		exit;
		
	break;
	
	case "browse":
		
		$t->set_file('SubTemplate', "{$mod->mod}_{$mod->action}.ihtml");
		$t->set_block('SubTemplate', 'SubTemplate');
		
		// Default header comes first.
		$headers = array(
			array('date', 'date_create'), 'title'
		);
		
		include "../src/drivers/headers.php";
		$hs = new HeaderSort($headers);
		
		$t->set_var('news_help',
			$lang->replace('news_help', TRUE, 'tip',
				"<span class='bodyTextDarkBold'>{$lang->mixed['tip']}</span>"
			)
		);
		
		$t->parse('SubTemplate', 'SubTemplate');
		
		$db->query(
			"SELECT news.id, news.title,
			DATE_FORMAT(news.date_create, '%W, %M %e, %Y %l:%i %p') AS date
			FROM news ORDER BY news.{$hs->sql['sort']} {$hs->sql['dir']}",
			"Get last X news posts."
		);
		
		if ($db->get_num_rows()) {
			$t->set_block('SubTemplate', 'NewsRow', 'NewsBlock');
			
			while ($row = $db->fetch_row()) {
				$t->set_var($row);
				$t->parse('NewsBlock', 'NewsRow', TRUE);
			}
		}
		
		$mod->parse();
		
	break;
	
	case "details":
		
		$id = $_REQUEST['id'] + 0;
		
		$db->query(
			"SELECT news.id, news.title, news.body, users.username AS author, users.email,
			DATE_FORMAT(news.date_create, '%W, %M %e, %Y %l:%i %p') AS date
			FROM news LEFT JOIN users ON users.id = news.uid_create
			WHERE news.id = '{$id}'",
			"Get last 3 news posts."
		);
		
		if ($db->get_num_rows()) {
			
			$t->set_file('SubTemplate', "{$mod->mod}_{$mod->action}.ihtml");
			$t->set_block('SubTemplate', 'SubTemplate');
			
			$row = $db->fetch_row();
			$row['body'] = nl2br($row['body']);
			
			if ($row['email']) {
				$row['author'] = "<b>{$row['author']}</b>";
			}
			
			$t->set_var($row);
			
			$t->parse('SubTemplate', 'SubTemplate');
			$mod->parse();
			
		} else {
			$mod->notify("inavlid_news_id");
			$mid->parse();
			exit;
		}
		
	break;
}

?>