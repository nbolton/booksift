#!/usr/bin/php

<?php

echo "\nScript started!\n\n";

include "./config.php";

$AssocID = $cfg['amazon_assoc_id'];
$Token = $cfg['amazon_token'];

// Open the DB connection with default values.
include "./drivers/mysqldb.php";
$db = new DBDriver($cfg['db_conf']);

// Create the Amazon Search environment.
include "./drivers/amazon.php";
$AmazonSoap = new AmazonSearch($cfg['amazon_wdsl'], FALSE);

// Send request for the search results.
function getSoapResult($AmazonSoap, $Keywords, $Page, $AssocID, $Token) {
	return $AmazonSoap->Proxy->KeywordSearchRequest(
		array(
			'keyword' => $Keywords,
			'mode'    => 'books-uk',
			'tag'     => $AssocID,
			'devtag'  => $Token,
			'type'    => 'heavy',
			'locale'	=> 'uk',
			'sort'		=> 'daterank',
			'page'		=> $Page
		)
	);
}

function gatherResults($AmazonSoap, $Keywords, $AssocID, $Token, $UserID) {
	
	global $db;
	
	$InitSoapResult = getSoapResult($AmazonSoap, $Keywords, 1, $AssocID, $Token);
	
	if (count($InitSoapResult['Details'])) {
		
		// Add the first page of books to array.
		foreach ($InitSoapResult['Details'] as $Row) {
			$ResultsArray[] = array(
				"Isbn" =>	$Row['Isbn'],
				"ProductName" =>	$Row['ProductName'],
				"Url" =>	$Row['Url'],
				"Media" =>	$Row['Media'],
			);
		}
	}
	
	foreach ($ResultsArray as $Row) {
		
		$BookExists = $db->lookup(
			"SELECT COUNT(`id`) FROM `history`
			WHERE `uid` = '{$UserID}' AND
			`isbn` = '{$Row['Isbn']}'",
			"Check if book already recorded"
		);
		
		if ($BookExists == 0) {
			$db->query(
				"INSERT `history` SET uid = '{$UserID}',
				`isbn` = '{$Row['Isbn']}', `time` = NOW()",
				"Insert book into history."
			);
			
			$NewBooks[] = $Row;
		}
	}
	
	$Out = "";
	if (isset($NewBooks)) {
		foreach ($NewBooks as $Book) {
			$Out .= $Book['ProductName'] .
				" ({$Book['Media']}) - <a href='{$Book['Url']}'" .
				" target='_blank'>Buy now!</a>\n<br>";
		}
	}
	
	return $Out;
}

$db->query(
	"SELECT queries.id AS ID, queries.uid_create,
	queries.keywords AS Keywords, users.username AS uid_name,
	users.email AS uid_email FROM queries
	LEFT JOIN users ON users.id = queries.uid_create",
	"Select all queries."
);

if ($db->get_num_rows()) {
	while ($Row = $db->fetch_row()) {
		// Group all queries to their owners.
		$Uid = $Row['uid_create'];
		$Queries[$Uid]['Uid'] = $Row['uid_create'];
		$Queries[$Uid]['Name'] = $Row['uid_name'];
		$Queries[$Uid]['Email'] = $Row['uid_email'];
		$Queries[$Uid]['Queries'][] = array('ID' => $Row['ID'], 'Keywords' => $Row['Keywords']);
	}
}

foreach ($Queries as $UsersQueries) {
	$BooksFound = false;
	$Email = "{$UsersQueries['Name']},<br><br>";
	$Email .= "We have found you some new books!<br><br>";
	
	if (count($UsersQueries['Queries'])) {
		foreach ($UsersQueries['Queries'] as $Query) {
			$Results = gatherResults($AmazonSoap, $Query['Keywords'], $AssocID, $Token, $UsersQueries['Uid']);
			$Email .= "<p>Keywords: {$Query['Keywords']}</p>" . $Results;
			if ($Results != "") $BooksFound = true;
		}
		$Email .= "<br><br>End of Message! ";
	}
	
	if ($BooksFound == true) {
		$Subject = "We found you some new books!";
		$Headers = "From: Booksift <{$cfg['admin_email']}>;\r\n";
		$Headers .= "Content-type: text/html;\r\n";
		echo "Emailing: {$UsersQueries['Email']}\n";
		mail($UsersQueries['Email'], $Subject, $Email, $Headers);
	}
}

echo "\nScript done!\n\n";

?>