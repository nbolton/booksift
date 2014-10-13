<?php

switch ($mod->action) {
	
	default:
		
		header("Location: ./?mod=search");
		exit;
		
	break;
	
	case "query":
		
		header("Location: " . SearchFilterURL());
		exit;
		
	break;
	
	case "results":
		
		if (!$_GET['keywords']) {
			$mod->notify('no_keywords');
			$mod->parse();
			exit;
		}
		
		function GetMicroTime() {
			list($Usec, $Sec) = explode(" ", microtime());
			return ((float)$Usec + (float)$Sec);
		}
		
		function PageLoadTime($ProcessStart) {
			$Time = explode(".", GetMicroTime() - $ProcessStart);
			$Time[1] = substr($Time[1],0,3);
			
			return "{$Time[0]}.{$Time[1]}";
		}
		
		// Search started at this time...
		$SearchStart = GetMicroTime();
		
		// Create the Amazon Search environment.
		include "../src/drivers/amazon.php";
		$AmazonSoap = new AmazonSearch($cfg['amazon_wdsl'], FALSE);
		
		// This value cannot be changed!
		$Limit = 10;
		
		// If no Sort flag, assigns one.
		if (isset($_GET['sort'])) {
			$Sort = $_GET['sort'];
		} else {
			$Sort = 'salesrank';
		}
		
		// Gets true/filtered page number.
		if (isset($_GET['page'])) {
			if ($_GET['page'] >= 1) {
				$Page = $_GET['page'];
			}
		} else {
			$Page = 1;
		}
		
		// Send request for the search results.
    $SoapResult = $AmazonSoap->Proxy->KeywordSearchRequest(
			array(
				'keyword' => htmlentities($_GET['keywords']),
				'mode'    => 'books-uk',
				'tag'     => $cfg['amazon_assoc_id'],
				'devtag'  => $cfg['amazon_token'],
				'type'    => 'lite',
				'locale'	=> 'uk',
				'sort'		=> $Sort,
				'page'		=> $Page
			)
		);
		
		$t->set_file('SubTemplate', "{$mod->mod}_{$mod->action}.ihtml");
		$t->set_block('SubTemplate', 'SubTemplate');
		
		// Show the keywords entered.
		$t->set_var('Keywords', $_GET['keywords']);
		
		/*
		if (!isset($SoapResult['TotalResults'])) {
			$mod->notify('no_results');
			break;
		}
		*/
		
		// Find the beginning number of this page.
		$ResultNum = ($Page * $Limit) - $Limit;
		$FirstResult = $ResultNum + 1;
		$LastResult = $ResultNum + $Limit;
		$LastPage = $Page - 1;
		$NextPage = $Page + 1;
		
		// Make sure we don't lie about the results on the page.
		if (isset($SoapResult['TotalResults'])) {
			if ($LastResult > $SoapResult['TotalResults']) {
				$LastResult = $SoapResult['TotalResults'];
			}
		}
		
		// Change a value of a variable for the page's URL.
		function changePageUrl($NewVal, $PreVar, $SearchPreg = '[\d]*') {
			if (stristr($_SERVER['REQUEST_URI'], $PreVar)) {
				return preg_replace("/(.*){$PreVar}{$SearchPreg}(.*)/i", "$1" . $PreVar . $NewVal . "$2", $_SERVER['REQUEST_URI']);
			} else {
				return $_SERVER['REQUEST_URI'] . $PreVar . $NewVal;
			}
		}
		
		if ($Page > 1) {
			// Link to jump back to the first page.
			$PageUrl = changePageUrl(1, '&page=');
			$PageMarkers[] = "<a href='$PageUrl' onClick=\"notifySearching('$PageUrl')\">&laquo;&laquo;</a>";
			
			// Create link to jump to last page.
			$PageUrl = changePageUrl($LastPage, '&page=');
			$PageMarkers[] = "<a href='$PageUrl' onClick=\"notifySearching('$PageUrl')\">&laquo; {$lang->mixed['back']}</a>";
		}
		
		$PageMin = $Page - 4;
		$PageMax = $Page + 5;
		$PageUrl = "";
		
		if (isset($SoapResult['TotalPages'])) {
			if ($SoapResult['TotalPages'] > 1) {
				for ($i = $PageMin; $i < $PageMax; $i++) {
					
					// If we are within the valid page numbers.
					if (($i > 0) && ($i <= $SoapResult['TotalPages'])) {
						if ($Page != $i) {
							$PageUrl = changePageUrl($i, '&page=');
							$PageMarkers[] = "[<a href='$PageUrl' onClick=\"notifySearching('$PageUrl')\">{$i}</a>]";
							
						} else {
							// If we are on this page, mark it.
							$PageMarkers[] = "[<b>{$i}</b>]";
						}
						
					} elseif ($i > $SoapResult['TotalPages']) {
						// Since we can't go back in time, stop here.
						break;
						
					} else {
						// Push the page markers to the right.
						$PageMax++;
					}
				}
			}
			
			if ($Page != $SoapResult['TotalPages']) {
				// Create a link to the next page.
				$PageUrl = changePageUrl($NextPage, '&page=');
				$PageMarkers[] = "<a href='$PageUrl' onClick=\"notifySearching('$PageUrl')\">{$lang->mixed['next']} &raquo;</a>";
				
				// Link to the last page.
				$PageUrl = changePageUrl($SoapResult['TotalPages'], '&page=');
				$PageMarkers[] = "<a href='$PageUrl' onClick=\"notifySearching('$PageUrl')\">&raquo;&raquo;</a>";
			}
		}
		
		// Set the types of searches the user can perform.
		// Format: AmazonName, lang_name, lang_desc, lang_asc
		$SortTypes = array(
			array("salesrank", "sales_rank", "highest", "lowest"),
			array("daterank", "release_date", "newest")
		);
		
		// Build up a list of sort markers.
		foreach($SortTypes as $SortType) {
			$Marker = "{$lang->mixed[$SortType[1]]} (";
			
			if (isset($SortType[2])) {
				$DescPageUrl = changePageUrl("{$SortType[0]}", '&sort=', '[\-a-zA-Z]*');
				
				// Note: The '+' character is translated to a space.
				if ($Sort == $SortType[0]) {
					$Marker .= "<b>{$lang->mixed[$SortType[2]]}</b>";
				} else {
					$Marker .= "<a href='$DescPageUrl' onClick=\"notifySearching('$PageUrl')\">{$lang->mixed[$SortType[2]]}</a>";
				}
			}
			
			if (isset($SortType[3])) {
				$AscPageUrl = changePageUrl("-{$SortType[0]}", '&sort=', '[\-a-zA-Z]*');
				
				if ($Sort == "-{$SortType[0]}") {
					$Marker .= "/<b>{$lang->mixed[$SortType[3]]}</b>";
				} else {
					$Marker .= "/<a href='$AscPageUrl' onClick=\"notifySearching('$PageUrl')\">{$lang->mixed[$SortType[3]]}</a>";
				}
			}
			
			$SortMarkers[] = $Marker . ')';
		}
		
		// Set the markers and sort by flags etc then parse.
		$PageNav = '<b>' . $lang->mixed['sort_by'] . ':</b> ' . implode($SortMarkers, ', ') . '<br>';
		
		// Only show page markers if there is more than 1 page!
		if (isset($SoapResult['TotalPages'])) {
			if ($SoapResult['TotalPages'] > 1) {
				$PageNav .= "<b>{$lang->mixed['page']}:</b> " . implode($PageMarkers, ' ') . '<br><br>';
			}
			
			// Set the page & results stats.
			$PageNav .= $lang->replace('showing_page_info', TRUE,
				array(
					'page' => $Page, 'total' => $SoapResult['TotalPages'],
					'first' => $FirstResult, 'last' => $LastResult,
					'total_results' => $SoapResult['TotalResults'],
					'search_time' => PageLoadTime($SearchStart)
				)
			);
			
			$t->set_var('PageNav', $PageNav);
		}
		
		$t->parse('SubTemplate', 'SubTemplate');
		
		if (isset($SoapResult['Details'])) {
			
			$t->set_block('SubTemplate', 'ResultRow', 'ResultsBlock');
			
			foreach ($SoapResult['Details'] as $row) {
				
				$AuthorsFilled = false;
				if (isset($row['Authors'])) {
					if (is_array($row['Authors'])) {
						$row['Authors'] = implode($row['Authors'], ', ');
						$AuthorsFilled = true;
					}
				}
				
				if (!$AuthorsFilled) {
					$row['Authors'] = "n/a";
				}
				
				$row['ResultNum'] = ++$ResultNum;
				$row['Availability'] = ucfirst($row['Availability']);
				$row['ImageName'] = preg_replace("/(.*)\/(.*)/", "$2", $row['ImageUrlSmall']);
				$row['ImagePath'] = "../images/amazon/{$row['ImageName']}";
				
				// Get raw prices for the books.
				if (isset($row['ListPrice'])) {
					$row['ListPriceRaw'] = preg_replace("/{$cfg['user_currency']}(.*)/", "$1", $row['ListPrice']);
				}
				
				if (isset($row['OurPrice'])) {
					$row['OurPriceRaw'] = preg_replace("/{$cfg['user_currency']}(.*)/", "$1", $row['OurPrice']);
				}
				
				if (isset($row['ListPriceRaw']) && isset($row['OurPriceRaw'])) {
					$YouSave = currency($row['ListPriceRaw'] - $row['OurPriceRaw']);
					$row['YouSave'] = "";
					if ($YouSave > 0) {
						$YouSavePercent = round(($YouSave / $row['ListPriceRaw']) * 100);
						$row['YouSave'] = '<span class="bodyTextDarkBold">' . $lang->mixed['you_save'];
						$row['YouSave'] .= ":</span> {$cfg['user_currency']}{$YouSave} ($YouSavePercent%)";
					}
				}
				
				if (!file_exists($row['ImagePath'])) {
					if ($cache_handle = @fopen($row['ImagePath'], 'w')) {
						if (@fopen($row['ImageUrlSmall'], 'r')) {
							fwrite($cache_handle, implode(file($row['ImageUrlSmall'])));
							fclose($cache_handle);
						}
					}
				}
				
				if (@filesize($row['ImagePath']) < 850) {
					
					// Must be a dead image.
					if (file_exists($row['ImagePath'])) {
						@unlink($row['ImagePath']);
					}
					
					$row['ImagePath'] = '../images/icon_no_image.gif';
				}
				
				$img_size = getimagesize($row['ImagePath']);
				$row['ImageWidth'] = $img_size[0];
				$row['ImageHeight'] = $img_size[1];
				
				$t->set_var($row);
				$t->parse('ResultsBlock', 'ResultRow', TRUE);
			}
		} else {
			$mod->notify($lang->module_lang['no_results']);
		}
		
		$mod->parse();
		
	break;
}

?>