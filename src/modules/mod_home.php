<?php

switch ($mod->action) {
	
	default:
		
		header("Location: ./?mod=home&action=news");
		exit;
		
	break;
	
	case "news":
		
		$t->set_file('SubTemplate', "{$mod->mod}_{$mod->action}.ihtml");
		$t->set_block('SubTemplate', 'SubTemplate');
		
		$db->query(
			"SELECT news.id, news.title, news.body, users.username AS author, users.email,
			DATE_FORMAT(news.date_create, '%W, %M %e, %Y %l:%i %p') AS date
			FROM news LEFT JOIN users ON users.id = news.uid_create
			ORDER BY news.date_create DESC LIMIT 5",
			"Get last 5 news posts."
		);
		
		if ($db->get_num_rows()) {
			$t->set_block('SubTemplate', 'NewsRow', 'NewsBlock');
			
			while ($row = $db->fetch_row()) {
				$t->set_file("NewsDetailsRow", "news_details.ihtml");
				$t->set_block("InputRow", "NewsDetailsRow", "NewsDetailsRow");
				
				$link = "?mod=news&action=details&id={$row['id']}";
				$row['body'] = nl2br(substr_ext($row['body'], 500, array($lang->mixed['more'], $link)));
				
				if ($row['email']) {
					$row['author'] = "<b>{$row['author']}</b>";
				}
				
				$t->set_var($row);
				
				$t->parse('NewsDetailsRow', 'NewsDetailsRow');
				$t->parse('NewsBlock', 'NewsRow', TRUE);
			}
		}
		
		$mod->parse();
		
	break;
}

?>